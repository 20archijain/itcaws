import { Component, OnDestroy, OnInit } from '@angular/core';
import { Subscription } from 'rxjs';
import { UntypedFormBuilder, UntypedFormGroup } from '@angular/forms';
import { finalize } from 'rxjs/operators';
import { TranslateService } from '@ngx-translate/core';

import { FormService } from 'src/app/core/services/form.service';
import { environment } from 'src/environments/environment';
import { LISTING, REQUEST_STATUS, STATIC_MODULES } from 'src/app/app.constants';
import { Functions } from 'src/app/core/utils/functions.list';
import { COMMON_VALIDATORS } from 'src/app/core/validators/validations.list';
import { DashboardData, DropdownList, GetDownloadFileDetails, VanDsListingData } from 'src/app/core/interfaces/http-response.interface';
import { LoaderService } from 'src/app/core/services/loader.service';
import { ToastrService } from 'src/app/core/services/toastr.service';

@Component({
    templateUrl: './evaluation-report.component.html',
    styleUrls: ["./evaluation-report.component.scss"],
    standalone: false
})
export class EvaluationReportComponent implements OnDestroy, OnInit {
  private subscription: Subscription[] = [];
  group: UntypedFormGroup;
  branchOptions: DropdownList[] = [];
  teamOptions: DropdownList[] = [];
  isBranchRequired = true;
  isTeamRequired = false;
  branchFilter = false;
  teamTypeOptions: DropdownList[] = [];
  rmdNameOptions: DropdownList[] = [];
  circleOptions: DropdownList[] = [];
  sectionOptions: DropdownList[] = [];
  wdCodeOptions: DropdownList[] = [];
  districtOptions: DropdownList[] = [];
  wdMarketOptions: DropdownList[] = [];
  wdPopGroupOptions: DropdownList[] = [];
  branchSelectError = 'err.branchError';
  errorMessages = {
    branch: COMMON_VALIDATORS.messages.requiredOnly('Branch'),
    dateFrom: COMMON_VALIDATORS.messages.requiredOnly('From'),
    dateTo: COMMON_VALIDATORS.messages.requiredOnly('To'),
  };
  isDisabled = false;
  url = environment.getBinderReportDataUrl;
  searchbarForm: UntypedFormGroup;

  constructor(private fb: UntypedFormBuilder, private formService: FormService, private loaderService: LoaderService,
    private toastr: ToastrService, private translate: TranslateService) { }


