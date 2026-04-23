import { Component, OnDestroy, OnInit } from '@angular/core';
import { UntypedFormBuilder, UntypedFormGroup } from '@angular/forms';
import { Subscription } from 'rxjs';
import { finalize } from 'rxjs/operators';

import { REQUEST_STATUS, STATIC_MODULES } from 'src/app/app.constants';
import { DashboardData, DropdownList, SalesDashboardData } from 'src/app/core/interfaces/http-response.interface';
import { FormService } from 'src/app/core/services/form.service';
import { LoaderService } from 'src/app/core/services/loader.service';
import { environment } from 'src/environments/environment';

@Component({
  templateUrl: './sales-dashboard.component.html',
  styleUrls: ['./sales-dashboard.component.scss'],
  standalone: false,
})
export class SalesDashboardComponent implements OnDestroy, OnInit {
  private subscription: Subscription[] = [];
  url = environment.viewSalesDashboardDataUrl;
  group!: UntypedFormGroup;
  // chartData;
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
  categoryOptions: DropdownList[] = [];
  productOptions: DropdownList[] = [];
  todaySaleDone: number | undefined;
  todaySaleAmount: number | undefined;
  tillDateSaleDone: number | undefined;
  tillDateSaleAmount: number | undefined;
  // todayFocusSaleDone: number | undefined;
  // todayFocusSaleAmount: number | undefined;
  // tillDateFocusSaleDone: number | undefined;
  // tillDateFocusSaleAmount: number | undefined;
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

  constructor(private fb: UntypedFormBuilder, private formService: FormService, private loaderService: LoaderService) { }

  ngOnInit() {
    this.group = this.fb.group({
      district: [''],
      branch: [''],
      circle: [''],
      section: [''],
      wdCode: [''],
      dsType: [''],
      dsName: [''],
      month: [''],
      month1: [''],
      month2: [''],
      month3: [''],
      year: [''],
      year1: [''],
      category: [''],
      product: [''],
      wdMarket: [],
      wdPopGroup: [],
    });

    this.initialData();
    this.getCardData();
    // this.getSalesData();
  }

  ngOnDestroy() {
    this.subscription.forEach(sub => sub.unsubscribe());
  }

  initialData() {
    if (this.group.valid) {
      this.loaderService.startLoader();

      this.subscription.push(
        this.formService.getData<SalesDashboardData>(this.url, this.group.getRawValue())
          .pipe(
            finalize(() => this.loaderService.stopLoader()),
          )
          .subscribe(resp => {
            if (resp && resp.status === REQUEST_STATUS.SUCCESS && resp.data) {
              this.monthOptions = resp.data.monthList;
              this.yearOptions = resp.data.yearList;
              this.branchOptions = resp.data.branchList;
              this.circleOptions = resp.data.circleList;
              this.sectionOptions = resp.data.sectionList;
              this.wdCodeOptions = resp.data.wdCodeList;
              this.teamOptions = resp.data.teamList;
              this.wdMarketOptions = resp.data.wdMarketList;
              this.wdPopGroupOptions = resp.data.wdPopGroupList;
              this.districtOptions = resp.data.districtList;
              this.branchFilter = resp.data.branchFilter ?? false;
              this.teamTypeOptions = resp.data.teamType;
            }
          })
      );
    }
  }

