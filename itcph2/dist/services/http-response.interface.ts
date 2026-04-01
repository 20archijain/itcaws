import { SeriesOptionsType } from 'highcharts';

import { CustomGalleryConfig, GalleryImagesList, MapConfig, StatisticsConfig } from './common.interface';

export interface GenericObject {
  [key: string]: GenericObject;
}

export interface DropdownList<V = string, L = string> {
  label: L;
  value: V;
  disabled?: boolean;
}

// Login Data Interface
export interface LoginDataResponse {
  enableTwoWayAuth: boolean;
  user: LoginDataUser;
  landing: LoginDataLanding;
  client: LoginDataClient;
  token: string;
  modules: GenericObject;
}

export interface LoginDataUser {
  id?: string;
  mobile?: string;
  name: string;
  email: string;
}

export interface LoginDataLanding {
  modc: string;
  pmodc: string;
}

export interface LoginDataClient {
  logo: string;
}

export interface GetViewHeaderBody {
  viewHeader?: string[];
  viewBody?: string[];
}

export interface AddGroup extends DropdownList {
  isChecked?: boolean;
}

export interface GetTableListingResponse extends GetViewHeaderBody {
  data0: any;
}

export interface ViewClientsResponse extends GetTableListingResponse {
  sortOptions: DropdownList[];
}

export interface GroupDataResponse {
  modulesList: DropdownList[][];
  groupData: {
    name: string;
    items: string;
  };
  sortOptions: DropdownList[];
}

export interface GetUserDataResponse extends GetViewHeaderBody {
  teamTypeList: DropdownList[];
  teamList: DropdownList[];
  sectionList: DropdownList[];
  circleList: DropdownList[];
  loginTypeList: DropdownList[];
  clientList: DropdownList[];
  landingPageList: DropdownList[];
  groupList: DropdownList[];
  projectList: DropdownList[];
  branchList: DropdownList[];
  wdCodeList: DropdownList[];
  sortOptions: DropdownList[];
  unlockCondition: [string, boolean];
}

export interface ViewGroupResponse {
  id: number;
  name: string;
  modules: string;
}

export interface ModuleDataResponse extends GetViewHeaderBody {
  moduleActionCodeList: DropdownList[];
  modulePositionList: DropdownList[];
  breadcrumbList: DropdownList<number>[];
  sortOptions: DropdownList[];
}

export interface GetAttendanceDataResponse {
  showAsUserCard?: boolean;
  cgConfig?: CustomGalleryConfig;
  attendanceTimeList: DropdownList[];
  clientList: DropdownList[];
  districtList: DropdownList[];
  branchList: DropdownList[];
  teamList: DropdownList[];
  teamType: DropdownList[];
  monthList?: DropdownList[];
  circleList?: DropdownList[];
  sectionList?: DropdownList[];
  wdCodeList?: DropdownList[];
  yearList?: DropdownList<number, number>[];
  branchFilter?: boolean
}

export interface GetDownloadBillCutResponse {
  reportTypeList: DropdownList[];
  monthList: DropdownList[];
  districtList: DropdownList[];
  wdMarketList: DropdownList[];
  wdPopGroupList: DropdownList[];
  branchList: DropdownList[];
  branchFilter?: boolean
  teamType: DropdownList[];
  productList: DropdownList[];
  circleList: DropdownList[];
  sectionList: DropdownList[];
  wdCodeList: DropdownList[];
  teamList: DropdownList[];
  dsType: DropdownList[];
}

export interface GetDownloadLeaderboardData {
  branchList: DropdownList[];
  circleList: DropdownList[];
  sectionList: DropdownList[];
  wdCodeList: DropdownList[];
  teamList: DropdownList[];
  branchFilter?: boolean
  dsType: DropdownList[];
}

export interface ViewAttendanceTrackerResponse {
  totalPresent: number;
  totalTeams: number;
  images: GalleryImagesList[];
}

export interface ViewAttendanceLocatorResponse {
  total: number;
  markers: MapConfig[];
}

export interface GetLocationCoveredResponse {
  workingWith: string;
  wdPopGroupList: DropdownList[];
  wdMarketList: DropdownList[];
  districtList: DropdownList[];
  yearList: DropdownList<number, number>[];
  monthList?: DropdownList[];
  branchList?: DropdownList[];
  circleList?: DropdownList[];
  sectionList?: DropdownList[];
  wdCodeList?: DropdownList[];
  teamList?: DropdownList[];
  teamType?: DropdownList[];
  rmdNameList?: DropdownList[];
  columnSize?: number;
  repeatMapBy?: number;
  markers: MapConfig[];
  branchFilter?: boolean
}

