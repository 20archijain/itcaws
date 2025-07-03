import { Component, OnDestroy, OnInit } from '@angular/core';
import { Subscription } from 'rxjs';
import { UntypedFormBuilder, UntypedFormGroup } from '@angular/forms';
import { finalize } from 'rxjs/operators';

import { FormService } from 'src/app/core/services/form.service';
import { environment } from 'src/environments/environment';
import { REQUEST_STATUS, STATIC_MODULES } from 'src/app/app.constants';
import { Functions } from 'src/app/core/utils/functions.list';
import { COMMON_VALIDATORS } from 'src/app/core/validators/validations.list';
import { DropdownList, GetDownloadBillCutResponse, GetDownloadFileDetails, GetDownloadLeaderboardData } from 'src/app/core/interfaces/http-response.interface';
import { LoaderService } from 'src/app/core/services/loader.service';

@Component({
  templateUrl: './binder-report.component.html',
  styleUrls: ["./binder-report.component.scss"],
})
export class BinderReportComponent implements OnDestroy, OnInit {
  private subscription: Subscription[] = [];
  group: UntypedFormGroup;
  branchOptions: DropdownList[] = [];
  teamOptions: DropdownList[] = [];
  isBranchRequired = true;
  isTeamRequired = false;
  branchFilter = false;
  circleOptions: DropdownList[] = [];
  sectionOptions: DropdownList[] = [];
  wdCodeOptions: DropdownList[] = [];
  teamTypeOptions: DropdownList[] = [];
  errorMessages = {
    branch: COMMON_VALIDATORS.messages.requiredOnly('Branch'),
    dateFrom: COMMON_VALIDATORS.messages.requiredOnly('From'),
    dateTo: COMMON_VALIDATORS.messages.requiredOnly('To'),
  };
  isDisabled = false;
  url = environment.getBinderReportDataUrl;

  constructor(private formService: FormService, private fb: UntypedFormBuilder, private loaderService: LoaderService) { }

  ngOnInit() {
    this.group = this.fb.group({
      branch: ['', COMMON_VALIDATORS.validators.requiredOnly],
      dateFrom: [null, COMMON_VALIDATORS.validators.requiredOnly],
      dateTo: [null, COMMON_VALIDATORS.validators.requiredOnly],
      circle: [''],
      section: [''],
      wdCode: [''],
      dsType: [''],
      dsName: [''],
    });

    this.loaderService.startLoader();
    this.subscription.push(
      this.formService.getData<GetDownloadBillCutResponse>(this.url)
        .pipe(
          finalize(() => this.loaderService.stopLoader()),
        )
        .subscribe(resp => {
          if (resp && resp.status === REQUEST_STATUS.SUCCESS) {
            this.branchOptions = resp.data.branchList;
            this.branchFilter = resp.data.branchFilter;
          }
        })
    );
  }

  getCircle() {
    this.circleValue = null;
    this.sectionValue = null;
    this.wdCodeValue = null;
    this.dsTypeValue = null;
    this.dsNameValue = null;
    this.loaderService.startLoader();
    this.subscription.push(
      this.formService.customActionCall<GetDownloadLeaderboardData>(STATIC_MODULES.custom.getCircle, { branch: this.group.get('branch').value },
        null, this.url)
        .pipe(finalize(() => this.loaderService.stopLoader()))
        .subscribe(resp => {
          if (resp && resp.status === REQUEST_STATUS.SUCCESS) {
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
      this.formService.customActionCall<GetDownloadLeaderboardData>(STATIC_MODULES.custom.getSection, { branch: this.group.get('branch').value, circle: this.group.get('circle').value },
        null, this.url)
        .pipe(finalize(() => this.loaderService.stopLoader()))
        .subscribe(resp => {
          if (resp && resp.status === REQUEST_STATUS.SUCCESS) {
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
      this.formService.customActionCall<GetDownloadLeaderboardData>(STATIC_MODULES.custom.getWDList, { branch: this.group.get('branch').value, circle: this.group.get('circle').value, section: this.group.get('section').value },
        null, this.url)
        .pipe(finalize(() => this.loaderService.stopLoader()))
        .subscribe(resp => {
          if (resp && resp.status === REQUEST_STATUS.SUCCESS) {
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
      this.formService.customActionCall<GetDownloadLeaderboardData>(STATIC_MODULES.custom.getTeamsTypeList, { branch: this.group.get('branch').value, circle: this.group.get('circle').value, section: this.group.get('section').value, wdCode: this.group.get('wdCode').value },
        null, this.url)
        .pipe(finalize(() => this.loaderService.stopLoader()))
        .subscribe(resp => {
          if (resp && resp.status === REQUEST_STATUS.SUCCESS) {
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
      this.formService.customActionCall<GetDownloadLeaderboardData>(STATIC_MODULES.custom.getTeamsList, { branch: this.group.get('branch').value, circle: this.group.get('circle').value, section: this.group.get('section').value, wdCode: this.group.get('wdCode').value, dsType: this.group.get('dsType').value },
        null, this.url)
        .pipe(finalize(() => this.loaderService.stopLoader()))
        .subscribe(resp => {
          if (resp && resp.status === REQUEST_STATUS.SUCCESS) {
            this.teamOptions = resp.data.teamList;
          }
        })
    );
  }

  ngOnDestroy() {
    this.subscription.forEach(sub => sub.unsubscribe());
  }

  download() {
    if (!this.isDisabled && this.group.valid) {
      console.log('abcd');
      this.isDisabled = true;
      this.loaderService.startLoader();
      this.subscription.push(
        this.formService.customActionCall<GetDownloadFileDetails>(STATIC_MODULES.custom.getDownloadData, this.group.getRawValue(),
          null, environment.downloadExcelUrl)
          .pipe(
            finalize(() => {
              this.isDisabled = false;
              this.loaderService.stopLoader();
            })
          )
          .subscribe(resp => {
            if (resp && resp.status === REQUEST_STATUS.SUCCESS) {
              Functions.downloadFile(resp.data.filePath, resp.data.fileName);
            }
          })
      );
    }
  }


  get branchValue() {
    return this.group && this.group.get('branch').value;
  }
  set circleValue(value: string) {
    this.circleOptions = [];
    this.group.get('circle').setValue(value);
  }
  set sectionValue(value: string) {
    this.sectionOptions = [];
    this.group.get('section').setValue(value);
  }
  set wdCodeValue(value: string) {
    this.wdCodeOptions = [];
    this.group.get('wdCode').setValue(value);
  }
  set dsTypeValue(value: string) {
    this.teamTypeOptions = [];
    this.group.get('dsType').setValue(value);
  }
  set dsNameValue(value: string) {
    this.teamOptions = [];
    this.group.get('dsName').setValue(value);
  }

}

