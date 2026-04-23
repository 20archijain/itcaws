import { Component, OnDestroy, OnInit } from '@angular/core';
import { UntypedFormBuilder, UntypedFormGroup } from '@angular/forms';
import { Subscription } from 'rxjs';
import { finalize } from 'rxjs/operators';

import { REQUEST_STATUS, STATIC_MODULES } from 'src/app/app.constants';
import { DashboardData, DropdownList } from 'src/app/core/interfaces/http-response.interface';
import { FormService } from 'src/app/core/services/form.service';
import { LoaderService } from 'src/app/core/services/loader.service';
import { COMMON_VALIDATORS } from 'src/app/core/validators/validations.list';
import { environment } from 'src/environments/environment';

@Component({
  templateUrl: './dashboard.component.html',
  styleUrls: ["./dashboard.component.scss"],
  standalone: false,
})
export class DashboardComponent implements OnDestroy, OnInit {
  activeIndex = 0;
  intervalId: any;
  private subscription: Subscription[] = [];
  group!: UntypedFormGroup;
  form!: UntypedFormGroup;
  url = environment.viewDashboardDataUrl;
  formData: any;
  dashboardData: any;
  outletCoverageData: any;
  chartData: any[] = [];
  lineChartData: any[] = [];
  branchWiseSaleGraph: any[] = [];
  visitedLineChartData: any[] = [];
  visitedSPLYLineChartData: any[] = [];
  ytdlytdVisitDataBarChartData: any[] = [];
  billedLineChartData: any[] = [];
  billedSPLYLineChartData: any[] = [];
  billedYTDLYTDLineChartData: any[] = [];
  billedCMLMFocusLineChartData: any[] = [];
  billedSPLFocusLineChartData: any[] = [];
  datewiseMonthSaleComparison: any[] = [];
  branchWisesChartData: any[] = [];
  pieChartData: any[] = [];
  AttCardData: any;
  isSidebarOpen = false;
  QualifiedAttData: any;
  slideCardData: any;
  OutletVisitedCardData: any;
  TodayOutletVisitedCardData: any;
  BeatAdherenceCardData: any;
  FocusVisitTodayAmountCardData: any;
  FocusVisitTillDateAmountCardData: any;
  TodaySalesAmountCardData: any;
  SalesAmountComparisonData: any;
  getShopBilledComparisonData: any;
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
  branchFilter = false
  graph1: any;
  graph2: any;
  graph3: any;
  graph4: any;
  graph5: any;
  graph6: any;
  graph7: any;
  graph8: any;
  activeTab = 'static-0';
  currentTab = 'Today';
  isTabOpen = true;
  errorMessages = {
    dateFrom: COMMON_VALIDATORS.messages.requiredOnly('From'),
    dateTo: COMMON_VALIDATORS.messages.requiredOnly('To'),
  };
  isDataLoaded = false;
  data: any;
  animateSlide = true;
  searchbarForm!: UntypedFormGroup;

  constructor(private fb: UntypedFormBuilder, private formService: FormService, private loaderService: LoaderService) { }

  ngOnInit() {
    this.group = this.fb.group({
      searchbar: this.fb.group({
        district: [''],
        branch: [''],
        circle: [''],
        section: [''],
        wdCode: [''],
        dsType: [],
        dsName: [''],
        dateFrom: [''],
        dateTo: [''],
        month: [''],
        month1: [''],
        month2: [''],
        month3: [''],
        month4: [''],
        month5: [''],
        year: [''],
        year1: [''],
        category: [''],
        product: [''],
        wdMarket: [],
        wdPopGroup: [],
      })
    });

    this.searchbarForm = this.group.get('searchbar') as UntypedFormGroup;

    this.getInitialData();
    this.getDashboardData();
    this.resumeAutoSlide();
  }

  ngOnDestroy() {
    this.subscription.forEach(sub => sub.unsubscribe());
  }

  autoSlide: any;

  pauseAutoSlide() {
    clearInterval(this.autoSlide);
  }

  resumeAutoSlide() {
    this.autoSlide = setInterval(() => this.nextSlide(), 3000);
  }

  nextSlide() {
    this.animateSlide = true;
    this.activeIndex = (this.activeIndex + 1) % this.slideCardData?.length;
  }

  onAnimationEnd() {
    this.animateSlide = false; // Reset animation state
  }