export interface GetDownloadFileDetails {
  fileName: string;
  filePath: string;
}

export interface GetAddProjectDataResponse {
  branchList: DropdownList[];
  districtList: DropdownList[];
  clientList: DropdownList[];
  landingPageList: DropdownList[];
  checkboxList: DropdownList[];
}

export interface SkuPickUpList {
  date: string;
  productList: ProductList[];
  grandTotalStockPicked: number;
}
export interface ViewskuPickupResponse {
  SkuPickUpList: SkuPickUpList[];
  totalPresent: string;
  totalTeams: string;
}

export interface ProductList {
  productName: string;
  csrPicked: string;
  stockPicked: string;
}

export interface ViewProjectsResponse extends GetTableListingResponse {
  sortOptions: DropdownList[];
  clientList: DropdownList[];
  landingPageList: DropdownList[];
}

export interface ViewWdMappingResponse extends GetTableListingResponse {
  districtList: DropdownList[];
  branchList: DropdownList[];
  circleList: DropdownList[];
  sectionList: DropdownList[];
  wdCodeList: DropdownList[];
  wdMarketList: DropdownList[];
  wdPopGroupList: DropdownList[];
}

export interface GetAddTeamDataResponse extends GetViewHeaderBody {
  focusTypeList: DropdownList[];
  brandTypeList: DropdownList[];
  reportTypeList: DropdownList[];
  productList: DropdownList[];
  monthList: DropdownList[];
  yearList: DropdownList[];
  teamType: DropdownList[];
  wdCodeList: DropdownList[];
  wdPopGroupList: DropdownList[];
  wdMarketList: DropdownList[];
  districtList: DropdownList[];
  projectList: DropdownList[];
  branchList: DropdownList[];
  wdList: DropdownList[];
  circleList: DropdownList[];
  sectionList: DropdownList[];
  dsTypeList: DropdownList[];
  mdoTypeList: DropdownList[];
  statusList: DropdownList[];
  teamList: DropdownList[];
  routeList: DropdownList[];
  aeNameList: DropdownList[];
  jsonIdList: DropdownList[];
  amNameList: DropdownList[];
  separatorList?: DropdownList[];
  sortOptions?: DropdownList[];
  accessList: DropdownList[];
  deleteCondition: [string, number];
  isSelectable: boolean;
  jsonName?: string;
  branchFilter?: boolean
}

export interface StockProduct extends DropdownList {
  daySummary: string;
  previousDay: string;
  laminateStock: string;
  day5Incubation: string;
  fgStock: string;
  stockIssued?: number;
  brand?: any;
  wd_code?: any;
  productOnePreMonthTarget?: number;
  productOnepreviousMonthAchieve?: number;
  productTwoPreMonthTarget?: number;
  productTwopreviousMonthAchieve?: number;
  productOneCurrentMonthTarget?: number;
  productOnecurrentMonthAchieve?: number;
  productTwoCurrentMonthTarget?: number;
  productTwocurrentMonthAchieve?: number;
  overAllPreMonthTarget?: number;
  overallPreviousMonthAchieve?: number;
  overallCurrentMonthTarget?: number;
  overallCurrentMonthAchieve?: number;
  productOneNextMonthTarget?: number;
  productTwoNextMonthTarget?: number;
  overAllNextMonthTarget?: number;
  existTeamTableCond?: number;
}

export interface ReadyStockPickupResponse {
  previousMonthProduct1: string;
  previousMonthProduct2: string;
  nextMonthProduct1: string;
  nextMonthProduct2: string;
  tableColumnCondition: boolean;
  product1: string;
  product2: string;
  monthList: DropdownList[];
  yearList: DropdownList[];
  todaysSales: DropdownList[];
  sumMTD: number;
  distributorList: DropdownList[];
  teamTypeList: DropdownList[];
  stockIssuedHeading?: string;
  stockProductsList: StockProduct[];
  teamsList: DropdownList[];
}

// Listing
export interface ListingDataResponse<T = any> {
  listingData: T[];
  total: number;
}

export interface DropdownList<V = string, L = string> {
  label: L;
  value: V;
  disabled?: boolean;
}