  getCardData() {
    // this.chartData = null;
    this.loaderService.startLoader();
    this.subscription.push(
      this.formService.getList<SalesDashboardData>(this.url, this.group.getRawValue())
        .pipe(
          finalize(() => this.loaderService.stopLoader()),
        )
        .subscribe(resp => {
          if (resp && resp.status === REQUEST_STATUS.SUCCESS && resp.data) {
            this.todaySaleDone = resp.data.todaySaleDone;
            this.todaySaleAmount = resp.data.todaySaleAmount;
            this.tillDateSaleDone = resp.data.tillDateSaleDone;
            this.tillDateSaleAmount = resp.data.tillDateSaleAmount;
            // this.todayFocusSaleDone = resp.data.todayFocusSaleDone;
            // this.todayFocusSaleAmount = resp.data.todayFocusSaleAmount;
            // this.tillDateFocusSaleDone = resp.data.tillDateFocusSaleDone;
            // this.tillDateFocusSaleAmount = resp.data.tillDateFocusSaleAmount;
            this.monthlySalesData = resp.data.monthlySalesData;
            // this.outletVisitedTableData = resp.data.outletVisitedTableData;
            // this.chartData = resp.data.chartsData;

            // Extract current and last month sales data
            const currentMonthSales = resp.data.currentAndLastMonthData || [];
            // const lastMonthSales = resp.data.currentAndLastMonthData.lastMonthSales || [];

            const saleData = currentMonthSales.seriesData; // Get seriesData
            const saleXAxisLabels = currentMonthSales.xAxisLabels || []; // Get XAxisLabels
            const lastMonthName = resp.data.currentAndLastMonthData.lastMonthName; // Get Last Month Name
            const currentMonthName = resp.data.currentAndLastMonthData.currentMonthName;
            // Prepare data for chart
            this.monthSaleLineChartData = this.prepareCumulativesaleData(saleData, saleXAxisLabels, lastMonthName, currentMonthName);

            // Extract current and last month Focus sales data
            const currentMonthFocusSales = resp.data.currentAndLastMonthFocusData || [];
            // const lastMonthSales = resp.data.currentAndLastMonthData.lastMonthSales || [];

            const focusSaleData = currentMonthFocusSales.seriesData; // Get seriesData
            const focusSaleXAxisLabels = currentMonthFocusSales.xAxisLabels || []; // Get XAxisLabels
            const focusLastMonthName = resp.data.currentAndLastMonthFocusData.focusLastMonthName; // Get Last Month Name
            const focusCurrentMonthName = resp.data.currentAndLastMonthFocusData.focusCurrentMonthName;
            // Prepare data for chart
            this.monthFocusSaleLineChartData = this.prepareCumulativesaleData(focusSaleData, focusSaleXAxisLabels, focusLastMonthName, focusCurrentMonthName);

            // Extract current year vs last year month sales data
            const currentMontAndLastYearMonthSales = resp.data.currentMonthVsLastYearMonthSales || [];

            const yearWisesaleData = currentMontAndLastYearMonthSales.seriesData; // Get seriesData
            const yearWisesaleXAxisLabels = currentMontAndLastYearMonthSales.xAxisLabels || []; // Get XAxisLabels
            const lastYearMonthName = resp.data.currentMonthVsLastYearMonthSales.lastYearMonthName; // Get Last Month Name
            const currentYearMonthName = resp.data.currentMonthVsLastYearMonthSales.currentYearMonthName;
            // Prepare data for chart
            this.monthAndYearLineChartData = this.preparemonthAndYearSaleData(yearWisesaleData, yearWisesaleXAxisLabels, lastYearMonthName, currentYearMonthName);

            // Extract current year and last year monthly sales data
            const currentYearAndLastYearSales = resp.data.currentYearLastYearMonthlySales || [];

            const currentAndLastyearWisesaleData = currentYearAndLastYearSales.seriesData; // Get seriesData
            const currentAndLastyearWisesaleXAxisLabels = currentYearAndLastYearSales.xAxisLabels || []; // Get XAxisLabels
            const lastYear = resp.data.currentYearLastYearMonthlySales.lastYear; // Get Last Month Name

            const currentYear = resp.data.currentYearLastYearMonthlySales.currentYear;
            // Prepare data for chart
            this.currentAndLastYearLineChartData = this.prepareCurrentYearAndLastYearSaleData(currentAndLastyearWisesaleData, currentAndLastyearWisesaleXAxisLabels, lastYear, currentYear);

            // Extract current year vs last year month focus sales data
            const currentMontAndLastYearMonthFocusSales = resp.data.currentMonthVsLastYearMonthFocusSales || [];

            const yearWisesaleFocusData = currentMontAndLastYearMonthFocusSales.seriesData; // Get seriesData
            const yearWiseFocussaleXAxisLabels = currentMontAndLastYearMonthFocusSales.xAxisLabels || []; // Get XAxisLabels
            const lastYearFocusMonthName = resp.data.currentMonthVsLastYearMonthFocusSales.lastYearFocusMonthName; // Get Last Month Name
            const currentYearFocusMonthName = resp.data.currentMonthVsLastYearMonthFocusSales.currentYearFocusMonthName;
            // Prepare data for chart
            this.monthAndYearFocusLineChartData = this.preparemonthAndYearSaleData(yearWisesaleFocusData, yearWiseFocussaleXAxisLabels, lastYearFocusMonthName, currentYearFocusMonthName);

            // Extract current year and last year monthly focus sales data
            const currentYearAndLastYearFocusSales = resp.data.currentYearLastYearMonthlyFocusSales || [];

            const currentAndLastyearWiseFocussaleData = currentYearAndLastYearFocusSales.seriesData; // Get seriesData
            const currentAndLastyearWiseFocussaleXAxisLabels = currentYearAndLastYearFocusSales.xAxisLabels || []; // Get XAxisLabels
            const focusLastYear = resp.data.currentYearLastYearMonthlyFocusSales.focusLastYear; // Get Last Month Name
            const focusCurrentYear = resp.data.currentYearLastYearMonthlyFocusSales.focusCurrentYear;
            // Prepare data for chart
            this.currentAndLastYearFocusLineChartData = this.prepareCurrentYearAndLastYearSaleData(currentAndLastyearWiseFocussaleData, currentAndLastyearWiseFocussaleXAxisLabels, focusLastYear, focusCurrentYear);
          }
        })
    );
  }