  getInitialData() {
    this.loaderService.startLoader();
    this.subscription.push(
      this.formService.getData<DashboardData>(this.url)
        .pipe(finalize(() => this.loaderService.stopLoader()))
        .subscribe(resp => {
          if (resp && resp.status === REQUEST_STATUS.SUCCESS && resp.data) {
            this.monthOptions = resp.data.monthList;
            this.yearOptions = resp.data.yearList;
            this.branchOptions = resp.data.branchList;
            this.circleOptions = resp.data.circleList;
            this.sectionOptions = resp.data.sectionList;
            this.wdCodeOptions = resp.data.wdCodeList;
            this.teamTypeOptions = resp.data.teamType;
            this.teamOptions = resp.data.teamList;
            this.wdMarketOptions = resp.data.wdMarketList;
            this.wdPopGroupOptions = resp.data.wdPopGroupList;
            this.districtOptions = resp.data.districtList;
            this.AttCardData = resp.data.attCardData;
            this.QualifiedAttData = resp.data.qualifiedAttData;
            this.OutletVisitedCardData = resp.data.outletVisitedCardData;
            this.BeatAdherenceCardData = resp.data.beatAdherenceCardData;
            this.FocusVisitTillDateAmountCardData = resp.data.focusVisitTillDateAmountCardData;
            this.slideCardData = resp.data.slideCardData;
            this.branchFilter = resp.data.branchFilter ?? false;
          }
        })
    );
  }