export interface VanDsListingData<T = any> extends ListingDataResponse<T> {
  wdPopGroupList: DropdownList[];
  wdMarketList: DropdownList[];
  districtList: DropdownList[];
  branchList: DropdownList[];
  circleList: DropdownList[];
  sectionList: DropdownList[];
  wdCodeList: DropdownList[];
  sortOptions?: DropdownList[];
  teamList: DropdownList[];
  wdList: DropdownList[];
  viewHeader?: string[];
  viewBody?: string[];
  teamType: DropdownList[];
  showTransactionDownloadBtn?: boolean;
  showSummaryDownloadBtn?: boolean;
  branchFilter?: boolean;
  userBranch?: string;
  binderReportDownloadDays?: number;
  transactionReportDownloadDays?: number;
  summaryReportDownloadDays?: number;
}

export interface MdoListingData<T = any> extends ListingDataResponse<T> {
  wdPopGroupList: DropdownList[];
  wdMarketList: DropdownList[];
  districtList: DropdownList[];
  branchList: DropdownList[];
  circleList: DropdownList[];
  sectionList: DropdownList[];
  wdCodeList: DropdownList[];
  sortOptions?: DropdownList[];
  teamList: DropdownList[];
  wdList: DropdownList[];
  viewHeader?: string[];
  viewBody?: string[];
  teamType: DropdownList[];
  showTransactionDownloadBtn?: boolean;
  showSummaryDownloadBtn?: boolean;
  branchFilter?: boolean;
  userBranch?: string;
}

export interface GetActiveDsDataResponse extends GetViewHeaderBody {
  branchList: DropdownList[];
  sortOptions?: DropdownList[];
  teamList: DropdownList[];
  wdList: DropdownList[];
  viewHeader?: string[];
  viewBody?: string[];
  separatorList?: DropdownList[];
  teamType: DropdownList[];
  // showTransactionDownloadBtn?: boolean;
  // showSummaryDownloadBtn?: boolean;
}

export interface VanDsListing {
  reportingType: string;
  route: string;
  section: string;
  shopName: string;
  mobileNumber: string;
  shopType: string;
  sellIinOrder: string;
  timestamp: string;
  team: string;
  branchName: string;
  circle: string;
  wdCode: string;
  team_id: number;
  lt: number;
  lg: number;
  images: GalleryImagesList[];
}

export interface MdoListing {
  reportingType: string;
  workType: string;
  route: string;
  dsName: string;
  section: string;
  shopName: string;
  mobileNumber: string;
  shopType: string;
  sellIinOrder: string;
  timestamp: string;
  team: string;
  branchName: string;
  circle: string;
  wdCode: string;
  team_id: number;
  lt: number;
  lg: number;
  images: GalleryImagesList[];
}

export interface DashboardData {
  teamTypeList: DropdownList[];
  regionList: DropdownList[];
  districtList: DropdownList[];
  wdPopGroupList: DropdownList[];
  wdMarketList: DropdownList[];
  categoryList: DropdownList[];
  productList: DropdownList[];
  graphs: StatisticsConfig[];
  dashboardList: any;
  monthList: DropdownList[];
  yearList: DropdownList[];
  branchList: DropdownList[];
  circleList: DropdownList[];
  sectionList: DropdownList[];
  wdCodeList: DropdownList[];
  teamList: DropdownList[];
  teamType: DropdownList[];
  attCardData: number;
  qualifiedAttData: number;
  outletVisitedCardData: number;
  todayOutletVisitedCardData: number;
  beatAdherenceCardData: number;
  slideCardData: any;
  focusVisitTodayAmountCardData: number;
  focusVisitTillDateAmountCardData: number;
  todaySalesAmountCardData: number;
  salesAmountComparisonData: number;
  allTeams: number;
  morningAttData: number;
  percentAttendance: number;
  notPresent: number;
  totalRoute: number;
  adherence: number;
  avgCompRoute: number;
  getShopVisitedYTDLYTDComparisonDataMonthly: any;
  outletVisitedMonthlyComparisonData: any;
  getShopVisitedSPLYComparisonData: any;
  getShopBilledSPLYComparisonData: any;
  getShopBilledYTDLYTDComparisonData: any;
  getFocusCMLMOutletBilledComparisonData: any;
  getFocusSPLYOutletBilledComparisonData: any;
  getOutletBilledComparisonData: any;
  branchFilter?: boolean
}