  getcurrentAndLastMonthSale() {
    this.loaderService.startLoader();
    this.subscription.push(
      this.formService.customActionCall<SalesDashboardData>(STATIC_MODULES.custom.getCmLmData, { month: this.group.get('month')?.value }, null, this.url)
        .pipe(
          finalize(() => this.loaderService.stopLoader()),
        )
        .subscribe(resp => {
          if (resp && resp.status === REQUEST_STATUS.SUCCESS && resp.data) {
            // Extract current and last month sales data
            const currentMonthSales = resp.data.currentAndLastMonthData || [];
            // const lastMonthSales = resp.data.currentAndLastMonthData.lastMonthSales || [];

            const saleData = currentMonthSales.seriesData; // Get seriesData
            const saleXAxisLabels = currentMonthSales.xAxisLabels || []; // Get XAxisLabels
            const lastMonthName = resp.data.currentAndLastMonthData.lastMonthName; // Get Last Month Name
            const currentMonthName = resp.data.currentAndLastMonthData.currentMonthName;
            // Prepare data for chart
            this.monthSaleLineChartData = this.prepareCumulativesaleData(saleData, saleXAxisLabels, lastMonthName, currentMonthName);
          }
        })
    );
  }

  getcurrentAndLastMonthFocusSale() {
    this.loaderService.startLoader();
    this.subscription.push(
      this.formService.customActionCall<SalesDashboardData>(STATIC_MODULES.custom.getCmLmFocusData, { month: this.group.get('month1')?.value }, null, this.url)
        .pipe(
          finalize(() => this.loaderService.stopLoader()),
        )
        .subscribe(resp => {
          if (resp && resp.status === REQUEST_STATUS.SUCCESS && resp.data) {
            // Extract current and last month sales data
            const currentMonthFocusSales = resp.data.currentAndLastMonthFocusData || [];
            // const lastMonthSales = resp.data.currentAndLastMonthData.lastMonthSales || [];

            const focusSaleData = currentMonthFocusSales.seriesData; // Get seriesData
            const focusSaleXAxisLabels = currentMonthFocusSales.xAxisLabels || []; // Get XAxisLabels
            const focusLastMonthName = resp.data.currentAndLastMonthFocusData.focusLastMonthName; // Get Last Month Name
            const focusCurrentMonthName = resp.data.currentAndLastMonthFocusData.focusCurrentMonthName;
            // Prepare data for chart
            this.monthFocusSaleLineChartData = this.prepareCumulativesaleData(focusSaleData, focusSaleXAxisLabels, focusLastMonthName, focusCurrentMonthName);
          }
        })
    );
  }