  getDashboardData() {
    this.loaderService.startLoader();
    this.subscription.push(
      this.formService.customActionCall<DashboardData>(STATIC_MODULES.custom.dashboardData, this.group.getRawValue(), null, this.url)
        .pipe(finalize(() => this.loaderService.stopLoader()))
        .subscribe(resp => {
          if (resp && resp.status === REQUEST_STATUS.SUCCESS) {
            this.dashboardData = resp.data;
            this.formData = null;
            this.isDataLoaded = true;
            this.AttCardData = this.dashboardData.attCardData;
            this.QualifiedAttData = this.dashboardData.qualifiedAttData;
            this.OutletVisitedCardData = this.dashboardData.outletVisitedCardData;
            this.BeatAdherenceCardData = this.dashboardData.beatAdherenceCardData;
            this.slideCardData = this.dashboardData.slideCardData
            this.FocusVisitTillDateAmountCardData = this.dashboardData.focusVisitTillDateAmountCardData;

            //OUTLET VISITED MONTHWISE YTDLYTD COMPARISON
            const ytdlytdVisit = this.dashboardData.graphs.getShopVisitedYTDLYTDComparisonDataMonthly
            if (!ytdlytdVisit || !ytdlytdVisit.ytdlytdVisit || !ytdlytdVisit.ytdlytdVisit.seriesData) {
              return;
            }
            const ytdlytdVisitData = ytdlytdVisit.ytdlytdVisit.seriesData; // Get seriesData
            const ytdlytdVisitXAxisLabels = ytdlytdVisit.ytdlytdVisit.xAxisLabels || []; // Get XAxisLabels
            const lastYear = ytdlytdVisit.ytdlytdVisit.lastYear;
            const currentYear = ytdlytdVisit.ytdlytdVisit.currentYear;
            this.ytdlytdVisitDataBarChartData = this.prepareCumulativeYTDLYTDOutletVisitedData(ytdlytdVisitData, ytdlytdVisitXAxisLabels, lastYear, currentYear);

            //OUTLET VISITED DATEWISE SPLY COMPARISON
            const splyVisit = this.dashboardData.graphs.getShopVisitedSPLYComparisonData
            if (!splyVisit || !splyVisit.visitData || !splyVisit.visitData.seriesData) {
              return;
            }
            const SPLYData = splyVisit.visitData.seriesData; // Get seriesData
            const SPLYDataXAxisLabels = splyVisit.visitData.xAxisLabels || []; // Get XAxisLabels
            const lastYearMonthName = splyVisit.visitData.lastYearMonthName;
            const currentYearMonthName = splyVisit.visitData.currentYearMonthName;
            this.visitedSPLYLineChartData = this.prepareCumulativeSPLYOutletVisitedData(SPLYData, SPLYDataXAxisLabels, lastYearMonthName, currentYearMonthName);

            //OUTLET VISITED DATEWISE  COMPARISON
            const amount = this.dashboardData.graphs.outletVisitedMonthlyComparisonData
            if (!amount || !amount.salesData || !amount.salesData.seriesData) {
              return;
            }
            const visitedData = amount.salesData.seriesData;
            const XAxisLabels = amount.salesData.xAxisLabels || [];
            const lastMonthName = amount.salesData.lastMonthName;
            const currentMonthName = amount.salesData.currentMonthName;

            this.visitedLineChartData = this.prepareCumulativeOutletVisitedData(visitedData, XAxisLabels, lastMonthName, currentMonthName);

            //OUTLET BILLED DATEWISE  COMPARISON
            const billed = this.dashboardData.graphs.getOutletBilledComparisonData
            if (!billed || !billed.billedData || !billed.billedData.seriesData) {
              return;
            }
            const billedData = billed.billedData.seriesData;
            const billedXAxisLabels = billed.billedData.xAxisLabels || [];
            const lastrMonth = billed.billedData.lastMonthName;
            const currentMonth = billed.billedData.currentMonthName;
            this.billedLineChartData = this.prepareCumulativeOutletBilledData(billedData, billedXAxisLabels, lastrMonth, currentMonth);

            //OUTLET BILLED SPLY DATEWISE  COMPARISON
            const SPLYbilled = this.dashboardData.graphs.getShopBilledSPLYComparisonData
            if (!SPLYbilled || !SPLYbilled.billedSPLYData || !SPLYbilled.billedSPLYData.seriesData) {
              return;
            }
            const billedSPLYData = SPLYbilled.billedSPLYData.seriesData;
            const billedSPLYDataXAxisLabels = SPLYbilled.billedSPLYData.xAxisLabels || [];
            const lastYearMonth = SPLYbilled.billedSPLYData.lastYearMonthName;
            const currenYeartMonth = SPLYbilled.billedSPLYData.currentYearMonthName;
            this.billedSPLYLineChartData = this.prepareCumulativeSPLYOutletBilledData(billedSPLYData, billedSPLYDataXAxisLabels, lastYearMonth, currenYeartMonth);

            //OUTLET BILLED YTD/LYTD DATEWISE  COMPARISON
            const TYDLYTDbilled = this.dashboardData.graphs.getShopBilledYTDLYTDComparisonData
            if (!TYDLYTDbilled || !TYDLYTDbilled.ytdlytdBilled || !TYDLYTDbilled.ytdlytdBilled.seriesData) {
              return;
            }
            const TYDLYTDbilledData = TYDLYTDbilled.ytdlytdBilled.seriesData;
            const TYDLYTDbilledDataXAxisLabels = TYDLYTDbilled.ytdlytdBilled.xAxisLabels || [];
            const lastBilledYear = TYDLYTDbilled.ytdlytdBilled.lastYear;
            const currentBilledYear = TYDLYTDbilled.ytdlytdBilled.currentYear;
            this.billedYTDLYTDLineChartData = this.prepareCumulativeYTDLYTDOutletBilledData(TYDLYTDbilledData, TYDLYTDbilledDataXAxisLabels, lastBilledYear, currentBilledYear);

            //FOCUS OUTLET BILLED CM/LM DATEWISE  COMPARISON
            const CMLMFocusBilled = this.dashboardData.graphs.getFocusCMLMOutletBilledComparisonData
            if (!CMLMFocusBilled || !CMLMFocusBilled.focusbilledData || !CMLMFocusBilled.focusbilledData.seriesData) {
              return;
            }
            const CMLMFocusBilledData = CMLMFocusBilled.focusbilledData.seriesData;
            const CMLMFocusBilledDataXAxisLabels = CMLMFocusBilled.focusbilledData.xAxisLabels || [];
            const lastFocusMonth = CMLMFocusBilled.focusbilledData.lastMonthName;
            const currentFocusMonth = CMLMFocusBilled.focusbilledData.currentMonthName;
            this.billedCMLMFocusLineChartData = this.prepareCumulativeCMLMFocusOutletBilledData(CMLMFocusBilledData, CMLMFocusBilledDataXAxisLabels, lastFocusMonth, currentFocusMonth);

            //FOCUS OUTLET BILLED SPLY DATEWISE  COMPARISON
            const SPLYBilled = this.dashboardData.graphs.getFocusSPLYOutletBilledComparisonData
            if (!SPLYBilled || !SPLYBilled.focusSPLYbilledData || !SPLYBilled.focusSPLYbilledData.seriesData) {
              return;
            }
            const SPLYFocusBilledData = SPLYBilled.focusSPLYbilledData.seriesData;
            const SPLYFocusBilledDataXAxisLabels = SPLYBilled.focusSPLYbilledData.xAxisLabels || [];
            const lastYearFocusMonth = SPLYBilled.focusSPLYbilledData.lastYearMonthName;
            const currentYearFocusMonth = SPLYBilled.focusSPLYbilledData.currentYearMonthName;
            this.billedSPLFocusLineChartData = this.prepareCumulativeSPLYocusOutletBilledData(SPLYFocusBilledData, SPLYFocusBilledDataXAxisLabels, lastYearFocusMonth, currentYearFocusMonth);

            //FOCUS OUTLET BILLED SPLY DATEWISE  COMPARISON
            // const YTDLYTDBilled = this.dashboardData.graphs.getFocusSPLYOutletBilledComparisonData
            // if (!SPLYBilled || !SPLYBilled.focusSPLYbilledData || !SPLYBilled.focusSPLYbilledData.seriesData) {
            //   return;
            // }
            // const SPLYFocusBilledData = SPLYBilled.focusSPLYbilledData.seriesData;
            // const SPLYFocusBilledDataXAxisLabels = SPLYBilled.focusSPLYbilledData.xAxisLabels || [];
            // this.billedSPLFocusLineChartData = this.prepareCumulativeSPLYocusOutletBilledData(SPLYFocusBilledData, SPLYFocusBilledDataXAxisLabels);
          }
        })
    );
  }

