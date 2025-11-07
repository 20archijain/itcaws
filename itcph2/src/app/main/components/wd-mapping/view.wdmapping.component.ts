import { Component, OnDestroy, OnInit } from '@angular/core';
import { UntypedFormBuilder, UntypedFormGroup } from '@angular/forms';
import { Subscription } from 'rxjs';
import { finalize } from 'rxjs/operators';

import { environment } from 'src/environments/environment';
import { REQUEST_STATUS, STATIC_MODULES } from 'src/app/app.constants';
import { FormService } from 'src/app/core/services/form.service';
import { DropdownList, ViewWdMappingResponse } from 'src/app/core/interfaces/http-response.interface';
import { CsvDataFormat, EditConfig } from 'src/app/core/interfaces/helpers.interface';
import { LoaderService } from 'src/app/core/services/loader.service';
import { Functions } from 'src/app/core/utils/functions.list';

@Component({
  templateUrl: './view.wdmapping.component.html'
})
export class ViewWdMappingComponent implements OnDestroy, OnInit {
  private subscription: Subscription[] = [];
  header: string[] = [];
  body: string[] = [];
  url = environment.viewProjectsUrl;
  districtOptions: DropdownList[] = [];
  branchOptions: DropdownList[] = [];
  circleOptions: DropdownList[] = [];
  sectionOptions: DropdownList[] = [];
  wdCodeOptions: DropdownList[] = [];
  wdMarketOptions: DropdownList[] = [];
  wdPopGroupOptions: DropdownList[] = [];
  form: UntypedFormGroup;
  editConfig: EditConfig[] = [];
  isExportBtnDisabled = false;

  constructor(private formService: FormService, private fb: UntypedFormBuilder, private loaderService: LoaderService) { }

  ngOnInit() {
    this.form = this.fb.group({
      district: [],
      branch: [],
      circle: [],
      section: [],
      wdCode: [],
      wdMarket: [],
      wdPopGroup: [],
    });

    this.getInitialData();
  }

  getInitialData() {
    this.loaderService.startLoader();

    this.subscription.push(
      this.formService.getData<ViewWdMappingResponse>(this.url)
        .pipe(
          finalize(() => this.loaderService.stopLoader())
        )
        .subscribe(resp => {
          if (resp && resp.status === REQUEST_STATUS.SUCCESS) {
            this.districtOptions = resp.data.districtList;
            this.branchOptions = resp.data.branchList;
            this.circleOptions = resp.data.circleList;
            this.sectionOptions = resp.data.sectionList;
            this.wdCodeOptions = resp.data.wdCodeList;
            this.wdMarketOptions = resp.data.wdMarketList;
            this.wdPopGroupOptions = resp.data.wdPopGroupList;
            this.header = resp.data.viewHeader;
            this.body = resp.data.viewBody;
          }
        })
    );
  }

  getBranch() {
    this.branchValue = null;
    this.circleValue = null;
    this.sectionValue = null;
    this.wdCodeValue = null;
    this.wdMarketValue = null;
    this.wdPopGroupValue = null;
    this.loaderService.startLoader();
    this.subscription.push(
      this.formService.customActionCall<ViewWdMappingResponse>(STATIC_MODULES.custom.getBranch, { district: this.form.get('district').value }, null, this.url)
        .pipe(finalize(() => this.loaderService.stopLoader()))
        .subscribe(resp => {
          if (resp && resp.status === REQUEST_STATUS.SUCCESS) {
            this.branchOptions = resp.data.branchList;
            this.circleOptions = resp.data.circleList;
            this.sectionOptions = resp.data.sectionList;
            this.wdCodeOptions = resp.data.wdCodeList;
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
    this.wdMarketValue = null;
    this.wdPopGroupValue = null;
    this.loaderService.startLoader();
    this.subscription.push(
      this.formService.customActionCall<ViewWdMappingResponse>(STATIC_MODULES.custom.getCircle, { branch: this.form.get('branch').value }, null, this.url)
        .pipe(finalize(() => this.loaderService.stopLoader()))
        .subscribe(resp => {
          if (resp && resp.status === REQUEST_STATUS.SUCCESS) {
            this.circleOptions = resp.data.circleList;
            this.sectionOptions = resp.data.sectionList;
            this.wdCodeOptions = resp.data.wdCodeList;
            this.wdMarketOptions = resp.data.wdMarketList;
            this.wdPopGroupOptions = resp.data.wdPopGroupList;
          }
        })
    );
  }

  getSection() {
    this.sectionValue = null;
    this.wdCodeValue = null;
    this.wdMarketValue = null;
    this.wdPopGroupValue = null;
    this.loaderService.startLoader();
    this.subscription.push(
      this.formService.customActionCall<ViewWdMappingResponse>(STATIC_MODULES.custom.getSection, { circle: this.form.get('circle').value }, null, this.url)
        .pipe(finalize(() => this.loaderService.stopLoader()))
        .subscribe(resp => {
          if (resp && resp.status === REQUEST_STATUS.SUCCESS) {
            this.sectionOptions = resp.data.sectionList;
            this.wdCodeOptions = resp.data.wdCodeList;
            this.wdMarketOptions = resp.data.wdMarketList;
            this.wdPopGroupOptions = resp.data.wdPopGroupList;
          }
        })
    );
  }

  getWdCode() {
    this.wdCodeValue = null;
    this.wdMarketValue = null;
    this.wdPopGroupValue = null;
    this.loaderService.startLoader();
    this.subscription.push(
      this.formService.customActionCall<ViewWdMappingResponse>(STATIC_MODULES.custom.getWDList, { section: this.form.get('section').value }, null, this.url)
        .pipe(finalize(() => this.loaderService.stopLoader()))
        .subscribe(resp => {
          if (resp && resp.status === REQUEST_STATUS.SUCCESS) {
            this.wdCodeOptions = resp.data.wdCodeList;
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

  set wdMarketValue(value: string) {
    this.wdMarketOptions = [];
    this.form.get('wdMarket').setValue(value);
  }

  set wdPopGroupValue(value: string) {
    this.wdPopGroupOptions = [];
    this.form.get('wdPopGroup').setValue(value);
  }

  ngOnDestroy() {
    this.subscription.forEach(sub => sub.unsubscribe());
  }
}