export interface DashboardTableData {
  attendanceDetails: AttendanceDetails;
  avgTimeInMarketDetails: AttendanceDetails;
  appAttendanceUsageAnalysis: AttendanceDetails;
  timeInMarketAnalysis: AttendanceDetails;
  appKmTravelledUsageAnalysis: AttendanceDetails;
  outletsCoverage: AttendanceDetails;
  salesConvertedCoverage: AttendanceDetails;
  avgDailySales: AttendanceDetails;
}

export interface SalesDashboardData {
  monthlySalesGraphData: any;
  wdMarketList: DropdownList[];
  wdPopGroupList: DropdownList[];
  districtList: DropdownList[];
  saleList: any[];
  monthList: DropdownList[];
  yearList: DropdownList[];
  branchList: DropdownList[];
  circleList: DropdownList[];
  sectionList: DropdownList[];
  wdCodeList: DropdownList[];
  teamList: DropdownList[];
  teamType: DropdownList[];
  todaySaleDone: number;
  todaySaleAmount: number;
  tillDateSaleDone: number;
  tillDateSaleAmount: number;
  monthlySalesData: any;
  chartsData: any;
  outletVisitedTableData: any;
  todayFocusSaleDone: number;
  todayFocusSaleAmount: number;
  tillDateFocusSaleDone: number;
  tillDateFocusSaleAmount: number;
  currentAndLastMonthData: any;
  currentMonthVsLastYearMonthSales: any;
  currentYearLastYearMonthlySales: any;
  currentAndLastMonthFocusData: any;
  currentMonthVsLastYearMonthFocusSales: any;
  currentYearLastYearMonthlyFocusSales: any;
  branchFilter?: boolean
  showMapStyleDropdown?: boolean
}

export interface AttendanceDetails {
  heading?: string;
  header: string[] | string[][];
  body: {
    rows: string[];
    expandableData?: {
      rows: string[][];
    };
  }[];
}

export interface StackedGroupedColumnChartData {
  title?: string;
  yAxisLabel?: string;
  xAxisLabels: string[];
  seriesData: SeriesOptionsType[];
}

export interface RouteDataUploadResponse {
  excelData: RouteDataUploadExcelData;
  excelDataDashboard: any[];
  excelHeader: string[];
  tableColumns: string[];
}

export interface RouteDataUploadExcelData {
  [key: string]: string[];
}

export interface DownloadReports {
  projectList: DropdownList<string, string>[];
  dataBaseList: DropdownList<string, string>[];
  wdCodeList: DropdownList<string, string>[];
  retailerList: DropdownList<string, string>[];
  statusList: DropdownList<string, string>[];
  sectionList: DropdownList<string, string>[];
  teamList: DropdownList<string, string>[];
  typeList: DropdownList<string, string>[];
  tableList: DropdownList<string, string>[];
  operatorList: DropdownList<string, string>[];
  logicalOperatorList: DropdownList<string, string>[];
  viewHeader: string[];
  viewBody: string[];
}
export interface GetProductiveReportDataResponse {
  monthList?: DropdownList[];
  yearList?: DropdownList<number, number>[];
}

export interface EditProductResponse {
  editProductHeading?: string;
  branchLabel?: string;
  wdLabel?: string;
  teamTypeLabel?: string;
  branchList: DropdownList[];
  wdList: DropdownList[];
  teamsTypeList: DropdownList[];
  productData: ProductList[];
  productsList: any[];
}

export interface ProductList {
  productName: string;
  sellingPrice: number;
  productId: number;
  branchId: number;
  wdCode: string;
}

export interface teams {
  aeName: DropdownList<string, string>[];
  wdName: DropdownList<string, string>[];
  tlName: DropdownList<string, string>[];
  teamList: DropdownList<string, string>[];
  routeList: DropdownList<string, string>[];
}

export interface GetProductSelectorDataResponse {
  tableData: any[];
  isFocusList: [];
  isDspmList: [];
  selectedDataList: ProductItem[];
  selectedProductsList: [];
  submittedList: any[];
  statusFlag: boolean;
  mainBranchList: DropdownList[];
  productList: any;
  isSelectable: boolean;
  viewBody: string[];
  viewHeader: string[];
  status: string;
  data: {
    productList: DropdownList[];         // all available products from backend
    viewHeader: string[];
    viewBody: string[];
    isSelectable: boolean;
  };
}

export interface ProductItem {
  id: number;
  name: string;
  category: string;
  price?: number;
  sku?: string;
}

// Each item in the submit payload — includes checkbox flags
export interface ProductItemWithFlags extends ProductItem {
  dspmBrand: boolean;   // firstCheckbox  — max 2 across all selected products
  isFocusBrand:   boolean;   // secondCheckbox — unlimited, any/all can be checked
}