  getShopVisitedYTDLYTDComparisonDataMonthly() {
    this.loaderService.startLoader();
    this.subscription.push(
      this.formService.customActionCall<DashboardData>(STATIC_MODULES.custom.getGraph1, { year: this.group.get('searchbar')?.get('year')?.value }, null, this.url)
        .pipe(
          finalize(() => this.loaderService.stopLoader()),
        )
        .subscribe(resp => {
          if (resp && resp.status === REQUEST_STATUS.SUCCESS) {
            this.graph1 = resp.data;
            const ytdlytdVisit = this.graph1.graphs.getShopVisitedYTDLYTDComparisonDataMonthly
            if (!ytdlytdVisit || !ytdlytdVisit.ytdlytdVisit || !ytdlytdVisit.ytdlytdVisit.seriesData) {
              return;
            }
            const ytdlytdVisitData = ytdlytdVisit.ytdlytdVisit.seriesData; // Get seriesData
            const ytdlytdVisitXAxisLabels = ytdlytdVisit.ytdlytdVisit.xAxisLabels || []; // Get XAxisLabels
            const lastYear = ytdlytdVisit.ytdlytdVisit.lastYear;
            const currentYear = ytdlytdVisit.ytdlytdVisit.currentYear;
            this.ytdlytdVisitDataBarChartData = [...this.prepareCumulativeYTDLYTDOutletVisitedData(ytdlytdVisitData, ytdlytdVisitXAxisLabels, lastYear, currentYear)];
          }
        })
    );
  }

  outletVisitedMonthlyComparisonData() {
    this.visitedLineChartData = [];
    this.loaderService.startLoader();
    this.subscription.push(
      this.formService.customActionCall<DashboardData>(STATIC_MODULES.custom.getGraph2, { month: this.group.get('searchbar')?.get('month')?.value }, null, this.url)
        .pipe(
          finalize(() => this.loaderService.stopLoader()),
        )
        .subscribe(resp => {
          if (resp && resp.status === REQUEST_STATUS.SUCCESS) {
            this.graph2 = resp.data;
            const amount = this.graph2.graphs.outletVisitedMonthlyComparisonData;
            if (!amount || !amount.salesData || !amount.salesData.seriesData) {
              return;
            }
            const visitedData2 = amount.salesData.seriesData;
            const XAxisLabels2 = amount.salesData.xAxisLabels || [];
            const lastMonthName = amount.salesData.lastMonthName;
            const currentMonthName = amount.salesData.currentMonthName;
            this.visitedLineChartData = this.prepareCumulativeOutletVisitedData(visitedData2, XAxisLabels2, lastMonthName, currentMonthName);
          }
        })
    );
  }

