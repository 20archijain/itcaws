// This file can be replaced during build by using the `fileReplacements` array.
// `ng build --prod` replaces `environment.ts` with `environment.prod.ts`.
// The list of file replacements can be found in `angular.json`.
export const basePath = {
  image: 'assets/images/',
  mapMarkers: 'assets/markers/',
  svgIcons: 'assets/icons/',
  url: ''
};

export const baseHref = '';
const baseUrl = `${baseHref}/resources/`;

export const environment = {
  addClientUrl: baseUrl + 'addClient.json',
  addGroupUrl: baseUrl + 'addGroup.json',
  addModuleUrl: baseUrl + 'addModule.json',
  addProjectUrl: baseUrl + 'addProject.json',
  addTeamUrl: baseUrl + 'addTeam.json',
  addUserUrl: baseUrl + 'addUser.json',
  apiUrl: baseUrl,
  changePasswordUrl: baseUrl + 'changePassword.json',
  confirmUploadRouteDataUrl: baseUrl + 'confirmUploadRouteData.json',
  deleteDataUrl: baseUrl + 'deleteData.json',
  downloadAttendanceUrl: baseUrl + 'downloadAttendanceData.json',
  downloadDataUrl: baseUrl + 'downloadData.json',
  downloadExcelUrl: baseUrl + 'downloadExcel.json',
  editGroupUrl: baseUrl + 'editGroup.json',
  editProductUrl: baseUrl + 'getEditProduct.json',
  editProfileUrl: baseUrl + 'editProfile.json',
  forgotUrl: baseUrl + 'forgot.json',
  getActiveUsersUrl: baseUrl + 'getActiveUsersData.json',
  getActiveVariantsDataUrl: baseUrl + 'getActiveVariantsData.json',
  getAddProjectDataUrl: baseUrl + 'getAddProjectData.json',
  // getAnalyticsDataUrl: baseUrl + 'getAnalyticsData.json',
  getAttendanceDataUrl: baseUrl + 'getAttendanceData.json',
  getBinderReportDataUrl: baseUrl + 'getBinderReportData.json',
  getCaptchaUrl: baseUrl + 'getCaptcha.json',
  // getDashboardDataUrl: baseUrl + 'getDashboardData.json',
  getEvaluationReportDataUrl: baseUrl + 'getEvaluationReportData.json',
  getGroupDataUrl: baseUrl + 'getGroupData.json',
  getListingExcelUrl: baseUrl + 'getListingExcel.json',
  getLocationsCoveredDataUrl: baseUrl + 'getLocationsCoveredData.json',
  getModuleDataUrl: baseUrl + 'getModuleData.json',
  getOutletUniverseDataUrl: baseUrl + 'getOutletUniverseData.json',
  getProductiveReportDataUrl: baseUrl + 'getProductiveReportData.json',
  getRouteDataDownloadDataUrl: baseUrl + 'getRouteDataDownloadDataUrl.json',
  getRouteDataUploadDataUrl: baseUrl + 'getRouteDataUploadData.json',
  getTeamDataUrl: baseUrl + 'getTeamData.json',
  getUobReportDataUrl: baseUrl + 'getUobReportData.json',
  getUserDataUrl: baseUrl + 'getUserData.json',
  loginUrl: baseUrl + 'login.json',
  logoutUrl: baseUrl + 'logout.json',
  noUrlProvidedUrl: baseUrl + 'noUrlProvided.json',
  production: false,
  uploadRouteDataUrl: baseUrl + 'uploadRouteData.json',
  viewAttendanceLocatorUrl: baseUrl + 'viewAttendanceLocator.json',
  viewAttendanceTrackerUrl: baseUrl + 'viewAttendanceTracker.json',
  viewClientsUrl: baseUrl + 'viewClients.json',
  viewDashboardDataUrl: baseUrl + 'viewDashboardData.json',
  viewSalesDashboardDataUrl: baseUrl + 'viewSalesDashboardData.json',
  aiInsightsUrl: baseUrl + 'aiInsights.json',
  salesDataDataUrl: baseUrl + 'salesDashboard.json',
  viewGroupUrl: baseUrl + 'viewGroups.json',
  viewModulesUrl: baseUrl + 'viewModules.json',
  viewProjectsUrl: baseUrl + 'viewProjects.json',
  viewTeamsUrl: baseUrl + 'viewTeams.json',
  viewUsersUrl: baseUrl + 'viewUsers.json',
  viewVanDsDataUrl: baseUrl + 'viewVanDsData.json',
  viewSkuPickupUrl: baseUrl + 'getSkuPickup.json',
  dashboardDataUrl: baseUrl + 'dashboardData.json',
};

/*
 * For easier debugging in development mode, you can import the following file
 * to ignore zone related error stack frames such as `zone.run`, `zoneDelegate.invokeTask`.
 *
 * This import should be commented out in production mode because it will have a negative impact
 * on performance if an error is thrown.
 */
// import 'zone.js/dist/zone-error';  // Included with Angular CLI.