export interface SubmitSelectedProductsResponse {
  status: string;
  data?: any;
  message?: string;
}

export interface ProductSelectorPayload {
  selectedProducts: ProductItemWithFlags[];
  formData: [];
}

export interface AiInsightsTopProduct {
  productName: string;
  totalSales: number;
  sharePercent?: number;
}

export interface AiInsightsTopWdCode {
  wdCode: string;
  totalSales: number;
  sharePercent?: number;
  branch?: string;
  dsCount?: number;
  qualifiedDays?: number;
  totalDays?: number;
  qualificationRate?: number;
}

export interface AiInsightsPeriodComparison {
  name: string;
  currentSales: number;
  previousSales: number;
  changePercent: number;
  direction: string;
}

export interface AiInsightsDayOfWeek {
  dayName: string;
  dayNumber: number;
  totalSales: number;
  avgSales: number;
  activeDsAvg: number;
  qualificationRate: number;
  weekCount: number;
}

export interface AiInsightsGrowthData {
  name: string;
  firstHalfSales: number;
  secondHalfSales: number;
  changePercent: number;
  direction: string;
  trend: string;
}

export interface AiInsightsExecutiveSummary {
  totalSales: number;
  avgDailySales: number;
  totalDs: number;
  avgQualificationRate: number;
  totalBranches: number;
  activeDays: number;
  bestBranch?: string;
  worstBranch?: string;
}

export interface AiInsightsAnomaly {
  branch: string;
  date: string;
  weekday: string;
  actualSales: number;
  expectedSales: number;
  zScore: number;
  type: string;
  severity: string;
  deviationPercent: number;
  activeDsCount: number;
  expectedDsCount: number;
  cause: string; // 'attendance_issue' | 'market_issue' | 'no_ds_in_field' | 'demand_spike' | 'extra_ds_deployed'
  urgency: string; // 'critical' | 'warning' | 'watch'
  district: string;
  mainBranch: string;
  isRegional: boolean;
  dsBreakdown?: Array<{ dsName: string; sales: number }>;
}

export interface AiInsightsFocusBrandAnomaly extends AiInsightsAnomaly {
  productName: string;
  categoryName: string;
}

export interface AiInsightsRegionalEvent {
  date: string;
  district: string;
  affectedBranches: string[];
  branchCount: number;
  avgZScore: number;
  urgency: string; // 'critical' | 'warning' | 'watch'
}

export interface AiInsightsAnomalyMapPoint {
  branch: string;
  lat: number;
  lng: number;
  urgency: string; // 'critical' | 'warning' | 'watch'
  anomalyCount: number;
}

export interface AiInsightsStreak {
  branch: string;
  type: string;    // 'drop' | 'spike'
  days: number;
  startDate: string;
  endDate: string;
  avgZScore: number;
  urgency: string; // 'critical' | 'warning' | 'watch'
}

export interface AiInsightsOutletData {
  dsName: string;
  planned: number;
  visited: number;
  billed: number;
  coveragePercent: number;
  billingPercent: number;
}

export interface AiInsightsProductivityData {
  dsName: string;
  avgTimeInMarket: number;
  avgKm: number;
  totalSales: number;
  salesPerMinute: number;
}

export interface AiInsightsInventoryData {
  dsName: string;
  totalSales: number;
  totalDays: number;
  avgDailySales: number;
}

export interface AiInsightsRouteData {
  dsName: string;
  totalDays: number;
  adherenceDays: number;
  adherencePercent: number;
}

export interface AiInsightsDsPerformance {
  teamId: string;
  dsName: string;
  wdCode: string;
  branch: string;
  district: string;
  region?: string;
  circle?: string;
  section?: string;
  totalSales: number;
  qualifiedDays: number;
  totalDays: number;
  qualificationRate: number;
}

export interface AiInsightsComparativeData {
  name: string;
  totalSales: number;
  dsCount: number;
  qualifiedDays: number;
  totalDays: number;
  qualificationRate: number;
  adherenceRate?: number;
}

export interface AiInsightsBranchQualified {
  branchId: string;
  regionName: string;
  branchName?: string;
  district?: string;
  qualifiedDays: number;
  totalDays: number;
  uniqueDays: number;
  qualificationRate: number;
  totalDs: number;
  totalSales: number;
  avgSalesPerDay: number;
}

