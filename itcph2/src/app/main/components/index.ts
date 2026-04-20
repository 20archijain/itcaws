import { AttendanceLocatorComponent } from './attendance/attendance-locator.component';
import { AttendanceTrackerComponent } from './attendance/attendance-tracker.component';
import { ComingSoonComponent } from 'src/app/shared/components/coming-soon/coming-soon.component';
import { MyProfileComponent } from './my-profile/my-profile.component';
import { AddClientComponent } from './client/add.client.component';
import { ViewClientComponent } from './client/view.client.component';
import { AddProjectComponent } from './project/add.project.component';
import { ViewProjectComponent } from './project/view.project.component';
import { AddTeamComponent } from './team/add.team.component';
import { ViewTeamComponent } from './team/view.team.component';
import { LocationsCoveredComponent } from './sites-on-map/locations-covered.component';
import { RouteTrackerComponent } from './sites-on-map/route-tracker.component';
import { RMDLocationsCoveredComponent } from './sites-on-map/rmd-locations-covered.component';
import { AddGroupComponent } from './super-user/add.group.component';
import { ViewGroupComponent } from './super-user/view.group.component';
import { AddUserComponent } from './super-user/add.user.component';
import { ViewUserComponent } from './super-user/view.user.component';
import { AddModuleComponent } from './super-user/add.module.component';
import { ViewModuleComponent } from './super-user/view.module.component';
import { VanDsListingComponent } from './reporting/van-ds/van-ds-listing.component';
import { DashboardComponent } from './dashboard/dashboard.component';
import { RouteDataUploadComponent } from './route-data/route-data-upload.component';
import { RouteDataDownloadComponent } from './route-data/route-data-download.component';
import { DowloadBillCutComponent } from './dowload-bill-cut/dowload-bill-cut.component';
import { ProductivityReportComponent } from './dowload-bill-cut/productivity-report.component';
import { SkuPickupComponent } from './sku-pickup/sku-pickup.component';
import { ActiveUsersListingComponent } from './reporting/ActiveUsers/ActiveUsers.component';
import { MasterDataListingComponent } from './reporting/MasterData/MasterData.component';
import { EditProductPriceComponent } from './edit-price/edit-product-price/edit-product-price.component';
import { SalesDashboardComponent } from './sales-dashboard/sales-dashboard.component';
import { LeaderBoardComponent } from './leaderboardReport/leaderboardReport.component';
import { UniverseDataComponent } from './sites-on-map/universe-data.component';
import { NpsrDashboardComponent } from './npsr-dashboard/npsr-dashboard.component';
import { ProductiveDashboardComponent } from './productive-dashboard/productive-dashboard.component';

import { AddMdoTeamComponent } from './team/add.mdo.team.component';
import { AssignTargetComponent } from './assign-target/assignTarget.component';
import { ViewWdMappingComponent } from './wd-mapping/view.wdmapping.component';
import { EvaluationReportComponent } from './download-evaluation-report/evaluation-report.component';
import { DownloadMisscallComponent } from './download-misscall/download-misscall.component';
import { ViewRouteComponent } from './route/view-route.component';
import { MdoListingComponent } from './reporting/mdo/mdo-listing.component';
import { SystemOfflineComponent } from './system-offline/system-offline.component';
import { MdoRouteTrackerComponent } from './sites-on-map/mdo-route-tracker.component';
import { MdoUniverseDataComponent } from './sites-on-map/mdo-universe-data.component';
import { ActiveMDOUsersListingComponent } from './reporting/active-mdo/active-mdo-reporting.component';
import { AppNotificationComponent } from './notification/app.notification.component';
import { TargetReportComponent } from './assign-target/target-report.component';
import { MdoPerformanceComponent } from './mdo-performance/mdo-performance.component';
import { MDORouteDataUploadComponent } from './route-data/mdo-route-upload.component';
import { MDOViewTeamComponent } from './team/mdoview.team.component';
import { FocusBrandReportingComponent } from './focus-brand-reporting/focus.brand.reporting.component';
import { AddWdMappingComponent } from './wd-mapping/add.wdmapping.component';
import { SWDTargetUploadComponent } from './swd-retailer-target/swd-retailer-target.component';
import { BreezeResponseUploadComponent } from './breeze_reponse_upload/breezeResponseUpload.component';
import { FocusBrandDataListingComponent } from './reporting/FocusBrandData/FocusBrandData.component';
import { MdoDownloadRouteComponent } from './route-data/mdo-route-download.component';
import { DownloadDBTableComponent } from './download-db-table/download-db-table.component';
import { FSOTrackerDataUploadComponent } from './fso-tracker-upload/fso-tracker-upload.component';
import { NpsrDashboard2Component } from './npsr-dashboard/npsr-dashboard2.component';
import { PDFAccessReportComponent } from './pdf-access-report/pdf-access-report.component';
import { LineCutReportComponent } from './line-cut/line-cut-report.component';
import { OrderListingComponent } from './reporting/order-report/order-listing.component';
import { BranchProductAllocationComponent } from './reporting/skuSetting/branchProductAllocation.component';
import { DistrictProductAllocationComponent } from './reporting/skuSetting/districtProductAllocation.component';
import { AllocationReportComponent } from './reporting/skuSetting/allocationReport.component';
import { BreezeDashboardComponent } from './breeze-dashboard/breeze-dashboard.component';
import { AiInsightsComponent } from './ai-insights/ai-insights.component';
import { MDOReportComponent } from './reporting/mdo/mdo-report.component';
import { ManualAssignTargetComponent } from './reporting/UpdateManualTarget/ManualAssignTarget.component';

