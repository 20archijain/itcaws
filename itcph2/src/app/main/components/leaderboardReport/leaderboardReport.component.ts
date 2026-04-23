import { Component, OnDestroy, OnInit } from '@angular/core';
import { UntypedFormBuilder, UntypedFormGroup } from '@angular/forms';
import { Subscription } from 'rxjs';
import { finalize } from 'rxjs/operators';

import { FormService } from 'src/app/core/services/form.service';
import { REQUEST_STATUS, STATIC_MODULES } from 'src/app/app.constants';
import { MapConfig } from 'src/app/core/interfaces/common.interface';
import { COMMON_VALIDATORS } from 'src/app/core/validators/validations.list';
import { DropdownList, GetDownloadFileDetails, GetDownloadLeaderboardData } from 'src/app/core/interfaces/http-response.interface';
import { environment } from 'src/environments/environment';
import { LoaderService } from 'src/app/core/services/loader.service';
import { Functions } from 'src/app/core/utils/functions.list';

@Component({
  templateUrl: './leaderboardReport.component.html',
  standalone: false,
})
export class LeaderBoardComponent implements OnDestroy, OnInit {
  private subscription: Subscription[] = [];
  group!: UntypedFormGroup;
  branchOptions: DropdownList[] = [];
  teamOptions: DropdownList[] = [];
  columnSize = 12;
  markers: MapConfig[] = [];
  isBranchRequired = true;
  isTeamRequired = false;
  circleOptions: DropdownList[] = [];
  sectionOptions: DropdownList[] = [];
  wdCodeOptions: DropdownList[] = [];
  teamTypeOptions: DropdownList[] = [];
  monthList: DropdownList[] = [];
  errorMessages = {
    // branch: COMMON_VALIDATORS.messages.dropdown('Branch'),
    dateFrom: COMMON_VALIDATORS.messages.requiredOnly('From'),
    dateTo: COMMON_VALIDATORS.messages.requiredOnly('To'),
  };
  isDownloading = false;
  branchFilter = false

  constructor(private formService: FormService, private fb: UntypedFormBuilder, private loaderService: LoaderService) { }

  ngOnInit() {
    this.group = this.fb.group({
      dateFrom: ['', COMMON_VALIDATORS.validators.date],
      dateTo: ['', COMMON_VALIDATORS.validators.date],
      branch: [''],
      circle: [''],
      section: [''],
      wdCode: [''],
      dsType: [''],
      dsName: [''],
    });

    this.initialData();
  }

  ngOnDestroy() {
    this.subscription.forEach(sub => sub.unsubscribe());
  }

  initialData() {
    this.loaderService.startLoader();
    this.subscription.push(
      this.formService.getData<GetDownloadLeaderboardData>(environment.getRouteDataDownloadDataUrl)
        .pipe(
          finalize(() => this.loaderService.stopLoader()),
        )
        .subscribe(resp => {
          if (resp && resp.status === REQUEST_STATUS.SUCCESS && resp.data) {
            this.branchOptions = resp.data.branchList;
            this.branchFilter = resp.data.branchFilter ?? false;
            this.circleOptions = resp.data.circleList;
            this.sectionOptions = resp.data.sectionList;
            this.wdCodeOptions = resp.data.wdCodeList;
            this.teamTypeOptions = resp.data.dsType;
            this.teamOptions = resp.data.teamList;
          }
        })
    );
  }

  downloadData() {
    if (this.group.valid) {
      this.loaderService.startLoader();
      this.subscription.push(
        this.formService.customActionCall<GetDownloadFileDetails>(STATIC_MODULES.custom.getDownloadData, this.group.getRawValue(),
          null, environment.downloadExcelUrl)
          .pipe(
            finalize(() => {
              this.loaderService.stopLoader();
            })
          )
          .subscribe(resp => {
            if (resp && resp.status === REQUEST_STATUS.SUCCESS && resp.data) {
              Functions.downloadFile(resp.data.filePath, resp.data.fileName);
            }
          })
      );
    }
  }

  getCircle() {
    this.circleValue = null;
    this.sectionValue = null;
    this.wdCodeValue = null;
    this.dsTypeValue = null;
    this.dsNameValue = null;
    this.loaderService.startLoader();
    this.subscription.push(
      this.formService.customActionCall<GetDownloadLeaderboardData>(STATIC_MODULES.custom.getCircle, { branch: this.group.get('branch')?.value },
        null, environment.viewVanDsDataUrl)
        .pipe(finalize(() => this.loaderService.stopLoader()))
        .subscribe(resp => {
          if (resp && resp.status === REQUEST_STATUS.SUCCESS && resp.data) {
            this.circleOptions = resp.data.circleList;
            this.sectionOptions = resp.data.sectionList;
            this.wdCodeOptions = resp.data.wdCodeList;
            this.teamOptions = resp.data.teamList;
            this.teamTypeOptions = resp.data.dsType;
          }
        })
    );
  }