export interface AiInsightsResponse {
  query: string;
  dateRange: {
    startDate: string;
    endDate: string;
    days: number;
  };
  filters: {
    district?: string | string[];
    branch?: string | string[];
    circle?: string | string[];
    section?: string | string[];
    wdCode?: string | string[];
    dsType?: string | string[];
    dsName?: string | string[];
    product?: string | string[];
    category?: string | string[];
  };
  metrics: {
    totalSales: number;
    avgDailySales: number;
    totalDs?: number;
    totalDsAll?: number;
    avgQualificationRate?: number;
    avgAdherenceRate?: number;
    totalCount?: number;
    totalBranches?: number;
    totalRegions?: number;
    totalDistricts?: number;
    totalCircles?: number;
    totalSections?: number;
    totalWdCodes?: number;
    compareType?: string;
    rating?: string;
    // Period/product comparison
    currentTotal?: number;
    previousTotal?: number;
    changePercent?: number;
    // Category / focus brand
    categoryCount?: number;
    focusTotal?: number;
    focusShare?: number;
    nonFocusTotal?: number;
    focusCount?: number;
    // Growth
    growingCount?: number;
    decliningCount?: number;
    stableCount?: number;
    dimension?: string;
    // Outlet sales
    totalOutlets?: number;
    totalTransactions?: number;
    grandRevenue?: number;
    avgOrderValue?: number;
    // Anomaly-specific
    totalAnomalies?: number;
    spikes?: number;
    drops?: number;
    highSeverity?: number;
    branchesAnalyzed?: number;
    attendanceIssues?: number;
    marketIssues?: number;
    noDs?: number;
    focusBrandAnomalyCount?: number;
    criticalCount?: number;
    warningCount?: number;
    watchCount?: number;
    streakCount?: number;
    regionalEventCount?: number;
    // Breeze-specific
    totalPresent?: number;
    totalQualified?: number;
    qualRate?: number;
    totalSalesM?: number;
    avgSalesM?: number;
    rmdCount?: number;
    stockistCount?: number;
    anomalyCount?: number;
    // DS Leaderboard
    grandTotal?: number;
    avgSales?: number;
    periodDays?: number;
    // Outlet Visit Frequency
    lowVisited?: number;
    avgVisits?: number;
    expectedVisits?: number;
  };
  topProducts: AiInsightsTopProduct[];
  topWdCodes: AiInsightsTopWdCode[];
  topDs?: AiInsightsDsPerformance[];
  comparativeData?: AiInsightsComparativeData[];
  topBranches?: AiInsightsBranchQualified[];
  dsLabel?: string;
  productLabel?: string;
  dataLabel?: string;
  branchLabel?: string;
  dimLabel?: string;
  compareType?: string;
  topProductSharePercent?: number;
  topWdSharePercent?: number;
  detectedFilters?: string[];
  contextNote?: string;
  aiText: string;
  visualizationType?: string;
  showWorst?: boolean;
  dailyTrend?: Array<{ date: string; totalSales: number }>;
  isDsQuery?: boolean;
  isDsCountQuery?: boolean;
  dsCountBreakdown?: Array<{ name: string; dsCount: number }>;
  dsCountBreakdownLabel?: string;
  dsCountScope?: string;
  isCombinedQuery?: boolean;
  isComparativeQuery?: boolean;
  isBranchQualifiedQuery?: boolean;
  isWdCodeQuery?: boolean;
  isRouteQuery?: boolean;
  isPeriodComparison?: boolean;
  isDayOfWeekQuery?: boolean;
  isGrowthQuery?: boolean;
  isExecutiveSummary?: boolean;
  isAnomalyQuery?: boolean;
  period?: { current: { start: string; end: string }; previous: { start: string; end: string } };
  comparisonData?: AiInsightsPeriodComparison[];
  dayOfWeekData?: AiInsightsDayOfWeek[];
  growthData?: AiInsightsGrowthData[];
  executiveSummary?: AiInsightsExecutiveSummary;
  bottomBranches?: AiInsightsBranchQualified[];
  anomalyData?: AiInsightsAnomaly[];
  focusBrandAnomalies?: AiInsightsFocusBrandAnomaly[];
  streaks?: AiInsightsStreak[];
  regionalEvents?: AiInsightsRegionalEvent[];
  anomalyMapPoints?: AiInsightsAnomalyMapPoint[];
  outletData?: AiInsightsOutletData[];
  productivityData?: AiInsightsProductivityData[];
  inventoryData?: AiInsightsInventoryData[];
  routeData?: AiInsightsRouteData[];
  trendMetrics?: { highestDay?: string; highestSales?: number; lowestDay?: string; lowestSales?: number; avgSales?: number };
  routeMetrics?: { avgAdherence?: number; totalDs?: number };
  // Category, Product Comparison, Focus Brand
  isCategoryQuery?: boolean;
  categoryData?: AiInsightsCategoryData[];
  productBreakdown?: Array<{ productName: string; totalSales: number }>;
  specificCategory?: string;
  isProductComparison?: boolean;
  productComparisonData?: AiInsightsProductComparison[];
  branchComparison?: AiInsightsProductComparison[];
  entityLabel?: string;
  isFocusBrandQuery?: boolean;
  focusBrandSummary?: Array<{ name: string; totalSales: number; sharePercent: number; productCount: number }>;
  focusProducts?: AiInsightsFocusProduct[];
  nonFocusProducts?: AiInsightsFocusProduct[];
  // DS Scorecard
  isDsScorecard?: boolean;
  dsScorecard?: AiInsightsDsScorecard;
  dsDailyTrend?: Array<{ date: string; sales: number; qualified: number }>;
  dsProductBreakdown?: Array<{ productName: string; totalSales: number }>;
  dsMapLocations?: AiMapPoint[];
  dsOutletLocations?: AiOutletPoint[];
  // Hierarchy Scorecard
  isHierarchyScorecard?: boolean;
  hierarchyScorecard?: AiHierarchyScorecard;
  hierarchyTrend?: Array<{ date: string; sales: number; dsCount: number }>;
  hierarchyTopDs?: Array<{ dsName: string; wdCode: string; sales: number; qualRate: number }>;
  hierarchyBottomDs?: Array<{ dsName: string; wdCode: string; sales: number; qualRate: number }>;
  hierarchySubBreakdown?: AiHierarchySubBreakdown;
  hierarchyHeatmapPoints?: AiHeatmapPoint[];
  // Outlet-level Sales
  isOutletSales?: boolean;
  outletSalesData?: AiOutletSalesRecord[];
  // Geographic Heatmap
  isGeographicHeatmap?: boolean;
  heatmapPoints?: AiHeatmapPoint[];
  // Entity comparison (X vs Y)
  isEntityComparison?: boolean;
  comparisonLeft?: { entityName: string; totalSales: number; avgDailySales: number; totalDsCount: number; qualificationRate: number; adherenceRate: number; coverageRate: number; rating: string };
  comparisonRight?: { entityName: string; totalSales: number; avgDailySales: number; totalDsCount: number; qualificationRate: number; adherenceRate: number; coverageRate: number; rating: string };
  comparisonLevel?: string;
  is_today_summary?: boolean;
  todayHeatmapPoints?: AiHeatmapPoint[];
  executiveSummaryTopDs?: Array<{ dsName: string; totalSales: number; qualificationRate: number }>;
  executiveSummaryDailyTrend?: Array<{ date: string; totalSales: number }>;
  executiveSummaryTopProducts?: Array<{ productName: string; totalSales: number }>;
  mapScopeLevel?: 'all' | 'district' | 'branch' | 'region' | 'circle' | 'section' | 'wd_code';
  districtSummary?: AiHierarchySummary[];
  branchSummary?: AiHierarchySummary[];
  regionSummary?: AiHierarchySummary[];
  circleSummary?: AiHierarchySummary[];
  sectionSummary?: Array<{ name: string; totalSales: number; dsCount: number; sharePercent: number }>;
  wdSummary?: Array<{ name: string; totalSales: number; dsCount: number; sharePercent: number }>;
  // Breeze Field Force
  isBreezeQuery?: boolean;
  breezeData?: AiInsightsBreezeRecord[];
  breezeDailyTrend?: Array<{ date: string; totalSalesM: number; present: number; qualified: number; outlets: number }>;
  breezeAnomalies?: AiInsightsBreezeAnomaly[];
  // DS Leaderboard
  isDsLeaderboard?: boolean;
  dsTop10?: AiInsightsDsLeaderboardEntry[];
  dsBottom10?: AiInsightsDsLeaderboardEntry[];
  // Outlet Visit Frequency
  isOutletVisitFrequency?: boolean;
  underVisitedOutlets?: AiInsightsUnderVisitedOutlet[];
  outletVisitMapPoints?: AiInsightsOutletVisitMapPoint[];
  visitBuckets?: { [key: string]: number };
}