  getShopVisitedSPLYComparisonData() {
    this.loaderService.startLoader();
    this.subscription.push(
      this.formService.customActionCall<DashboardData>(STATIC_MODULES.custom.getGraph3, { month1: this.group.get('searchbar')?.get('month1')?.value }, null, this.url)
        .pipe(
          finalize(() => this.loaderService.stopLoader()),
        )
        .subscribe(resp => {
          if (resp && resp.status === REQUEST_STATUS.SUCCESS) {
            this.graph3 = resp.data;
            const splyVisit = this.graph3.graphs.getShopVisitedSPLYComparisonData
            if (!splyVisit || !splyVisit.visitData || !splyVisit.visitData.seriesData) {
              return;
            }
            const SPLYData = splyVisit.visitData.seriesData; // Get seriesData
            const SPLYDataXAxisLabels = splyVisit.visitData.xAxisLabels || []; // Get XAxisLabels
            const lastYearMonthName = splyVisit.visitData.lastYearMonthName;
            const currentYearMonthName = splyVisit.visitData.currentYearMonthName;
            this.visitedSPLYLineChartData = this.prepareCumulativeSPLYOutletVisitedData(SPLYData, SPLYDataXAxisLabels, lastYearMonthName, currentYearMonthName);
          }
        })
    );
  }

  getOutletBilledComparisonData() {
    this.loaderService.startLoader();
    this.subscription.push(
      this.formService.customActionCall<DashboardData>(STATIC_MODULES.custom.getGraph4, { month2: this.group.get('searchbar')?.get('month2')?.value }, null, this.url)
        .pipe(
          finalize(() => this.loaderService.stopLoader()),
        )
        .subscribe(resp => {
          if (resp && resp.status === REQUEST_STATUS.SUCCESS) {
            this.graph4 = resp.data;
            const billed = this.graph4.graphs.getOutletBilledComparisonData
            if (!billed || !billed.billedData || !billed.billedData.seriesData) {
              return;
            }
            const billedData = billed.billedData.seriesData;
            const billedXAxisLabels = billed.billedData.xAxisLabels || [];
            const lastMonthName = billed.billedData.lastMonthName;
            const currentMonthName = billed.billedData.currentMonthName;
            this.billedLineChartData = this.prepareCumulativeOutletBilledData(billedData, billedXAxisLabels, lastMonthName, currentMonthName);
          }
        })
    );
  }

  getShopBilledSPLYComparisonData() {
    this.loaderService.startLoader();
    this.subscription.push(
      this.formService.customActionCall<DashboardData>(STATIC_MODULES.custom.getGraph5, { month3: this.group.get('searchbar')?.get('month3')?.value }, null, this.url)
        .pipe(
          finalize(() => this.loaderService.stopLoader()),
        )
        .subscribe(resp => {
          if (resp && resp.status === REQUEST_STATUS.SUCCESS) {
            this.graph5 = resp.data;
            const SPLYbilled = this.graph5.graphs.getShopBilledSPLYComparisonData
            if (!SPLYbilled || !SPLYbilled.billedSPLYData || !SPLYbilled.billedSPLYData.seriesData) {
              return;
            }
            const billedSPLYData = SPLYbilled.billedSPLYData.seriesData;
            const billedSPLYDataXAxisLabels = SPLYbilled.billedSPLYData.xAxisLabels || [];
            const lastYearMonth = SPLYbilled.billedSPLYData.lastYearMonthName;
            const currenYeartMonth = SPLYbilled.billedSPLYData.currentYearMonthName;
            this.billedSPLYLineChartData = this.prepareCumulativeSPLYOutletBilledData(billedSPLYData, billedSPLYDataXAxisLabels, lastYearMonth, currenYeartMonth);
          }
        })
    );
  }

  getShopBilledYTDLYTDComparisonData() {
    this.loaderService.startLoader();
    this.subscription.push(
      this.formService.customActionCall<DashboardData>(STATIC_MODULES.custom.getGraph6, { year1: this.group.get('searchbar')?.get('year1')?.value }, null, this.url)
        .pipe(
          finalize(() => this.loaderService.stopLoader()),
        )
        .subscribe(resp => {
          if (resp && resp.status === REQUEST_STATUS.SUCCESS) {
            this.graph6 = resp.data;
            const TYDLYTDbilled = this.graph6.graphs.getShopBilledYTDLYTDComparisonData
            if (!TYDLYTDbilled || !TYDLYTDbilled.ytdlytdBilled || !TYDLYTDbilled.ytdlytdBilled.seriesData) {
              return;
            }
            const TYDLYTDbilledData = TYDLYTDbilled.ytdlytdBilled.seriesData;
            const TYDLYTDbilledDataXAxisLabels = TYDLYTDbilled.ytdlytdBilled.xAxisLabels || [];
            const lastYear = TYDLYTDbilled.ytdlytdBilled.lastYear;
            const currentYear = TYDLYTDbilled.ytdlytdBilled.currentYear;
            this.billedYTDLYTDLineChartData = this.prepareCumulativeYTDLYTDOutletBilledData(TYDLYTDbilledData, TYDLYTDbilledDataXAxisLabels, lastYear, currentYear);
          }
        })
    );
  }