export const MAIN_COMPONENTS = [
  AttendanceLocatorComponent,
  AttendanceTrackerComponent,
  MyProfileComponent,
  AddClientComponent,
  AddProjectComponent,
  ViewClientComponent,
  ViewProjectComponent,
  AddTeamComponent,
  ViewTeamComponent,
  LocationsCoveredComponent,
  RouteTrackerComponent,
  RMDLocationsCoveredComponent,
  AddGroupComponent,
  ViewGroupComponent,
  AddUserComponent,
  ViewUserComponent,
  AddModuleComponent,
  ViewModuleComponent,
  VanDsListingComponent,
  DashboardComponent,
  RouteDataUploadComponent,
  RouteDataDownloadComponent,
  DowloadBillCutComponent,
  ProductivityReportComponent,
  SalesDashboardComponent,
  SkuPickupComponent,
  ActiveUsersListingComponent,
  MasterDataListingComponent,
  EditProductPriceComponent,
  LeaderBoardComponent,
  UniverseDataComponent,
  EvaluationReportComponent,
  NpsrDashboardComponent,
  ProductiveDashboardComponent,
  AddMdoTeamComponent,
  AssignTargetComponent,
  ViewWdMappingComponent,
  DownloadMisscallComponent,
  ViewRouteComponent,
  MdoListingComponent,
  SystemOfflineComponent,
  MdoRouteTrackerComponent,
  MdoUniverseDataComponent,
  ActiveMDOUsersListingComponent,
  AppNotificationComponent,
  TargetReportComponent,
  MdoPerformanceComponent,
  MDOViewTeamComponent,
  MDORouteDataUploadComponent,
  FocusBrandReportingComponent,
  AddWdMappingComponent,
  SWDTargetUploadComponent,
  BreezeResponseUploadComponent,
  MdoDownloadRouteComponent,
  FocusBrandDataListingComponent,
  DownloadDBTableComponent,
  FSOTrackerDataUploadComponent,
  NpsrDashboard2Component,
  PDFAccessReportComponent,
  LineCutReportComponent,
  OrderListingComponent,
  BranchProductAllocationComponent,
  DistrictProductAllocationComponent,
  AllocationReportComponent,
  BreezeDashboardComponent,
  AiInsightsComponent,
  MDOReportComponent,
  ManualAssignTargetComponent,
];

export const MAP_MAIN_COMPONENTS = {
  AddClientComponent,
  AddGroupComponent,
  AddModuleComponent,
  AddProjectComponent,
  AddTeamComponent,
  AddUserComponent,
  AttendanceLocatorComponent,
  AttendanceTrackerComponent,
  ComingSoonComponent,
  DashboardComponent,
  LocationsCoveredComponent,
  MyProfileComponent,
  RMDLocationsCoveredComponent,
  RouteTrackerComponent,
  VanDsListingComponent,
  ViewClientComponent,
  ViewGroupComponent,
  ViewModuleComponent,
  ViewProjectComponent,
  ViewTeamComponent,
  ViewUserComponent,
  RouteDataUploadComponent,
  RouteDataDownloadComponent,
  DowloadBillCutComponent,
  ProductivityReportComponent,
  EditProductPriceComponent,
  SalesDashboardComponent,
  ActiveUsersListingComponent,
  MasterDataListingComponent,
  LeaderBoardComponent,
  UniverseDataComponent,
  EvaluationReportComponent,
  NpsrDashboardComponent,
  ProductiveDashboardComponent,
  AddMdoTeamComponent,
  AssignTargetComponent,
  ViewWdMappingComponent,
  DownloadMisscallComponent,
  ViewRouteComponent,
  MdoListingComponent,
  SystemOfflineComponent,
  MdoRouteTrackerComponent,
  MdoUniverseDataComponent,
  ActiveMDOUsersListingComponent,
  AppNotificationComponent,
  TargetReportComponent,
  MdoPerformanceComponent,
  MDOViewTeamComponent,
  MDORouteDataUploadComponent,
  FocusBrandReportingComponent,
  AddWdMappingComponent,
  SWDTargetUploadComponent,
  BreezeResponseUploadComponent,
  MdoDownloadRouteComponent,
  FocusBrandDataListingComponent,
  DownloadDBTableComponent,
  FSOTrackerDataUploadComponent,
  NpsrDashboard2Component,
  PDFAccessReportComponent,
  LineCutReportComponent,
  OrderListingComponent,
  BranchProductAllocationComponent,
  DistrictProductAllocationComponent,
  AllocationReportComponent,
  BreezeDashboardComponent,
  AiInsightsComponent,
  MDOReportComponent,
  ManualAssignTargetComponent,
};
