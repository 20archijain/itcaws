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
import { DashboardData, DropdownList, GetDownloadFileDetails, SalesDashboardData, VanDsListingData } from 'src/app/core/interfaces/http-response.interface';
import { LoaderService } from 'src/app/core/services/loader.service';
import { ToastrService } from 'src/app/core/services/toastr.service';
import { ConfirmationModalService } from 'src/app/core/services/confirmation-modal.service';
import { CanGoBackGuard } from 'src/app/core/guards/can-go-back-guard.service';

@Component({
  templateUrl: './app.notification.component.html',
  styleUrls: ["./app.notification.component.scss"],
})
export class AppNotification implements OnDestroy, OnInit {
  private subscription: Subscription[] = [];
  url = environment.viewSalesDashboardDataUrl;
  group: UntypedFormGroup;
  form: UntypedFormGroup;
  chartData;
  columnSize = 12;
  noOfMaps: string[] = [];
  categoryOptions: DropdownList[] = [];
  productOptions: DropdownList[] = [];
  districtOptions: DropdownList[] = [];
  branchOptions: DropdownList[] = [];
  monthOptions: DropdownList[] = [];
  yearOptions: DropdownList[] = [];
  circleOptions: DropdownList[] = [];
  sectionOptions: DropdownList[] = [];
  wdCodeOptions: DropdownList[] = [];
  teamOptions: DropdownList[] = [];
  teamTypeOptions: DropdownList[] = [];
  wdMarketOptions: DropdownList[] = [];
  wdPopGroupOptions: DropdownList[] = [];
  todaySaleDone: number;
  todaySaleAmount: number;
  tillDateSaleDone: number;
  tillDateSaleAmount: number;
  todayFocusSaleDone: number;
  todayFocusSaleAmount: number;
  tillDateFocusSaleDone: number;
  tillDateFocusSaleAmount: number;
  currentAndLastMonthData: any;
  currentMonthVsLastYearMonthSales: any;
  currentYearLastYearMonthlySales: any;
  monthlySalesData: any;
  chartsData: any;
  outletVisitedTableData: any;
  monthSaleLineChartData: any[] = [];
  monthAndYearLineChartData: any[] = [];
  currentAndLastYearLineChartData: any[] = [];
  monthFocusSaleLineChartData: any[] = [];
  monthAndYearFocusLineChartData: any[] = [];
  currentAndLastYearFocusLineChartData: any[] = [];
  isSidebarOpen = false;
  branchFilter = false
  showMapStyleDropdown = false;
  isDisabled = false;

  errorMessages = {
    notificationTitle: COMMON_VALIDATORS.messages.requiredOnly('Title'),
    notificationText: COMMON_VALIDATORS.messages.requiredOnly('Message'),
    branch: COMMON_VALIDATORS.messages.requiredOnly('Branch'),
    district: COMMON_VALIDATORS.messages.requiredOnly('District'),
  };

  constructor(private fb: UntypedFormBuilder, private formService: FormService, private loaderService: LoaderService,
    private toastr: ToastrService, private translate: TranslateService, private canGoBackGuard: CanGoBackGuard,
    private confirmationModalService: ConfirmationModalService) { }

  ngOnInit() {
    this.group = this.fb.group({
      district: [null, COMMON_VALIDATORS.validators.requiredOnly],
      branch: [null, COMMON_VALIDATORS.validators.requiredOnly],
      circle: [''],
      section: [''],
      wdCode: [''],
      dsType: [''],
      dsName: [''],
      wdMarket: [],
      wdPopGroup: [],
      notificationTitle: [null, COMMON_VALIDATORS.validators.requiredOnly],
      notificationText: [null, COMMON_VALIDATORS.validators.requiredOnly],
    });

    this.initialData();
    // subscribe to confirmation modal
    this.subscription.push(
      this.confirmationModalService.modal()
        .subscribe(resp => {
          if (!resp.goBackGuard && !resp.show) {
            // user confirms
            if (resp.data) {
              this.confirmSendNotification();
            }
          }
        })
    );
  }

  ngOnDestroy() {
    this.subscription.forEach(sub => sub.unsubscribe());
  }