export interface AiInsightsCategoryData {
  categoryName: string;
  totalSales: number;
  sharePercent: number;
  productCount: number;
  products?: Array<{ productName: string; totalSales: number }>;
}

export interface AiInsightsProductComparison {
  name: string;
  currentSales: number;
  previousSales: number;
  changePercent: number;
  direction: string;
}

export interface AiInsightsFocusProduct {
  productName: string;
  categoryName: string;
  totalSales: number;
  sharePercent?: number;
  isFocusBrand: boolean;
}

export interface AiMapPoint {
  lat: number;
  lng: number;
  date: string;
  time: string;
  type: string;
  route: string;
}

export interface AiOutletPoint {
  lat: number;
  lng: number;
  name: string;
  route: string;
  market: string;
  type: string;
  shopType: string;
}

export interface AiOutletSalesRecord {
  outletName: string;
  market: string;
  outletType: string;
  shopType: string;
  dsName: string;
  wdCode: string;
  visitCount: number;
  totalRevenue: number;
  avgOrderValue: number;
  lat: number;
  lng: number;
}

export interface AiHeatmapPoint {
  lat: number;
  lng: number;
  sales: number;
  region: string;
  district: string;
  circle: string;
  dsCount: number;
}

export interface AiRegionSummary {
  circle: string;
  totalSales: number;
  branches: number;
  dsCount: number;
  sharePercent: number;
}

