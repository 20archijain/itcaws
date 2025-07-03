export type ChartColorSchemeNames = 'VIVID' | 'NATURAL' | 'COOL' | 'FIRE' | 'SOLAR' | 'AIR' |
  'AQUA' | 'FLAME' | 'OCEAN' | 'FOREST' | 'HORIZON' | 'NEONS' | 'PICNIC' | 'NIGHT' | 'NIGHTLIGHTS';

export type ThemeMap = {
  [key in ChartColorSchemeNames]: ThemeDomainColorList;
};

export interface HttpRequestParamsModuleInfo {
  moduleName?: string;
  staticModule?: boolean;
}

export interface HttpRequestParams extends HttpRequestParamsModuleInfo {
  action: string;
  data?: any;
}

export interface HttpRequestPayload {
  auth_token: string;
  request_info: HttpRequestInfoPayload;
  staticModule: boolean;
}

export interface HttpRequestInfoPayload {
  action: string;
  data: any;
  module: string | HttpRequestModuleCodes;
}

export interface HttpRequestModuleCodes {
  modc: string;
  pmodc: string;
}

export interface HttpRequestResponse<T = any> {
  status: number;
  message: string[];
  data?: T;
  hidePopup?: boolean;
}

export interface LoginRequestPayload {
  captcha?: string;
  password: string;
  username: string;
}

export interface TwoWayAuthRequestPayload {
  id: string;
  otp: string;
}

export interface HttpRequestParamsModuleInfo {
  moduleName?: string;
  staticModule?: boolean;
}

export interface TimeoutModalConfig {
  countdown: number;
}

export interface SessionModuleObject {
  [key: string]: SessionModule;
}

export interface SessionModule extends HttpRequestModuleCodes {
  breadcrumbs: boolean;
  id: number;
  name: string;
  actions: string[];
  fold: string;
  icon: string;
  hidden: number;
  componentName: string;
  submodules: SessionModuleObject;
}

export interface MapConfig {
  date?: string;
  latitude: number;
  longitude: number;
  markerUrl?: string;
  markerTitle?: string;
  windowTitle?: string;
  color?: string;
  size?: string;
  borderWidth?: string;
}

export interface ModalData {
  data?: any;
  show: boolean;
}

export interface CustomGalleryConfig {
  closePreviewOnEsc?: boolean;
  previewActions?: CustomGalleryPreviewActionsConfig;
  previewImageKey?: string;
  previewImageDwnKey?: string;
  previewTextKey?: string;
  showPreviewOnClick?: boolean;
  showPreviewText?: boolean;
  showThumbnailActions?: boolean;
  showThumbnailText?: boolean;
  thumbnailAltTextKey?: string;
  thumbnailContentKey?: string;
  thumbnailImageKey?: string;
  thumbnailMaxHeight?: string;
  thumbnailMaxWidth?: string;
  thumbnailSizeClass?: string;
  thumbnailTitleKey?: string;
}

export interface CustomGalleryPreviewActionsConfig {
  maxZoom?: number;
  minZoom?: number;
  rotate?: boolean;
  zoom?: boolean;
}

export interface GalleryImagesList {
  big: string;
  small?: string;
  medium?: string;
  description?: string;
  id: string;
  downloadFileName?: string;
  thumbnailTitle?: string;
  thumbnailContent?: string;
  thumbnailAltText?: string;
  latitude?: number;
  longitude?: number;
}

export interface GalleryPreviewStyleConfig {
  rotate?: string;
  scale?: string;
}

export interface GalleryPreviewStyle {
  transform: string;
}

export interface ServerStatisticsConfig {
  autoScale?: boolean;
  data: any[];
  doughnut?: boolean;
  explodeSlices?: boolean;
  gradient?: boolean;
  trimLabels?: boolean;
  height?: number;
  legend?: boolean;
  legendPosition?: string;
  legendTitle?: string;
  scheme?: ChartColorSchemeNames;
  width?: number;
  hideXAxis?: boolean;
  hideYAxis?: boolean;
  showDataLabel?: boolean;
  noBarWhenZero?: boolean;
  barPadding?: number;
  groupPadding?: number;
  roundEdges?: boolean;
  tooltipDisabled?: boolean;
  xAxisLabel?: string;
  yAxisLabel?: string;
  trimXAxisTicks?: boolean;
  trimYAxisTicks?: boolean;
  rotateXAxisTicks?: boolean;
  maxXAxisTickLength?: number;
  maxYAxisTickLength?: number;
  timeline?: boolean;
  yScaleMin?: number;
  yScaleMax?: number;
  hideGridLines?: boolean;
  roundDomains?: boolean;
  hidePieChartLabels?: boolean;
  trimPieChartLabels?: boolean;
  pieChartMaxLabelLength?: number;
  pieGridLabel?: string;
  pieGridDesignatedTotal?: number;
  pieGridMinEachGraphWidth?: number;
}

export interface StatisticsConfig extends ServerStatisticsConfig {
  type: number;
  heading?: string;
  graphSizeClass?: string;
  graphMaxHeight?: number;
  noOverflow?: boolean;
  ngxCustomColors?: string[];
  appendPercentageOnYAxis?: boolean;
  appendPercentageOnXAxis?: boolean;
  appendPercentageOnDatalabel?: boolean;
  textToAppendAfterValue?: string;
  advancedPieChartTotalLabel?: string;
  apexColors?: string[];
  apexChartID?: string;
}

export interface ThemeDomainColorList {
  domain: string[];
}

export interface EditData {
  data?: any;
  show: boolean;
}

export interface DropdownChangeData {
  data?: any;
  type: number;
}