  initialData() {
    this.loaderService.startLoader();
    this.subscription.push(
      this.formService.getData<SalesDashboardData>(this.url, this.group.getRawValue())
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
            this.branchFilter = resp.data.branchFilter;
            this.teamTypeOptions = resp.data.teamType;
            this.wdMarketOptions = resp.data.wdMarketList;
            this.wdPopGroupOptions = resp.data.wdPopGroupList;
          }
        })
    );
  }

  sendNotification() {
    if (this.group.valid && !this.isDisabled) {
      this.confirmationModalService.show('modal.confirmation.add');
    }
  }

  confirmSendNotification() {
    if (this.group.valid && !this.isDisabled) {
      this.loaderService.startLoader();
      this.isDisabled = true;
      this.subscription.push(
        this.formService.addData<string>(this.group, null, environment.addTeamUrl)
          .pipe(
            finalize(() => {
              this.loaderService.stopLoader();
              this.isDisabled = false;
            })
          )
          .subscribe(resp => {
            if (resp && resp.status === REQUEST_STATUS.SUCCESS) {
              // this.clearForm();
              this.reset();
              this.canGoBackGuard.markAsPristine();
            }
          })
      );
    }
  }

  reset() {
    this.form.reset();
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
      this.formService.customActionCall<DashboardData>(STATIC_MODULES.custom.getBranch, { district: this.group.get('district').value }, null, this.url)
        .pipe(finalize(() => this.loaderService.stopLoader()))
        .subscribe(resp => {
          if (resp && resp.status === REQUEST_STATUS.SUCCESS) {
            this.branchOptions = resp.data.branchList;
            this.circleOptions = resp.data.circleList;
            this.sectionOptions = resp.data.sectionList;
            this.wdCodeOptions = resp.data.wdCodeList;
            this.teamOptions = resp.data.teamList;
            this.teamTypeOptions = resp.data.teamType;
            this.categoryOptions = resp.data.categoryList;
            this.productOptions = resp.data.productList;
            this.wdMarketOptions = resp.data.wdMarketList;
            this.wdPopGroupOptions = resp.data.wdPopGroupList;
          }
        })
    );
  }

  getProduct() {
    this.loaderService.startLoader();
    this.subscription.push(
      this.formService.customActionCall<DashboardData>(STATIC_MODULES.custom.getproduct, { category: this.group.get('category').value }, null, this.url)
        .pipe(finalize(() => this.loaderService.stopLoader()))
        .subscribe(resp => {
          if (resp && resp.status === REQUEST_STATUS.SUCCESS) {
            this.productOptions = resp.data.productList;
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
      this.formService.customActionCall<DashboardData>(STATIC_MODULES.custom.getCircle, { branch: this.group.get('branch').value }, null, this.url)
        .pipe(finalize(() => this.loaderService.stopLoader()))
        .subscribe(resp => {
          if (resp && resp.status === REQUEST_STATUS.SUCCESS) {
            this.circleOptions = resp.data.circleList;
            this.sectionOptions = resp.data.sectionList;
            this.wdCodeOptions = resp.data.wdCodeList;
            this.teamOptions = resp.data.teamList;
            this.teamTypeOptions = resp.data.teamType;
            this.categoryOptions = resp.data.categoryList;
            this.productOptions = resp.data.productList;
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
      this.formService.customActionCall<DashboardData>(STATIC_MODULES.custom.getSection, { branch: this.group.get('branch').value, circle: this.group.get('circle').value }, null, this.url)
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
      this.formService.customActionCall<DashboardData>(STATIC_MODULES.custom.getWDList, { branch: this.group.get('branch').value, circle: this.group.get('circle').value, section: this.group.get('section').value }, null, this.url)
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
      this.formService.customActionCall<DashboardData>(STATIC_MODULES.custom.getTeamsTypeList, { branch: this.group.get('branch').value, circle: this.group.get('circle').value, section: this.group.get('section').value, wdCode: this.group.get('wdCode').value }, null, this.url)
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
      this.formService.customActionCall<DashboardData>(STATIC_MODULES.custom.getTeamsList, { branch: this.group.get('branch').value, circle: this.group.get('circle').value, section: this.group.get('section').value, wdCode: this.group.get('wdCode').value, dsType: this.group.get('dsType').value }, null, this.url)
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
  clearForm() {
    this.group.reset(
      { month: '' }
    );
    this.initialData();

  }

}

