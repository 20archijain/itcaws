import { Component, OnDestroy, OnInit } from "@angular/core";
import { UntypedFormBuilder, UntypedFormGroup } from "@angular/forms";
import { Subscription } from "rxjs";
import { finalize } from "rxjs/operators";

import { ToastrService } from "src/app/core/services/toastr.service";
import { REQUEST_STATUS, STATIC_MODULES } from "src/app/app.constants";
import { MapConfig } from "src/app/core/interfaces/common.interface";
import {
  DashboardData,
  DropdownList,
  GetLocationCoveredResponse,
  SalesDashboardData,
} from "src/app/core/interfaces/http-response.interface";
import { FormService } from "src/app/core/services/form.service";
import { LoaderService } from "src/app/core/services/loader.service";
import { environment } from "src/environments/environment";

@Component({
  templateUrl: "./breeze-dashboard.component.html",
  styleUrls: ["./breeze-dashboard.component.scss"],
  standalone: false,
})
export class BreezeDashboardComponent implements OnDestroy, OnInit {
  private subscription: Subscription[] = [];
  url = environment.viewSalesDashboardDataUrl;
  group!: UntypedFormGroup;
  form!: UntypedFormGroup;
  // chartData;
  formData: any;
  columnSize = 12;
  noOfMaps: string[] = [];
  markers: MapConfig[] = [];
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
  // todaySaleDone: number;
  // todaySaleAmount: number;
  // tillDateSaleDone: number;
  // tillDateSaleAmount: number;
  // todayFocusSaleDone: number;
  // todayFocusSaleAmount: number;
  // tillDateFocusSaleDone: number;
  // tillDateFocusSaleAmount: number;
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
  branchFilter = false;
  showMapStyleDropdown = false;
  dashboardData: any;
  monthlySalesBarChartData: any[] = [];
  graph1: any;
  graphLevelOptions = [
    { label: "District", value: "district" },
    { label: "Branch", value: "branch" },
    { label: "Circle", value: "circle" },
    { label: "Section", value: "section" },
    { label: "WD Code", value: "wdCode" },
  ];
  selectedGraphLevel = "district";
  chartTypeOptions = [
    { label: "Grouped Bar", value: "grouped" },
    { label: "Bar", value: "bar" },
    { label: "Line", value: "line" },
  ];
  monthlySalesLineChartData: any = null;

  constructor(
    private fb: UntypedFormBuilder,
    private formService: FormService,
    private loaderService: LoaderService,
    private toastrService: ToastrService,
  ) { }

  ngOnInit() {
    this.group = this.fb.group({
      district: [""],
      branch: [""],
      circle: [""],
      section: [""],
      wdCode: [""],
      dsType: [""],
      dsName: [""],
      month: [""],
      month1: [""],
      month2: [""],
      month3: [""],
      year: [2025],
      week: [""],
      year1: [""],
      category: [""],
      product: [""],
      wdMarket: [],
      wdPopGroup: [],
      graphLevel: ["district"],
      chartType: ["grouped"],
    });

    this.initialData();
    // this.getSalesData();
  }

  ngOnDestroy() {
    this.subscription.forEach((sub) => sub.unsubscribe());
  }

  initialData() {
    if (this.group.valid) {
      this.loaderService.startLoader();

      this.subscription.push(
        this.formService
          .getData<SalesDashboardData>(this.url, this.group.getRawValue())
          .pipe(finalize(() => this.getCardData()))
          .subscribe((resp) => {
            if (resp && resp.status === REQUEST_STATUS.SUCCESS && resp.data) {
              this.monthOptions = resp.data.monthList;
              this.yearOptions = resp.data.yearList;
              this.districtOptions = resp.data.districtList;
              this.branchOptions = resp.data.branchList;
              this.circleOptions = resp.data.circleList;
              this.sectionOptions = resp.data.sectionList;
              this.wdCodeOptions = resp.data.wdCodeList;
              this.teamOptions = resp.data.teamList;
              this.branchFilter = resp.data.branchFilter ?? false;
              this.teamTypeOptions = resp.data.teamType;
              this.wdMarketOptions = resp.data.wdMarketList;
              this.wdPopGroupOptions = resp.data.wdPopGroupList;
              this.showMapStyleDropdown = resp.data.showMapStyleDropdown ?? false;
            }
          }),
      );
    }
  }

