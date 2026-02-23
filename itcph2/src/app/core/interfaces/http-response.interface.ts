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

