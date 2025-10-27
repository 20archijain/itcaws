import { Component, OnDestroy, OnInit } from '@angular/core';
import { UntypedFormBuilder, UntypedFormGroup } from '@angular/forms';
import { Subscription } from 'rxjs';
import { finalize } from 'rxjs/operators';

import { environment } from 'src/environments/environment';
import { FormService } from 'src/app/core/services/form.service';
import { DashboardData, DropdownList, GetAddTeamDataResponse } from 'src/app/core/interfaces/http-response.interface';
import { CsvDataFormat } from 'src/app/core/interfaces/helpers.interface';
import { LoaderService } from 'src/app/core/services/loader.service';
import { REQUEST_STATUS, STATIC_MODULES } from 'src/app/app.constants';
import { Functions } from 'src/app/core/utils/functions.list';

@Component({
  templateUrl: './active-mdo-reporting.component.html',
  styleUrls: ["./active-mdo-reporting.component.scss"],
})
export class ActiveMDOUsersListingComponent implements OnInit, OnDestroy {
  private subscription: Subscription[] = [];
  header: string[] = [];
  body: string[] = [];
  isSelectable: boolean;
  sortOptions: DropdownList[] = [];
  branchOptions: DropdownList[] = [];
  wdCodeOptions: DropdownList[] = [];
  teamTypeOptions: DropdownList[] = [];
  circleOptions: DropdownList[] = [];
  sectionOptions: DropdownList[] = [];
  teamOptions: DropdownList[] = [];
  districtOptions: DropdownList[] = [];
  wdMarketOptions: DropdownList[] = [];
  wdPopGroupOptions: DropdownList[] = [];
  form: UntypedFormGroup;
  isExportBtnDisabled = false;
  showDownloadDataBtn = false;
  downloadDataBtnTitle = false;
  branchFilter = false;
  url = environment.getActiveUsersUrl;

  constructor(private formService: FormService, private fb: UntypedFormBuilder, private loaderService: LoaderService) { }

  ngOnInit() {
    this.form = this.fb.group({
      branch: [''],
      circle: [''],
      section: [''],
      wdCode: [''],
      dsType: [''],
      dsName: [''],
      dateFrom: [],
      dateTo: [],
      district: [''],
      wdMarket: [''],
      wdPopGroup: [''],
    });

    this.getInitialData();
  }

  ngOnDestroy() {
    this.subscription.forEach(sub => sub.unsubscribe());
  }


  getInitialData() {
    this.loaderService.startLoader();
    this.subscription.push(
      this.formService.getData<GetAddTeamDataResponse>(this.url, this.form.getRawValue())
        .pipe(
          finalize(() => this.loaderService.stopLoader())
        )
        .subscribe(resp => {
          if (resp && resp.status === REQUEST_STATUS.SUCCESS) {
            this.sortOptions = resp.data.sortOptions;
            this.branchOptions = resp.data.branchList;
            this.wdCodeOptions = resp.data.wdCodeList;
            this.teamTypeOptions = resp.data.teamType;
            this.teamOptions = resp.data.teamList;
            this.header = resp.data.viewHeader;
            this.body = resp.data.viewBody;
            this.isSelectable = resp.data.isSelectable;
            this.branchFilter = resp.data.branchFilter
            this.circleOptions = resp.data.circleList;
            this.sectionOptions = resp.data.sectionList;
            this.districtOptions = resp.data.districtList;
            this.wdMarketOptions = resp.data.wdMarketList;
            this.wdPopGroupOptions = resp.data.wdPopGroupList;
          }
        })
    );
  }

  exportTeams() {
    if (!this.isExportBtnDisabled) {
      this.isExportBtnDisabled = true;
      this.loaderService.startLoader();

      this.subscription.push(
        this.formService.customActionCall<CsvDataFormat>(STATIC_MODULES.custom.getDownloadData,
          this.form.getRawValue(), null, this.url)
          .pipe(
            finalize(() => {
              this.loaderService.stopLoader();
              this.isExportBtnDisabled = false;
            })
          )
          .subscribe(resp => {
            if (resp && resp.status === REQUEST_STATUS.SUCCESS) {
              Functions.createCSV(resp.data);
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
      this.formService.customActionCall<DashboardData>(STATIC_MODULES.custom.getBranch, { district: this.form.get('district').value }, null, environment.viewVanDsDataUrl)
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
      this.formService.customActionCall<DashboardData>(STATIC_MODULES.custom.getCircle, { branch: this.form.get('branch').value }, null, environment.viewVanDsDataUrl)
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
      this.formService.customActionCall<DashboardData>(STATIC_MODULES.custom.getSection, { branch: this.form.get('branch').value, circle: this.form.get('circle').value }, null, environment.viewVanDsDataUrl)
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
      this.formService.customActionCall<DashboardData>(STATIC_MODULES.custom.getWDList, { branch: this.form.get('branch').value, circle: this.form.get('circle').value, section: this.form.get('section').value }, null, environment.viewVanDsDataUrl)
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
      this.formService.customActionCall<DashboardData>(STATIC_MODULES.custom.getTeamsTypeList, { branch: this.form.get('branch').value, circle: this.form.get('circle').value, section: this.form.get('section').value, wdCode: this.form.get('wdCode').value }, null, environment.viewVanDsDataUrl)
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
      this.formService.customActionCall<DashboardData>(STATIC_MODULES.custom.getTeamsList, { branch: this.form.get('branch').value, circle: this.form.get('circle').value, section: this.form.get('section').value, wdCode: this.form.get('wdCode').value, dsType: this.form.get('dsType').value }, null, environment.viewVanDsDataUrl)
        .pipe(finalize(() => this.loaderService.stopLoader()))
        .subscribe(resp => {
          if (resp && resp.status === REQUEST_STATUS.SUCCESS) {
            this.teamOptions = resp.data.teamList;
          }
        })
    );
  }

  set branchValue(value: string) {
    this.branchOptions = [];
    this.form.get('branch').setValue(value);
  }

  set circleValue(value: string) {
    this.circleOptions = [];
    this.form.get('circle').setValue(value);
  }
  set sectionValue(value: string) {
    this.sectionOptions = [];
    this.form.get('section').setValue(value);
  }
  set wdCodeValue(value: string) {
    this.wdCodeOptions = [];
    this.form.get('wdCode').setValue(value);
  }
  set dsTypeValue(value: string) {
    this.teamTypeOptions = [];
    this.form.get('dsType').setValue(value);
  }
  set dsNameValue(value: string) {
    this.teamOptions = [];
    this.form.get('dsName').setValue(value);
  }
  set wdMarketValue(value: string) {
    this.wdMarketOptions = [];
    this.form.get('wdMarket').setValue(value);
  }
  set wdPopGroupValue(value: string) {
    this.wdPopGroupOptions = [];
    this.form.get('wdPopGroup').setValue(value);
  }
  clearForm() {
    this.form.reset();
    this.getInitialData();
  }

}