  getFocusCMLMOutletBilledComparisonData() {
    this.loaderService.startLoader();
    this.subscription.push(
      this.formService.customActionCall<DashboardData>(STATIC_MODULES.custom.getGraph7, { month4: this.group.get('searchbar')?.get('month4')?.value }, null, this.url)
        .pipe(
          finalize(() => this.loaderService.stopLoader()),
        )
        .subscribe(resp => {
          if (resp && resp.status === REQUEST_STATUS.SUCCESS) {
            this.graph7 = resp.data;
            const CMLMFocusBilled = this.graph7.graphs.getFocusCMLMOutletBilledComparisonData
            if (!CMLMFocusBilled || !CMLMFocusBilled.focusbilledData || !CMLMFocusBilled.focusbilledData.seriesData) {
              return;
            }
            const CMLMFocusBilledData = CMLMFocusBilled.focusbilledData.seriesData;
            const CMLMFocusBilledDataXAxisLabels = CMLMFocusBilled.focusbilledData.xAxisLabels || [];
            const lastMonth = CMLMFocusBilled.focusbilledData.lastMonthName;
            const currentMonth = CMLMFocusBilled.focusbilledData.currentMonthName;
            this.billedCMLMFocusLineChartData = this.prepareCumulativeCMLMFocusOutletBilledData(CMLMFocusBilledData, CMLMFocusBilledDataXAxisLabels, lastMonth, currentMonth);
          }
        })
    );
  }

  getFocusSPLYOutletBilledComparisonData() {
    this.loaderService.startLoader();
    this.subscription.push(
      this.formService.customActionCall<DashboardData>(STATIC_MODULES.custom.getGraph8, { month5: this.group.get('searchbar')?.get('month5')?.value }, null, this.url)
        .pipe(
          finalize(() => this.loaderService.stopLoader()),
        )
        .subscribe(resp => {
          if (resp && resp.status === REQUEST_STATUS.SUCCESS) {
            this.graph8 = resp.data;
            const SPLYBilled = this.graph8.graphs.getFocusSPLYOutletBilledComparisonData
            if (!SPLYBilled || !SPLYBilled.focusSPLYbilledData || !SPLYBilled.focusSPLYbilledData.seriesData) {
              return;
            }
            const SPLYFocusBilledData = SPLYBilled.focusSPLYbilledData.seriesData;
            const SPLYFocusBilledDataXAxisLabels = SPLYBilled.focusSPLYbilledData.xAxisLabels || [];
            const lastYearFocusMonth = SPLYBilled.focusSPLYbilledData.lastYearMonthName;
            const currentYearFocusMonth = SPLYBilled.focusSPLYbilledData.currentYearMonthName;
            this.billedSPLFocusLineChartData = this.prepareCumulativeSPLYocusOutletBilledData(SPLYFocusBilledData, SPLYFocusBilledDataXAxisLabels, lastYearFocusMonth, currentYearFocusMonth);
          }
        })
    );
  }
  // Monthly prepare cumulative Visited data for the line chart
  prepareCumulativeYTDLYTDOutletVisitedData(ytdlytdVisitData: any, ytdlytdVisitXAxisLabels: any[], lastYear: string, currentYear: string
  ): any[] {
    const lastYearData = ytdlytdVisitData[0]?.data || [];
    const currentYearData = ytdlytdVisitData[1]?.data || [];

    // Combine the data to group by month
    const transformedData = ytdlytdVisitXAxisLabels.map((month: string, index: number) => ({
      name: month,
      series: [
        { name: lastYear.toString(), value: lastYearData[index] || 0 },
        { name: currentYear.toString(), value: currentYearData[index] || 0 },
      ],
    }));

    return transformedData;
  }

  // Monthly prepare cumulative Visited data for the line chart
  prepareCumulativeSPLYOutletVisitedData(SPLYData: any, SPLYDataXAxisLabels: any[], lastYearMonthName: string, currentYearMonthName: string): any[] {
    const lastMonthData = SPLYData[0]?.data || [];
    const thisMonthData = SPLYData[1]?.data || [];

    const transformedData = SPLYDataXAxisLabels.map((date: string, index: number) => ({
      name: date.toString(), // Date on x-axis
      series: [
        { name: lastYearMonthName, value: lastMonthData[index] || 0 }, // Last month data
        { name: currentYearMonthName, value: thisMonthData[index] || 0 }, // Current month data
      ],
    }));
    return transformedData;
  }

