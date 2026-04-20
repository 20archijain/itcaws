import { Component, OnDestroy, OnInit } from '@angular/core';
import { UntypedFormBuilder, UntypedFormGroup } from '@angular/forms';
import { Subscription } from 'rxjs';
import { finalize } from 'rxjs/operators';

import { FormService } from 'src/app/core/services/form.service';
import { REQUEST_STATUS, STATIC_MODULES } from 'src/app/app.constants';
import { MapConfig } from 'src/app/core/interfaces/common.interface';
import { COMMON_VALIDATORS } from 'src/app/core/validators/validations.list';
import { DashboardData, DropdownList, GetDownloadFileDetails, GetLocationCoveredResponse } from 'src/app/core/interfaces/http-response.interface';
import { environment } from 'src/environments/environment';
import { LoaderService } from 'src/app/core/services/loader.service';
import { CsvDataFormat } from 'src/app/core/interfaces/helpers.interface';
import { Functions } from 'src/app/core/utils/functions.list';

@Component({
    templateUrl: './route-data-download.component.html',
    styleUrls: ["./route-data.component.scss"],
    standalone: false
})
export class RouteDataDownloadComponent implements OnDestroy, OnInit {
  private subscription: Subscription[] = [];
  group: UntypedFormGroup;
  branchOptions: DropdownList[] = [];
  teamOptions: DropdownList[] = [];
  columnSize = 12;
  markers: MapConfig[] = [];
  isBranchRequired = true;
  isTeamRequired = false;
  yearList: DropdownList<number, number>[] = [];
  circleOptions: DropdownList[] = [];
  sectionOptions: DropdownList[] = [];
  wdCodeOptions: DropdownList[] = [];
  teamTypeOptions: DropdownList[] = [];
  districtOptions: DropdownList[] = [];
  wdMarketOptions: DropdownList[] = [];
  wdPopGroupOptions: DropdownList[] = [];
  monthList: DropdownList[] = [];
  errorMessages = {
    branch: COMMON_VALIDATORS.messages.dropdown('Branch'),
    team: COMMON_VALIDATORS.messages.requiredOnly('Team Name'),
  };
  isDownloading = false;
  branchFilter = false

  constructor(private formService: FormService, private fb: UntypedFormBuilder, private loaderService: LoaderService) { }

  ngOnInit() {
    const currentDate = Functions.currentDate();
    this.group = this.fb.group({
      branch: ['', this.isBranchRequired ? COMMON_VALIDATORS.validators.requiredOnly : []],
      team: [null, this.isTeamRequired ? COMMON_VALIDATORS.validators.requiredOnly : []],
      month: [''],
      year: [currentDate.year],
      circle: [''],
      section: [''],
      wdCode: [''],
      dsType: [''],
      dsName: ['', COMMON_VALIDATORS.validators.dropdownAllOptional],
      district: [''],
      wdMarket: [''],
      wdPopGroup: [''],
    });

    this.initialData();
  }

  ngOnDestroy() {
    this.subscription.forEach(sub => sub.unsubscribe());
  }

  initialData() {
    this.loaderService.startLoader();
    this.subscription.push(
      this.formService.getData<GetLocationCoveredResponse>(environment.getRouteDataDownloadDataUrl)
        .pipe(
          finalize(() => this.loaderService.stopLoader()),
        )
        .subscribe(resp => {
          if (resp && resp.status === REQUEST_STATUS.SUCCESS) {
            this.branchOptions = resp.data.branchList;
            this.yearList = resp.data.yearList;
            this.monthList = resp.data.monthList;
            this.branchFilter = resp.data.branchFilter
            this.circleOptions = resp.data.circleList;
            this.sectionOptions = resp.data.sectionList;
            this.wdCodeOptions = resp.data.wdCodeList;
            this.teamTypeOptions = resp.data.teamType;
            this.teamOptions = resp.data.teamList;
            this.districtOptions = resp.data.districtList;
            this.wdMarketOptions = resp.data.wdMarketList;
            this.wdPopGroupOptions = resp.data.wdPopGroupList;
          }
        })
    );
  }

  downloadData() {
    if (this.group.valid && !this.isDownloading) {
      this.isDownloading = true;
      this.loaderService.startLoader();

      this.subscription.push(
        this.formService.customActionCall<CsvDataFormat | GetDownloadFileDetails>(STATIC_MODULES.custom.getDownloadData,
          this.group.getRawValue(), null, environment.downloadExcelUrl)
          .pipe(
            finalize(() => {
              this.isDownloading = false;
              this.loaderService.stopLoader();
            })
          )
          .subscribe(resp => {
            if (resp && resp.status === REQUEST_STATUS.SUCCESS) {
              if ((resp?.data as GetDownloadFileDetails)?.filePath) {
                Functions.downloadFile((resp.data as GetDownloadFileDetails).filePath, resp.data.fileName);
              } else {
                Functions.createCSV(resp?.data as CsvDataFormat);
              }
            }
          })
      );
    }
  }