  ngOnInit() {
    this.group = this.fb.group({
      action: [''],
      limit: [''],
      page: [1],
      searchbar: this.fb.group({
        dateFrom: [''],
        dateTo: [''],
        branch: ['', COMMON_VALIDATORS.validators.requiredOnly],
        circle: [''],
        section: [''],
        wdCode: [''],
        dsType: [''],
        dsName: [''],
        district: [''],
        wdMarket: [''],
        wdPopGroup: [''],
        billed: ['', COMMON_VALIDATORS.validators.zeroAndFloatQtyStock],
      }),
      sort: [''],
    });

    this.searchbarForm = this.group.get('searchbar') as UntypedFormGroup;

    this.subscription.push(
      this.translate.get(this.branchSelectError)
        .subscribe(translatedMsg => {
          this.branchSelectError = translatedMsg;
        })
    );

    this.loaderService.startLoader();
    this.subscription.push(
      this.formService.getData<VanDsListingData>(environment.getEvaluationReportDataUrl)
        .pipe(
          finalize(() => this.loaderService.stopLoader()),
        )
        .subscribe(resp => {
          if (resp && resp.status === REQUEST_STATUS.SUCCESS) {
            this.districtOptions = resp.data.districtList;
            this.branchOptions = resp.data.branchList;
            this.circleOptions = resp.data.circleList;
            this.sectionOptions = resp.data.sectionList;
            this.wdCodeOptions = resp.data.wdCodeList;
            this.teamOptions = resp.data.teamList;
            this.teamTypeOptions = resp.data.teamType;
            this.wdMarketOptions = resp.data.wdMarketList;
            this.wdPopGroupOptions = resp.data.wdPopGroupList;
            this.branchFilter = resp.data.branchFilter;
            if (resp.data.userBranch) {
              this.group.get('searchbar').get('branch').setValue(resp.data.userBranch);
            }
          }
        })
    );

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
      this.formService.customActionCall<DashboardData>(STATIC_MODULES.custom.getBranch, { district: this.group.get('searchbar').get('district').value }, null, environment.viewVanDsDataUrl)
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
      this.formService.customActionCall<DashboardData>(STATIC_MODULES.custom.getCircle, { branch: this.group.get('searchbar').get('branch').value }, null, environment.viewVanDsDataUrl)
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
      this.formService.customActionCall<DashboardData>(STATIC_MODULES.custom.getSection, { branch: this.group.get('searchbar').get('branch').value, circle: this.group.get('searchbar').get('circle').value }, null, environment.viewVanDsDataUrl)
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
      this.formService.customActionCall<DashboardData>(STATIC_MODULES.custom.getWDList, { branch: this.group.get('searchbar').get('branch').value, circle: this.group.get('searchbar').get('circle').value, section: this.group.get('searchbar').get('section').value }, null, environment.viewVanDsDataUrl)
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
      this.formService.customActionCall<DashboardData>(STATIC_MODULES.custom.getTeamsTypeList, { branch: this.group.get('searchbar').get('branch').value, circle: this.group.get('searchbar').get('circle').value, section: this.group.get('searchbar').get('section').value, wdCode: this.group.get('searchbar').get('wdCode').value }, null, environment.viewVanDsDataUrl)
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
      this.formService.customActionCall<DashboardData>(STATIC_MODULES.custom.getTeamsList, { branch: this.group.get('searchbar').get('branch').value, circle: this.group.get('searchbar').get('circle').value, section: this.group.get('searchbar').get('section').value, wdCode: this.group.get('searchbar').get('wdCode').value, dsType: this.group.get('searchbar').get('dsType').value }, null, environment.viewVanDsDataUrl)
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
    // const teamTypeValue = this.group.get('searchbar').get('dsType').value;
    // if (this.branchValue && this.branchValue.length && teamTypeValue) {
    if (!this.isDisabled && this.group.valid) {
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
    // } else {
    //   this.displayBranchError();
    // }
  }


  displayBranchError() {
    this.toastr.toastr({ type: 'error', msg: 'Please select Branch and Team Type' });
  }

  get branchValue() {
    return this.group && this.group.get('searchbar').get('branch').value;
  }

  // get teamTypeValue() {
  //   return this.group && this.group.get('searchbar').get('dsType').value;
  // }

  set branchValue(value: string) {
    this.branchOptions = [];
    this.group.get('searchbar').get('branch').setValue(value);
  }
  set circleValue(value: string) {
    this.circleOptions = [];
    this.group.get('searchbar').get('circle').setValue(value);
  }
  set sectionValue(value: string) {
    this.sectionOptions = [];
    this.group.get('searchbar').get('section').setValue(value);
  }
  set wdCodeValue(value: string) {
    this.wdCodeOptions = [];
    this.group.get('searchbar').get('wdCode').setValue(value);
  }
  set dsTypeValue(value: string) {
    this.teamTypeOptions = [];
    this.group.get('searchbar').get('dsType').setValue(value);
  }
  set dsNameValue(value: string) {
    this.teamOptions = [];
    this.group.get('searchbar').get('dsName').setValue(value);
  }
  set wdMarketValue(value: string) {
    this.wdMarketOptions = [];
    this.group.get('searchbar').get('wdMarket').setValue(value);
  }
  set wdPopGroupValue(value: string) {
    this.wdPopGroupOptions = [];
    this.group.get('searchbar').get('wdPopGroup').setValue(value);
  }

  get limit() {
    return this.group.get('limit').value || LISTING.display[0];
  }
  clearForm() {
    this.group.reset();
  }

}