  // Monthly prepare cumulative Visited data for the line chart
  prepareCumulativeOutletVisitedData(visitedData: any, XAxisLabels: any[], lastMonthName: string, currentMonthName: string): any[] {
    const lastMonthData = visitedData[0]?.data || [];
    const thisMonthData = visitedData[1]?.data || [];

    // Create series data for each date
    const transformedData = XAxisLabels.map((date: string, index: number) => ({
      name: date.toString(), // Date on x-axis
      series: [
        { name: lastMonthName, value: lastMonthData[index] || 0 }, // Last month data
        { name: currentMonthName, value: thisMonthData[index] || 0 }, // Current month data
      ],
    }));
    return transformedData;
  }

  // Monthly prepare cumulative Visited data for the line chart
  prepareCumulativeOutletBilledData(billedData: any, billedXAxisLabels: any[], lastMonthName: string, currentMonthName: string): any[] {
    const lastMonthData = billedData[0]?.data || [];
    const thisMonthData = billedData[1]?.data || [];

    const lastMonthSeries = lastMonthData.map((value: number, index: number) => ({
      name: billedXAxisLabels[index]?.toString() || '',
      value: value,
    }));

    const thisMonthSeries = thisMonthData.map((value: number, index: number) => ({
      name: billedXAxisLabels[index]?.toString() || '',
      value: value,
    }));

    return [
      { name: lastMonthName, series: lastMonthSeries },
      { name: currentMonthName, series: thisMonthSeries },
    ];
  }

  // SPLY prepare cumulative Visited data for the line chart
  prepareCumulativeSPLYOutletBilledData(billedSPLYData: any, billedSPLYDataXAxisLabels: any[], lastMonthName: string, currentMonthName: string): any[] {
    const lastMonthData = billedSPLYData[0]?.data || [];
    const thisMonthData = billedSPLYData[1]?.data || [];

    const lastMonthSeries = lastMonthData.map((value: number, index: number) => ({
      name: billedSPLYDataXAxisLabels[index]?.toString() || '',
      value: value,
    }));

    const thisMonthSeries = thisMonthData.map((value: number, index: number) => ({
      name: billedSPLYDataXAxisLabels[index]?.toString() || '',
      value: value,
    }));

    return [
      { name: lastMonthName, series: lastMonthSeries },
      { name: currentMonthName, series: thisMonthSeries },
    ];
  }

  // CMLM Focus prepare cumulative Visited data for the line chart
  prepareCumulativeYTDLYTDOutletBilledData(CMLMFocusBilledData: any, CMLMFocusBilledDataXAxisLabels: any[], lastYear: number, currentYear: number): any[] {
    const lastMonthData = CMLMFocusBilledData[0]?.data || [];
    const thisMonthData = CMLMFocusBilledData[1]?.data || [];

    const lastMonthSeries = lastMonthData.map((value: number, index: number) => ({
      name: CMLMFocusBilledDataXAxisLabels[index]?.toString() || '',
      value: value,
    }));

    const thisMonthSeries = thisMonthData.map((value: number, index: number) => ({
      name: CMLMFocusBilledDataXAxisLabels[index]?.toString() || '',
      value: value,
    }));

    return [
      { name: lastYear, series: lastMonthSeries },
      { name: currentYear, series: thisMonthSeries },
    ];
  }

  // SPLY Focus prepare cumulative Visited data for the line chart
  prepareCumulativeSPLYocusOutletBilledData(SPLYFocusBilledData: any, SPLYFocusBilledDataXAxisLabels: any[], lastYearMonthName: string, currentYearMonthName: string): any[] {
    const lastMonthData = SPLYFocusBilledData[0]?.data || [];
    const thisMonthData = SPLYFocusBilledData[1]?.data || [];

    const lastMonthSeries = lastMonthData.map((value: number, index: number) => ({
      name: SPLYFocusBilledDataXAxisLabels[index]?.toString() || '',
      value: value,
    }));

    const thisMonthSeries = thisMonthData.map((value: number, index: number) => ({
      name: SPLYFocusBilledDataXAxisLabels[index]?.toString() || '',
      value: value,
    }));

    return [
      { name: lastYearMonthName, series: lastMonthSeries },
      { name: currentYearMonthName, series: thisMonthSeries },
    ];
  }