export interface AiHierarchySummary {
  name: string;
  totalSales: number;
  regions?: number;
  districts?: number;
  dsCount: number;
  sharePercent: number;
}

export interface AiInsightsDsScorecard {
  dsName: string;
  wdCode: string;
  section: string;
  circle: string;
  region: string;
  branch: string;
  district: string;
  rating: string;
  totalSales: number;
  avgDailySales: number;
  qualifiedDays: number;
  totalDays: number;
  qualificationRate: number;
  adherenceDays: number;
  adherenceRate: number;
  plannedOutlets: number;
  billedOutlets: number;
  coverageRate: number;
  avgTimeInMarket: number;
  avgKm: number;
  peerAvgSales: number;
  peerAvgQualification: number;
  salesVsPeer: number;
}

export interface AiHierarchyScorecard {
  level: string;
  levelLabel: string;
  entityName: string;
  rating: string;
  totalSales: number;
  avgDailySales: number;
  totalDsCount: number;
  activeDs: number;
  qualifiedDays: number;
  totalDays: number;
  qualificationRate: number;
  adherenceDays: number;
  adherenceRate: number;
  plannedOutlets: number;
  billedOutlets: number;
  coverageRate: number;
  avgTimeInMarket: number;
  avgKm: number;
  hierarchyInfo: {
    parent?: string;
    grandParent?: string;
    parentLabel?: string;
    grandParentLabel?: string;
    children?: string[];
    childLabel?: string;
  };
}

export interface AiHierarchySubBreakdown {
  label: string;
  data: Array<{
    name: string;
    sales: number;
    qualRate: number;
    adherenceRate: number;
    dsCount: number;
  }>;
}

export interface AiInsightsBreezeRecord {
  branch: string;
  district: string;
  circle: string;
  type: string;
  present: number;
  qualified: number;
  qualRate: number;
  totalSalesM: number;
  avgSalesM: number;
  avgTimeMin: number;
  avgKm: number;
  totalOutlets: number;
  avgOutlets: number;
}

export interface AiInsightsBreezeAnomaly {
  branch: string;
  type: string;
  date: string;
  weekday: string;
  actualSales: number;
  expectedSales: number;
  zScore: number;
  anomalyType: string;
  severity: string;
  deviationPercent: number;
}

export interface AiInsightsDsLeaderboardEntry {
  rank: number;
  rankPrev: number | null;
  rankChange: number | null;
  teamId: string | number;
  dsName: string;
  wdCode: string;
  region: string;
  district: string;
  totalSales: number;
  qualifiedDays: number;
  totalDays: number;
  qualificationRate: number;
}

export interface AiInsightsUnderVisitedOutlet {
  outletId: string;
  outletName: string;
  market: string;
  outletType: string;
  dsName: string;
  wdCode: string;
  region: string;
  visitCount: number;
  lastVisitDate: string;
  firstVisitDate: string;
  daysSinceVisit: number | null;
  lat: number;
  lng: number;
}

export interface AiInsightsOutletVisitMapPoint {
  name: string;
  lat: number;
  lng: number;
  visitCount: number;
  dsName: string;
}