  getBranch() {
    this.branchValue = null;
    this.circleValue = null;
    this.sectionValue = null;
    this.wdCodeValue = null;
    this.dsTypeValue = null;
    this.dsNameValue = null;
    this.wdMarketValue = null;
    this.wdPopGroupValue = null;
    this.loaderService.startLoader();
    this.subscription.push(
      this.formService.customActionCall<DashboardData>(STATIC_MODULES.custom.getBranch, { district: this.group.get('district').value }, null, environment.viewVanDsDataUrl)
        .pipe(finalize(() => this.loaderService.stopLoader()))
        .subscribe(resp => {
          if (resp && resp.status === REQUEST_STATUS.SUCCESS) {
            this.branchOptions = resp.data.branchList;
            this.circleOptions = resp.data.circleList;
            this.sectionOptions = resp.data.sectionList;
            this.wdCodeOptions = resp.data.wdCodeList;
            this.teamOptions = resp.data.teamList;
            this.teamTypeOptions = resp.data.teamType;
            this.wdMarketOptions = resp.data.wdMarketList;
            this.wdPopGroupOptions = resp.data.wdPopGroupList;
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
    this.wdMarketValue = null;
    this.wdPopGroupValue = null;
    this.loaderService.startLoader();
    this.subscription.push(
      this.formService.customActionCall<DashboardData>(STATIC_MODULES.custom.getCircle, { branch: this.group.get('branch').value }, null, environment.viewVanDsDataUrl)
        .pipe(finalize(() => this.loaderService.stopLoader()))
        .subscribe(resp => {
          if (resp && resp.status === REQUEST_STATUS.SUCCESS) {
            this.circleOptions = resp.data.circleList;
            this.sectionOptions = resp.data.sectionList;
            this.wdCodeOptions = resp.data.wdCodeList;
            this.teamOptions = resp.data.teamList;
            this.teamTypeOptions = resp.data.teamType;
            this.wdMarketOptions = resp.data.wdMarketList;
            this.wdPopGroupOptions = resp.data.wdPopGroupList;
          }
        })
    );
  }

  getSection() {
    this.sectionValue = null;
    this.wdCodeValue = null;
    this.dsTypeValue = null;
    this.dsNameValue = null;
    this.wdMarketValue = null;
    this.wdPopGroupValue = null;
    this.loaderService.startLoader();
    this.subscription.push(
      this.formService.customActionCall<DashboardData>(STATIC_MODULES.custom.getSection, { branch: this.group.get('branch').value, circle: this.group.get('circle').value }, null, environment.viewVanDsDataUrl)
        .pipe(finalize(() => this.loaderService.stopLoader()))
        .subscribe(resp => {
          if (resp && resp.status === REQUEST_STATUS.SUCCESS) {
            this.sectionOptions = resp.data.sectionList;
            this.wdCodeOptions = resp.data.wdCodeList;
            this.teamOptions = resp.data.teamList;
            this.teamTypeOptions = resp.data.teamType;
            this.wdMarketOptions = resp.data.wdMarketList;
            this.wdPopGroupOptions = resp.data.wdPopGroupList;
          }
        })
    );
  }

  getWDCode() {
    this.wdCodeValue = null;
    this.dsTypeValue = null;
    this.dsNameValue = null;
    this.wdMarketValue = null;
    this.wdPopGroupValue = null;
    this.loaderService.startLoader();
    this.subscription.push(
      this.formService.customActionCall<DashboardData>(STATIC_MODULES.custom.getWDList, { branch: this.group.get('branch').value, circle: this.group.get('circle').value, section: this.group.get('section').value }, null, environment.viewVanDsDataUrl)
        .pipe(finalize(() => this.loaderService.stopLoader()))
        .subscribe(resp => {
          if (resp && resp.status === REQUEST_STATUS.SUCCESS) {
            this.wdCodeOptions = resp.data.wdCodeList;
            this.teamOptions = resp.data.teamList;
            this.teamTypeOptions = resp.data.teamType;
            this.wdMarketOptions = resp.data.wdMarketList;
            this.wdPopGroupOptions = resp.data.wdPopGroupList;
          }
        })
    );
  }

  getTeamsType() {
    this.dsTypeValue = null;
    this.dsNameValue = null;
    this.loaderService.startLoader();
    this.subscription.push(
      this.formService.customActionCall<DashboardData>(STATIC_MODULES.custom.getTeamsTypeList, { branch: this.group.get('branch').value, circle: this.group.get('circle').value, section: this.group.get('section').value, wdCode: this.group.get('wdCode').value }, null, environment.viewVanDsDataUrl)
        .pipe(finalize(() => this.loaderService.stopLoader()))
        .subscribe(resp => {
          if (resp && resp.status === REQUEST_STATUS.SUCCESS) {
            this.teamTypeOptions = resp.data.teamType;
            this.teamOptions = resp.data.teamList;
          }
        })
    );
  }

  getTeamsName() {
    this.dsNameValue = null;
    this.loaderService.startLoader();
    this.subscription.push(
      this.formService.customActionCall<DashboardData>(STATIC_MODULES.custom.getTeamsList, { branch: this.group.get('branch').value, circle: this.group.get('circle').value, section: this.group.get('section').value, wdCode: this.group.get('wdCode').value, dsType: this.group.get('dsType').value }, null, environment.viewVanDsDataUrl)
        .pipe(finalize(() => this.loaderService.stopLoader()))
        .subscribe(resp => {
          if (resp && resp.status === REQUEST_STATUS.SUCCESS) {
            this.teamOptions = resp.data.teamList;
          }
        })
    );
  }

  get branchValue() {
    return this.group && this.group.get('branch').value;
  }

  set branchValue(value: string) {
    this.branchOptions = [];
    this.group.get('branch').setValue(value);
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
  set wdMarketValue(value: string) {
    this.wdMarketOptions = [];
    this.group.get('wdMarket').setValue(value);
  }
  set wdPopGroupValue(value: string) {
    this.wdPopGroupOptions = [];
    this.group.get('wdPopGroup').setValue(value);
  }
}