  getCardData(showLoader = false) {
    const monthValues = this.group.get("month")?.value;
    if (monthValues?.length <= 3) {
      // this.chartData = null;
      if (showLoader) {
        this.loaderService.startLoader();
      }
      this.subscription.push(
        this.formService
          .getList<SalesDashboardData>(this.url, this.group.getRawValue())
          .pipe(finalize(() => this.loaderService.stopLoader()))
          .subscribe((resp) => {
            if (resp && resp.status === REQUEST_STATUS.SUCCESS && resp.data) {
              // this.todaySaleDone = resp.data.todaySaleDone;
              // this.todaySaleAmount = resp.data.todaySaleAmount;
              // this.tillDateSaleDone = resp.data.tillDateSaleDone;
              // this.tillDateSaleAmount = resp.data.tillDateSaleAmount;
              // this.todayFocusSaleDone = resp.data.todayFocusSaleDone;
              // this.todayFocusSaleAmount = resp.data.todayFocusSaleAmount;
              // this.tillDateFocusSaleDone = resp.data.tillDateFocusSaleDone;
              // this.tillDateFocusSaleAmount = resp.data.tillDateFocusSaleAmount;
              this.monthlySalesData = resp.data.monthlySalesData;

              const graphResp = resp.data.monthlySalesGraphData;
              if (graphResp?.salesGraphData?.seriesData) {
                this.prepareMonthlySalesBarChartData(
                  graphResp.salesGraphData.seriesData,
                  graphResp.salesGraphData.xAxisLabels,
                );
              }
            }
          }),
      );
    } else {
      this.toastrService.toastr({
        msg: "Please select three months only",
        type: "error",
      });
    }
  }

  get selectedChartType(): string {
    return this.group.get("chartType")?.value || "grouped";
  }

  refreshGraph() {
    this.loaderService.startLoader();
    const payload = {
      ...this.group.getRawValue(),
      graphLevel: this.group.get("graphLevel")?.value || "district",
    };
    this.subscription.push(
      this.formService
        .getList<SalesDashboardData>(this.url, payload)
        .pipe(finalize(() => this.loaderService.stopLoader()))
        .subscribe((resp) => {
          if (resp && resp.status === REQUEST_STATUS.SUCCESS && resp.data) {
            this.monthlySalesData = resp.data.monthlySalesData;
            const respData = resp.data as any;
            const graphResp = respData?.monthlySalesGraphData;
            if (graphResp?.salesGraphData?.seriesData) {
              this.prepareMonthlySalesBarChartData(
                graphResp.salesGraphData.seriesData,
                graphResp.salesGraphData.xAxisLabels,
              );
            }
          }
        }),
    );
  }

  onChartTypeChange(): void {
    // No API call needed — just re-prepare labels with current data
    // Data is already loaded, selectedChartType getter handles the switch reactively
  }

  // prepareMonthlySalesBarChartData(seriesData: any[], xAxisLabels: any[]): void {
  //   const plannedData = seriesData[0]?.data || [];
  //   const revisitData = seriesData[1]?.data || [];
  //   const salesData = seriesData[2]?.data || [];

  //   this.monthlySalesBarChartData = xAxisLabels.map(
  //     (label: string, index: number) => ({
  //       name: label.toString(),
  //       series: [
  //         { name: "Planned Outlets", value: plannedData[index] || 0 },
  //         { name: "Outlets ReVisit", value: revisitData[index] || 0 },
  //         { name: "Total Sales", value: salesData[index] || 0 },
  //       ],
  //     }),
  //   );
  // }

  // prepareMonthlySalesBarChartData(seriesData: any[], xAxisLabels: any[]): void {
  //   const plannedData = seriesData[0]?.data || [];
  //   const revisitData = seriesData[1]?.data || [];
  //   const salesData = seriesData[2]?.data || [];

  //   // Build month label string like "(Mar 26, Apr 26)"
  //   const selectedMonths: string[] = this.group.get("month")?.value || [];
  //   // const monthSuffix = selectedMonths.length
  //   //   ? ` (${selectedMonths
  //   //       .map((m) => {
  //   //         const d = new Date(m);
  //   //         return d.toLocaleDateString("en-GB", {
  //   //           month: "short",
  //   //           year: "2-digit",
  //   //         });
  //   //       })
  //   //       .join(", ")})`
  //   //   : "";
  //   const monthSuffix = selectedMonths.length
  //     ? ` (${selectedMonths
  //         .map((m: string) => {
  //           const parts = m.split(" ");
  //           const monthAbbr = parts[0].slice(0, 3);
  //           const yearShort = parts[1]?.slice(2) || "";
  //           return `${monthAbbr} ${yearShort}`;
  //         })
  //         .join(", ")})`
  //     : "";