  getSection() {
    this.sectionValue = null;
    this.wdCodeValue = null;
    this.dsTypeValue = null;
    this.dsNameValue = null;
    this.loaderService.startLoader();
    this.subscription.push(
      this.formService.customActionCall<GetDownloadLeaderboardData>(STATIC_MODULES.custom.getSection, { branch: this.group.get('branch')?.value, circle: this.group.get('circle')?.value },
        null, environment.viewVanDsDataUrl)
        .pipe(finalize(() => this.loaderService.stopLoader()))
        .subscribe(resp => {
          if (resp && resp.status === REQUEST_STATUS.SUCCESS && resp.data) {
            this.sectionOptions = resp.data.sectionList;
            this.wdCodeOptions = resp.data.wdCodeList;
            this.teamOptions = resp.data.teamList;
            this.teamTypeOptions = resp.data.dsType;
          }
        })
    );
  }

  getWDCode() {
    this.wdCodeValue = null;
    this.dsTypeValue = null;
    this.dsNameValue = null;
    this.loaderService.startLoader();
    this.subscription.push(
      this.formService.customActionCall<GetDownloadLeaderboardData>(STATIC_MODULES.custom.getWDList, { branch: this.group.get('branch')?.value, circle: this.group.get('circle')?.value, section: this.group.get('section')?.value },
        null, environment.viewVanDsDataUrl)
        .pipe(finalize(() => this.loaderService.stopLoader()))
        .subscribe(resp => {
          if (resp && resp.status === REQUEST_STATUS.SUCCESS && resp.data) {
            this.wdCodeOptions = resp.data.wdCodeList;
            this.teamOptions = resp.data.teamList;
            this.teamTypeOptions = resp.data.dsType;
          }
        })
    );
  }

  getTeamsType() {
    this.dsTypeValue = null;
    this.dsNameValue = null;
    this.loaderService.startLoader();
    this.subscription.push(
      this.formService.customActionCall<GetDownloadLeaderboardData>(STATIC_MODULES.custom.getTeamsTypeList, { branch: this.group.get('branch')?.value, circle: this.group.get('circle')?.value, section: this.group.get('section')?.value, wdCode: this.group.get('wdCode')?.value },
        null, environment.viewVanDsDataUrl)
        .pipe(finalize(() => this.loaderService.stopLoader()))
        .subscribe(resp => {
          if (resp && resp.status === REQUEST_STATUS.SUCCESS && resp.data) {
            this.teamTypeOptions = resp.data.dsType;
            this.teamOptions = resp.data.teamList;
          }
        })
    );
  }

  getTeams() {
    this.dsNameValue = null;
    this.loaderService.startLoader();
    this.subscription.push(
      this.formService.customActionCall<GetDownloadLeaderboardData>(STATIC_MODULES.custom.getTeamsList, { branch: this.group.get('branch')?.value, circle: this.group.get('circle')?.value, section: this.group.get('section')?.value, wdCode: this.group.get('wdCode')?.value, dsType: this.group.get('dsType')?.value },
        null, environment.viewVanDsDataUrl)
        .pipe(finalize(() => this.loaderService.stopLoader()))
        .subscribe(resp => {
          if (resp && resp.status === REQUEST_STATUS.SUCCESS && resp.data) {
            this.teamOptions = resp.data.teamList;
          }
        })
    );
  }

  get branchValue() {
    return this.group && this.group.get('branch')?.value;
  }

  set circleValue(value: string | null) {
    this.circleOptions = [];
    this.group.get('circle')?.setValue(value);
  }
  set sectionValue(value: string | null) {
    this.sectionOptions = [];
    this.group.get('section')?.setValue(value);
  }
  set wdCodeValue(value: string | null) {
    this.wdCodeOptions = [];
    this.group.get('wdCode')?.setValue(value);
  }
  set dsTypeValue(value: string | null) {
    this.teamTypeOptions = [];
    this.group.get('dsType')?.setValue(value);
  }
  set dsNameValue(value: string | null) {
    this.teamOptions = [];
    this.group.get('dsName')?.setValue(value);
  }
}