  getcurrentAndLastYearMonthSale() {
    this.loaderService.startLoader();
    this.subscription.push(
      this.formService.customActionCall<SalesDashboardData>(STATIC_MODULES.custom.getCmLymData, { month: this.group.get('month2')?.value }, null, this.url)
        .pipe(
          finalize(() => this.loaderService.stopLoader()),
        )
        .subscribe(resp => {
          if (resp && resp.status === REQUEST_STATUS.SUCCESS && resp.data) {
            // //OUTLET BILLED DATEWISE AMOUNT SALES COMPARISON
            const currentMontAndLastYearMonthSales = resp.data.currentMonthVsLastYearMonthSales || [];

            const yearWisesaleData = currentMontAndLastYearMonthSales.seriesData; // Get seriesData
            const yearWisesaleXAxisLabels = currentMontAndLastYearMonthSales.xAxisLabels || []; // Get XAxisLabels
            const lastYearMonthName = resp.data.currentMonthVsLastYearMonthSales.lastYearMonthName; // Get Last Month Name
            const currentYearMonthName = resp.data.currentMonthVsLastYearMonthSales.currentYearMonthName;
            // Prepare data for chart
            this.monthAndYearLineChartData = this.preparemonthAndYearSaleData(yearWisesaleData, yearWisesaleXAxisLabels, lastYearMonthName, currentYearMonthName);
          }
        })
    );
  }

  getcurrentAndLastYearMonthFocusSale() {
    this.loaderService.startLoader();
    this.subscription.push(
      this.formService.customActionCall<SalesDashboardData>(STATIC_MODULES.custom.getCmLymFocusData, { month: this.group.get('month3')?.value }, null, this.url)
        .pipe(
          finalize(() => this.loaderService.stopLoader()),
        )
        .subscribe(resp => {
          if (resp && resp.status === REQUEST_STATUS.SUCCESS && resp.data) {
            // //OUTLET BILLED DATEWISE AMOUNT SALES COMPARISON
            const currentMontAndLastYearMonthFocusSales = resp.data.currentMonthVsLastYearMonthFocusSales || [];

            const yearWisesaleFocusData = currentMontAndLastYearMonthFocusSales.seriesData; // Get seriesData
            const yearWiseFocussaleXAxisLabels = currentMontAndLastYearMonthFocusSales.xAxisLabels || []; // Get XAxisLabels
            const lastYearFocusMonthName = resp.data.currentMonthVsLastYearMonthFocusSales.lastYearFocusMonthName; // Get Last Month Name
            const currentYearFocusMonthName = resp.data.currentMonthVsLastYearMonthFocusSales.currentYearFocusMonthName;
            // Prepare data for chart
            this.monthAndYearFocusLineChartData = this.preparemonthAndYearSaleData(yearWisesaleFocusData, yearWiseFocussaleXAxisLabels, lastYearFocusMonthName, currentYearFocusMonthName);
          }
        })
    );
  }

  getCurrentYearLastYearMonthlySales() {
    this.loaderService.startLoader();
    this.subscription.push(
      this.formService.customActionCall<SalesDashboardData>(STATIC_MODULES.custom.getCyLyData, { year: this.group.get('year')?.value }, null, this.url)
        .pipe(
          finalize(() => this.loaderService.stopLoader()),
        )
        .subscribe(resp => {
          if (resp && resp.status === REQUEST_STATUS.SUCCESS && resp.data) {
            // //OUTLET BILLED DATEWISE AMOUNT SALES COMPARISON
            const currentYearAndLastYearSales = resp.data.currentYearLastYearMonthlySales || [];

            const currentAndLastyearWisesaleData = currentYearAndLastYearSales.seriesData; // Get seriesData
            const currentAndLastyearWisesaleXAxisLabels = currentYearAndLastYearSales.xAxisLabels || []; // Get XAxisLabels
            const lastYear = resp.data.currentYearLastYearMonthlySales.lastYear; // Get Last Month Name
            const currentYear = resp.data.currentYearLastYearMonthlySales.currentYear;
            // Prepare data for chart
            this.currentAndLastYearLineChartData = this.prepareCurrentYearAndLastYearSaleData(currentAndLastyearWisesaleData, currentAndLastyearWisesaleXAxisLabels, lastYear, currentYear);
          }
        })
    );
  }