  // CM/LM Focus prepare cumulative Visited data for the line chart
  prepareCumulativeCMLMFocusOutletBilledData(TYDLYTDbilledData: any, TYDLYTDbilledDataXAxisLabels: any[], lastMonthName: string, currentMonthName: string): any[] {
    const lastMonthData = TYDLYTDbilledData[0]?.data || [];
    const thisMonthData = TYDLYTDbilledData[1]?.data || [];

    const lastMonthSeries = lastMonthData.map((value: number, index: number) => ({
      name: TYDLYTDbilledDataXAxisLabels[index]?.toString() || '',
      value: value,
    }));

    const thisMonthSeries = thisMonthData.map((value: number, index: number) => ({
      name: TYDLYTDbilledDataXAxisLabels[index]?.toString() || '',
      value: value,
    }));

    return [
      { name: lastMonthName, series: lastMonthSeries },
      { name: currentMonthName, series: thisMonthSeries },
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
      this.formService.customActionCall<DashboardData>(STATIC_MODULES.custom.getBranch, { district: this.group.get('searchbar')?.get('district')?.value }, null, this.url)
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
      this.formService.customActionCall<DashboardData>(STATIC_MODULES.custom.getproduct, { category: this.group.get('searchbar')?.get('category')?.value }, null, this.url)
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
      this.formService.customActionCall<DashboardData>(STATIC_MODULES.custom.getCircle, { branch: this.group.get('searchbar')?.get('branch')?.value }, null, this.url)
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
      this.formService.customActionCall<DashboardData>(STATIC_MODULES.custom.getSection, { branch: this.group.get('searchbar')?.get('branch')?.value, circle: this.group.get('searchbar')?.get('circle')?.value }, null, this.url)
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
      this.formService.customActionCall<DashboardData>(STATIC_MODULES.custom.getWDList, { branch: this.group.get('searchbar')?.get('branch')?.value, circle: this.group.get('searchbar')?.get('circle')?.value, section: this.group.get('searchbar')?.get('section')?.value }, null, this.url)
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
      this.formService.customActionCall<DashboardData>(STATIC_MODULES.custom.getTeamsTypeList, { branch: this.group.get('searchbar')?.get('branch')?.value, circle: this.group.get('searchbar')?.get('circle')?.value, section: this.group.get('searchbar')?.get('section')?.value, wdCode: this.group.get('searchbar')?.get('wdCode')?.value }, null, this.url)
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
      this.formService.customActionCall<DashboardData>(STATIC_MODULES.custom.getTeamsList, { branch: this.group.get('searchbar')?.get('branch')?.value, circle: this.group.get('searchbar')?.get('circle')?.value, section: this.group.get('searchbar')?.get('section')?.value, wdCode: this.group.get('searchbar')?.get('wdCode')?.value, dsType: this.group.get('searchbar')?.get('dsType')?.value }, null, this.url)
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
    this.group.get('searchbar')?.get('circle')?.setValue(value);
  }
  set sectionValue(value: string | null) {
    this.sectionOptions = [];
    this.group.get('searchbar')?.get('section')?.setValue(value);
  }
  set wdCodeValue(value: string | null) {
    this.wdCodeOptions = [];
    this.group.get('searchbar')?.get('wdCode')?.setValue(value);
  }
  set dsTypeValue(value: string | null) {
    this.teamTypeOptions = [];
    this.group.get('searchbar')?.get('dsType')?.setValue(value);
  }
  set dsNameValue(value: string | null) {
    this.teamOptions = [];
    this.group.get('searchbar')?.get('dsName')?.setValue(value);
  }
  set wdMarketValue(value: string | null) {
    this.wdMarketOptions = [];
    this.group.get('searchbar')?.get('wdMarket')?.setValue(value);
  }

  set wdPopGroupValue(value: string | null) {
    this.wdPopGroupOptions = [];
    this.group.get('searchbar')?.get('wdPopGroup')?.setValue(value);
  }
  set productValue(value: string | null) {
    this.productOptions = [];
    this.group.get('searchbar')?.get('product')?.setValue(value);
  }
  set categoryValue(value: string | null) {
    this.categoryOptions = [];
    this.group.get('searchbar')?.get('category')?.setValue(value);
  }
  set branchValue(value: string | null) {
    this.branchOptions = [];
    this.group.get('searchbar')?.get('branch')?.setValue(value);
  }

  clearForm() {
    this.group.reset();
    this.getInitialData();
  }
  toggleSidebar() {
    this.isSidebarOpen = !this.isSidebarOpen;
  }

  setTab(tab: string): void {
    this.currentTab = tab;
  }

}