  //   this.monthlySalesBarChartData = xAxisLabels.map(
  //     (label: string, index: number) => ({
  //       name: label.toString(),
  //       series: [
  //         {
  //           name: `Planned Outlets${monthSuffix}`,
  //           value: plannedData[index] || 0,
  //         },
  //         {
  //           name: `Outlets ReVisit${monthSuffix}`,
  //           value: revisitData[index] || 0,
  //         },
  //         { name: `Total Sales${monthSuffix}`, value: salesData[index] || 0 },
  //       ],
  //     }),
  //   );
  // }

  prepareMonthlySalesBarChartData(seriesData: any[], xAxisLabels: any[]): void {
    const plannedData = seriesData[0]?.data || [];
    const revisitData = seriesData[1]?.data || [];
    const salesData = seriesData[2]?.data || [];

    const selectedMonths: string[] = this.group.get("month")?.value || [];
    const monthSuffix = selectedMonths.length
      ? ` (${selectedMonths
        .map((m: string) => {
          const parts = m.split(" ");
          const monthAbbr = parts[0].slice(0, 3);
          const yearShort = parts[1]?.slice(2) || "";
          return `${monthAbbr} ${yearShort}`;
        })
        .join(", ")})`
      : "";

    // For grouped/stacked bar chart (transformedChartData format)
    this.monthlySalesBarChartData = xAxisLabels.map(
      (label: string, index: number) => ({
        name: label.toString(),
        series: [
          { name: `Planned Outlets${monthSuffix}`, value: plannedData[index] || 0 },
          { name: `Outlets ReVisit${monthSuffix}`, value: revisitData[index] || 0 },
          { name: `Total Survey (M)${monthSuffix}`, value: salesData[index] || 0 },
        ],
      }),
    );

    // For line chart (chartData format — same as Bankit uses)
    this.monthlySalesLineChartData = {
      chartData: [
        { name: `Planned Outlets${monthSuffix}`, data: plannedData },
        { name: `Outlets ReVisit${monthSuffix}`, data: revisitData },
        { name: `Total Survey (M)${monthSuffix}`, data: salesData },
      ],
      title: "Planned Outlets vs Outlets ReVisit vs Total Survey (M)",
      xAxisLabel1: xAxisLabels,
      xAxisLabel2: { height: 400 },
    };
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
      this.formService
        .customActionCall<DashboardData>(
          STATIC_MODULES.custom.getBranch,
          { district: this.group.get("district")?.value },
          null,
          this.url,
        )
        .pipe(finalize(() => this.loaderService.stopLoader()))
        .subscribe((resp) => {
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
        }),
    );
  }

  getProduct() {
    this.loaderService.startLoader();
    this.subscription.push(
      this.formService
        .customActionCall<DashboardData>(
          STATIC_MODULES.custom.getproduct,
          { category: this.group.get("category")?.value },
          null,
          this.url,
        )
        .pipe(finalize(() => this.loaderService.stopLoader()))
        .subscribe((resp) => {
          if (resp && resp.status === REQUEST_STATUS.SUCCESS && resp.data) {
            this.productOptions = resp.data.productList;
          }
        }),
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
      this.formService
        .customActionCall<DashboardData>(
          STATIC_MODULES.custom.getCircle,
          { branch: this.group.get("branch")?.value },
          null,
          this.url,
        )
        .pipe(finalize(() => this.loaderService.stopLoader()))
        .subscribe((resp) => {
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
        }),
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
      this.formService
        .customActionCall<DashboardData>(
          STATIC_MODULES.custom.getSection,
          {
            branch: this.group.get("branch")?.value,
            circle: this.group.get("circle")?.value,
          },
          null,
          this.url,
        )
        .pipe(finalize(() => this.loaderService.stopLoader()))
        .subscribe((resp) => {
          if (resp && resp.status === REQUEST_STATUS.SUCCESS && resp.data) {
            this.sectionOptions = resp.data.sectionList;
            this.wdCodeOptions = resp.data.wdCodeList;
            this.teamOptions = resp.data.teamList;
            this.teamTypeOptions = resp.data.teamType;
            this.wdMarketOptions = resp.data.wdMarketList;
            this.wdPopGroupOptions = resp.data.wdPopGroupList;
          }
        }),
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
      this.formService
        .customActionCall<DashboardData>(
          STATIC_MODULES.custom.getWDList,
          {
            branch: this.group.get("branch")?.value,
            circle: this.group.get("circle")?.value,
            section: this.group.get("section")?.value,
          },
          null,
          this.url,
        )
        .pipe(finalize(() => this.loaderService.stopLoader()))
        .subscribe((resp) => {
          if (resp && resp.status === REQUEST_STATUS.SUCCESS && resp.data) {
            this.wdCodeOptions = resp.data.wdCodeList;
            this.teamOptions = resp.data.teamList;
            this.teamTypeOptions = resp.data.teamType;
            this.wdMarketOptions = resp.data.wdMarketList;
            this.wdPopGroupOptions = resp.data.wdPopGroupList;
          }
        }),
    );
  }

  getTeamsType() {
    this.dsTypeValue = null;
    this.dsNameValue = null;
    this.loaderService.startLoader();
    this.subscription.push(
      this.formService
        .customActionCall<DashboardData>(
          STATIC_MODULES.custom.getTeamsTypeList,
          {
            branch: this.group.get("branch")?.value,
            circle: this.group.get("circle")?.value,
            section: this.group.get("section")?.value,
            wdCode: this.group.get("wdCode")?.value,
          },
          null,
          this.url,
        )
        .pipe(finalize(() => this.loaderService.stopLoader()))
        .subscribe((resp) => {
          if (resp && resp.status === REQUEST_STATUS.SUCCESS && resp.data) {
            this.teamTypeOptions = resp.data.teamType;
            this.teamOptions = resp.data.teamList;
          }
        }),
    );
  }

  getTeamsName() {
    this.dsNameValue = null;
    this.loaderService.startLoader();
    this.subscription.push(
      this.formService
        .customActionCall<DashboardData>(
          STATIC_MODULES.custom.getTeamsList,
          {
            branch: this.group.get("branch")?.value,
            circle: this.group.get("circle")?.value,
            section: this.group.get("section")?.value,
            wdCode: this.group.get("wdCode")?.value,
            dsType: this.group.get("dsType")?.value,
          },
          null,
          this.url,
        )
        .pipe(finalize(() => this.loaderService.stopLoader()))
        .subscribe((resp) => {
          if (resp && resp.status === REQUEST_STATUS.SUCCESS && resp.data) {
            this.teamOptions = resp.data.teamList;
          }
        }),
    );
  }

  set branchValue(value: string | null) {
    this.branchOptions = [];
    this.group.get("branch")?.setValue(value);
  }
  set circleValue(value: string | null) {
    this.circleOptions = [];
    this.group.get("circle")?.setValue(value);
  }
  set sectionValue(value: string | null) {
    this.sectionOptions = [];
    this.group.get("section")?.setValue(value);
  }
  set wdCodeValue(value: string | null) {
    this.wdCodeOptions = [];
    this.group.get("wdCode")?.setValue(value);
  }
  set dsTypeValue(value: string | null) {
    this.teamTypeOptions = [];
    this.group.get("dsType")?.setValue(value);
  }
  set dsNameValue(value: string | null) {
    this.teamOptions = [];
    this.group.get("dsName")?.setValue(value);
  }
  set wdMarketValue(value: string | null) {
    this.wdMarketOptions = [];
    this.group.get("wdMarket")?.setValue(value);
  }

  set wdPopGroupValue(value: string | null) {
    this.wdPopGroupOptions = [];
    this.group.get("wdPopGroup")?.setValue(value);
  }
  clearForm() {
    this.group.reset({ month: "" });
    this.initialData();
    this.categoryOptions = [];
    this.productOptions = [];
  }

  toggleSidebar() {
    this.isSidebarOpen = !this.isSidebarOpen;
  }

  onMapClicked(data: any): void {
    console.log("Map clicked in parent:", data);

    this.markers = [];
    this.loaderService.startLoader();

    this.subscription.push(
      this.formService
        .customActionCall<GetLocationCoveredResponse>(
          STATIC_MODULES.custom.getMapdata,
          data,
          null,
          this.url,
        )
        .pipe(finalize(() => this.loaderService.stopLoader()))
        .subscribe((resp) => {
          if (resp && resp.status === REQUEST_STATUS.SUCCESS && resp.data) {
            this.columnSize = resp.data.columnSize ? +resp.data.columnSize : 12;
            this.noOfMaps = Array(resp.data.repeatMapBy ? +resp.data.repeatMapBy : 1).fill("");

            if (resp.data.markers && resp.data.markers.length > 0) {
              this.markers = resp.data.markers.map((marker) => ({
                ...marker,
                latitude: +marker.latitude,
                longitude: +marker.longitude,
              }));
            }
          }
        }),
    );
  }
}