  getCurrentYearLastYearMonthlyFocusSales() {
    this.loaderService.startLoader();
    this.subscription.push(
      this.formService.customActionCall<SalesDashboardData>(STATIC_MODULES.custom.getCyLyFocusData, { year: this.group.get('year1')?.value }, null, this.url)
        .pipe(
          finalize(() => this.loaderService.stopLoader()),
        )
        .subscribe(resp => {
          if (resp && resp.status === REQUEST_STATUS.SUCCESS && resp.data) {
            // //OUTLET BILLED DATEWISE AMOUNT SALES COMPARISON
            const currentYearAndLastYearFocusSales = resp.data.currentYearLastYearMonthlyFocusSales || [];

            const currentAndLastyearWiseFocussaleData = currentYearAndLastYearFocusSales.seriesData; // Get seriesData
            const currentAndLastyearWiseFocussaleXAxisLabels = currentYearAndLastYearFocusSales.xAxisLabels || []; // Get XAxisLabels
            const focusLastYear = resp.data.currentYearLastYearMonthlyFocusSales.focusLastYear;
            const focusCurrentYear = resp.data.currentYearLastYearMonthlyFocusSales.focusCurrentYear;
            // Prepare data for chart
            this.currentAndLastYearFocusLineChartData = this.prepareCurrentYearAndLastYearSaleData(currentAndLastyearWiseFocussaleData, currentAndLastyearWiseFocussaleXAxisLabels, focusLastYear, focusCurrentYear);
          }
        })
    );
  }

  prepareCumulativesaleData(salesQtyData: any, XAxisLabels: any[], lastMonthName: string, currentMonthName: string): any[] {
    const lastMonthData = salesQtyData[0]?.data || [];
    const thisMonthData = salesQtyData[1]?.data || [];
    const currentMonthSeries = thisMonthData.map((value: number, index: number) => ({
      name: XAxisLabels[index]?.toString() || '',
      value: value,
    }));
    const lastMonthSeries = lastMonthData.map((value: number, index: number) => ({
      name: XAxisLabels[index]?.toString() || '',
      value: value,
    }));

    return [
      { name: lastMonthName, series: lastMonthSeries },
      { name: currentMonthName, series: currentMonthSeries },
    ];
  }
  preparemonthAndYearSaleData(salesQtyData: any, XAxisLabels: any[], lastYearMonthName: string, currentYearMonthName: string): any[] {
    const lastMonthData = salesQtyData[0]?.data || [];
    const thisMonthData = salesQtyData[1]?.data || [];
    const currentMonthSeries = thisMonthData.map((value: number, index: number) => ({
      name: XAxisLabels[index]?.toString() || '',
      value: value,
    }));
    const lastMonthSeries = lastMonthData.map((value: number, index: number) => ({
      name: XAxisLabels[index]?.toString() || '',
      value: value,
    }));

    return [
      { name: lastYearMonthName, series: lastMonthSeries },
      { name: currentYearMonthName, series: currentMonthSeries },
    ];
  }
  prepareCurrentYearAndLastYearSaleData(salesQtyData: any, XAxisLabels: any[], lastYear: string, currentYear: string): any[] {
    const lastYearData = salesQtyData[0]?.data || [];
    const thisYearData = salesQtyData[1]?.data || [];
    const currentYearSeries = thisYearData.map((value: number, index: number) => ({
      name: XAxisLabels[index]?.toString() || '',
      value: value,
    }));
    const lastYearSeries = lastYearData.map((value: number, index: number) => ({
      name: XAxisLabels[index]?.toString() || '',
      value: value,
    }));

    return [
      { name: lastYear, series: lastYearSeries },
      { name: currentYear, series: currentYearSeries },
    ];
  }

  getBranch() {
    this.categoryValue = null;
    this.productValue = null;
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
      this.formService.customActionCall<DashboardData>(STATIC_MODULES.custom.getBranch, { district: this.group.get('district')?.value }, null, this.url)
        .pipe(finalize(() => this.loaderService.stopLoader()))
        .subscribe(resp => {
          if (resp && resp.status === REQUEST_STATUS.SUCCESS && resp.data) {
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
      this.formService.customActionCall<DashboardData>(STATIC_MODULES.custom.getproduct, { category: this.group.get('category')?.value }, null, this.url)
        .pipe(finalize(() => this.loaderService.stopLoader()))
        .subscribe(resp => {
          if (resp && resp.status === REQUEST_STATUS.SUCCESS && resp.data) {
            this.productOptions = resp.data.productList;
          }
        })
    );
  }

  getCircle() {
    this.categoryValue = null;
    this.productValue = null;
    this.circleValue = null;
    this.sectionValue = null;
    this.wdCodeValue = null;
    this.dsTypeValue = null;
    this.dsNameValue = null;
    this.wdMarketValue = null;
    this.wdPopGroupValue = null;
    this.loaderService.startLoader();
    this.subscription.push(
      this.formService.customActionCall<DashboardData>(STATIC_MODULES.custom.getCircle, { branch: this.group.get('branch')?.value }, null, this.url)
        .pipe(finalize(() => this.loaderService.stopLoader()))
        .subscribe(resp => {
          if (resp && resp.status === REQUEST_STATUS.SUCCESS && resp.data) {
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
      this.formService.customActionCall<DashboardData>(STATIC_MODULES.custom.getSection, { branch: this.group.get('branch')?.value, circle: this.group.get('circle')?.value }, null, this.url)
        .pipe(finalize(() => this.loaderService.stopLoader()))
        .subscribe(resp => {
          if (resp && resp.status === REQUEST_STATUS.SUCCESS && resp.data) {
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
      this.formService.customActionCall<DashboardData>(STATIC_MODULES.custom.getWDList, { branch: this.group.get('branch')?.value, circle: this.group.get('circle')?.value, section: this.group.get('section')?.value }, null, this.url)
        .pipe(finalize(() => this.loaderService.stopLoader()))
        .subscribe(resp => {
          if (resp && resp.status === REQUEST_STATUS.SUCCESS && resp.data) {
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
      this.formService.customActionCall<DashboardData>(STATIC_MODULES.custom.getTeamsTypeList, { branch: this.group.get('branch')?.value, circle: this.group.get('circle')?.value, section: this.group.get('section')?.value, wdCode: this.group.get('wdCode')?.value }, null, this.url)
        .pipe(finalize(() => this.loaderService.stopLoader()))
        .subscribe(resp => {
          if (resp && resp.status === REQUEST_STATUS.SUCCESS && resp.data) {
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
      this.formService.customActionCall<DashboardData>(STATIC_MODULES.custom.getTeamsList, { branch: this.group.get('branch')?.value, circle: this.group.get('circle')?.value, section: this.group.get('section')?.value, wdCode: this.group.get('wdCode')?.value, dsType: this.group.get('dsType')?.value }, null, this.url)
        .pipe(finalize(() => this.loaderService.stopLoader()))
        .subscribe(resp => {
          if (resp && resp.status === REQUEST_STATUS.SUCCESS && resp.data) {
            this.teamOptions = resp.data.teamList;
          }
        })
    );
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
  set wdMarketValue(value: string | null) {
    this.wdMarketOptions = [];
    this.group.get('wdMarket')?.setValue(value);
  }

  set wdPopGroupValue(value: string | null) {
    this.wdPopGroupOptions = [];
    this.group.get('wdPopGroup')?.setValue(value);
  }
  set productValue(value: string | null) {
    this.productOptions = [];
    this.group.get('product')?.setValue(value);
  }
  set categoryValue(value: string | null) {
    this.categoryOptions = [];
    this.group.get('category')?.setValue(value);
  }
  set branchValue(value: string | null) {
    this.branchOptions = [];
    this.group.get('branch')?.setValue(value);
  }
  clearForm() {
    this.group.reset();
    this.initialData();
    this.monthlySalesData = [];
    this.getCardData();
    this.categoryOptions = [];
    this.productOptions = [];
  }
  toggleSidebar() {
    this.isSidebarOpen = !this.isSidebarOpen;
  }

}
