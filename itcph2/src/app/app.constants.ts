import { LegendPosition } from '@swimlane/ngx-charts';

import { basePath } from 'src/environments/environment';

export const imagePath = `${basePath.url}${basePath.image}`;

export const mapPath = `${basePath.url}${basePath.mapMarkers}`;

export const TOASTR_DEFAULT_CONFIG = {
  POSITION: 'toast-top-right',
  TIMEOUT: 2000,
  TYPE: 'default',
};

export enum AUTOREFRESH {
  enable = 1,
  duration = 300000, // 5min
}

export const URL_PARAMS_KEYS = {
  id: 'id',
  modc: 'modc',
  pmodc: 'pmodc',
  type: 'type',
};

export enum REQUEST_STATUS {
  SUCCESS = 200,
  WARNING = 300,
  FAILED = 400,
}

export enum Timeout {
  IDLE_TIME = 14400,  // 4Hr
  TIMEOUT = 60,
}

export enum TWO_WAY {
  ENABLE_SEND_OTP_BTN_IN_SEC = 90,
}

export const CONSTANTS = {
  ALL_VALUE: 'all',
  REGENERATE_CAPTCHA_IF_FAILS: true,
  STRONG_PASSWORD_FUNC: false,
  WINDOW_TITLE: ' | ITC',
};

export const CUSTOM_VALIDATOR_KEYS = {
  ATLEAST_ONE_QUANTITY_REQUIRED: 'atleastOneQtyRequired',
  ATLEAST_ONE_VALUE_REQUIRED: 'atleastOneValueRequired',
  INVALID_PATTERN: 'invalidPattern',
  PASSWOR_NOT_MATCH: 'passwordNotMatch',
  FILE_SIZE: 'fileSize',
  FILE_TYPE: 'fileType',
  ALL_VALUES_IN_GROUP_REQUIRED: 'allValuesInGroupRequired',
};

export const UPLOAD_FILES = {
  dummyAttachmentImage: imagePath + 'attach.png',
  dummyImage: imagePath + 'dummy_pic.jpg',
  fileTypes: {
    excel: {
      mimeTypes: ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.ms-excel'],
      fileExtensions: ['.xlsx', '.xls'],
    },
    csv: {
      mimeTypes: ['text/csv'],
      fileExtensions: ['.csv'],
    },
    imageOnly: {
      mimeTypes: ['image/*'],
      fileExtensions: ['.jpg', '.png', '.jpeg', '.gif'],
    },
    pdf: {
      mimeTypes: ['application/pdf'],
      fileExtensions: ['.pdf'],
    },
    word: {
      mimeTypes: ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/msword'],
      fileExtensions: ['.docx', '.doc'],
    },
  },
  loginImage: imagePath + 'auth/itc-logo.jpg',
  maxFileSizeInBytes: 10485760, // in bytes (10 MB, 1MB = 1024KB)
};

// "module" is used to identify file used for requested module
// "action" is used to call requested method from that file
export const STATIC_MODULES = {
  custom: {
    changePassword: 'change_password',
    getCaptcha: 'captcha',
    getCityList: 'get_city',
    getDownloadData: 'get_download_data',
    getDownloadSummary: 'get_download_summary',
    getProjectsList: 'get_projects',
    getTeamsList: 'get_teams',
    getRouteList: 'get_route_list',
    getTeamsTypeList: 'get_teams_type',
    getWDList: 'get_wd_code',
    getHeader: 'get_header',
    dashboardData: 'get_dashboard_data',
    changeToken: 'change_token',
    getData: 'get_user_data',
    getCircle: 'get_circle',
    getBranch: 'get_branch',
    getproduct: 'get_product',
    getSection: 'get_section',
    getCmLmData: 'get_cm_lm',
    getCmLmFocusData: 'get_cm_lm_focus',
    getCmLymData: 'get_cm_lym',
    getCmLymFocusData: 'get_cm_lym_focus',
    getCyLyData: 'get_cy_ly',
    getCyLyFocusData: 'get_cy_ly_focus',
    getGraph1: 'get_graph1',
    getGraph2: 'get_graph2',
    getGraph3: 'get_graph3',
    getGraph4: 'get_graph4',
    getGraph5: 'get_graph5',
    getGraph6: 'get_graph6',
    getGraph7: 'get_graph7',
    getGraph8: 'get_graph8',
    getAeName: 'get_ae_name',
    getJson: 'get_json',
    getDownloadBinderReport: 'get_download_binder_report',
    getDownloadAttendanceReport: 'get_download_attendance_report',
    getDownloadPdfReport: 'get_download_pdf_report',
    getMapdata: 'get_mapdata',
    getMonth: 'get_month',
    getTables: 'get_tables',
    getTableColumns: 'get_tables_column',
    getDownloadCSV: 'get_download_csv',
    submitSelectedProducts: 'submit_data',
    getAiInsights: 'get_ai_insights',
    getAiScopeOptions: 'get_ai_scope_options',
  },
  forgot: {
    action: 'forgot',
    module: 'forgot',
  },
  twoWay: {
    module: 'two_way',
    verifyOtp: {
      action: 'verify_otp',
    },
    resendOtp: {
      action: 'resend_otp',
    }
  },
  listing: {
    addData: 'add_data',
    deleteData: 'delete_data',
    deleteWithFormData: 'delete_with_form_data',
    deleteImage: 'delete_image',
    editData: 'edit_data',
    getData: 'get_data',
    getList: 'get_list',
    unlockData: 'unlock_data',
    restoreData: 'restore_data',
    getRoute: 'route_data',
  },
  login: {
    action: 'login',
    module: 'login',
  },
  logout: {
    action: 'logout',
    module: 'logout',
  },
};

export const LISTING = {
  display: [10, 20, 50, 100],
  mapKeys: {
    body: 'body',
    header: 'header',
    info: 'info',
    lg: 'lg',
    lt: 'lt',
    moreHeader: 'moreHeader',
    moreInfo: 'moreInfo',
    moreTableInfo: 'moreTableInfo',
    moreTabularHeader: 'moreTabularHeader'
  },
  noOfItemsPerColumn: 4,
};

export const MAP_DEFAULTS = {
  defaultZoom: 12,
  minZoom: 1,
  maxZoom: 22,
  fillColor: '#FF0000',
  icons: {
    START: 'start.png',
    BETWEEN: 'between.png',
    END: 'end.png',
    BLUE: 'blue-dot.png',
    GREEN: 'green-dot.png',
    ORANGE: 'orange-dot.png',
    PINK: 'pink-dot.png',
    PURPLE: 'purple-dot.png',
    RED: 'red-dot.png',
    YELLOW: 'yellow-dot.png',
    SPRINGGREEN: 'spring-green-dot.png',
    BLACK: 'black-dot.png',
    ROSE: 'rose-dot.png',
    GREY: 'grey-dot.png',
    MAGENTA: 'magenta-dot.png',
    AZURE: 'azure-dot.png',
    CYAN: 'cyan-dot.png',
    UNICON: 'unicon-marker.png',
    REDFLAG: 'red-flag.png',
  },
  strokeColor: '#FF0000',
  strokeWeight: 2,
  animationInterval: 1000,
  type: {
    HYBRID: 'hybrid',
    ROADMAP: 'roadmap',
    SATELLITE: 'satellite',
    TERRAIN: 'terrain'
  },
};

// In app.constants.ts (or any shared file)
export const MAP_STYLES = {
  DEFAULT: undefined,
  BLACK_WHITE: [
    {
      featureType: 'all', elementType: 'all', stylers: [
        { saturation: -100 }, { gamma: 0.2 }, { lightness: 4 }, { visibility: 'on' }
      ]
    }
  ],
  RETRO: [
    { elementType: 'geometry', stylers: [{ color: '#ebe3cd' }] },
    { elementType: 'labels.text.fill', stylers: [{ color: '#523735' }] },
    { elementType: 'labels.text.stroke', stylers: [{ color: '#f5f1e6' }] },
    { featureType: 'administrative', elementType: 'geometry.stroke', stylers: [{ color: '#c9b2a6' }] },
    { featureType: 'poi', elementType: 'geometry', stylers: [{ color: '#dfd2ae' }] }
  ],
  NIGHT: [
    { elementType: 'geometry', stylers: [{ color: '#242f3e' }] },
    { elementType: 'labels.text.stroke', stylers: [{ color: '#242f3e' }] },
    { elementType: 'labels.text.fill', stylers: [{ color: '#746855' }] },
    { featureType: 'administrative.locality', elementType: 'labels.text.fill', stylers: [{ color: '#d59563' }] }
  ],
  SILVER: [
    { elementType: 'geometry', stylers: [{ color: '#f5f5f5' }] },
    { elementType: 'labels.icon', stylers: [{ visibility: 'off' }] },
    { elementType: 'labels.text.fill', stylers: [{ color: '#616161' }] },
    { elementType: 'labels.text.stroke', stylers: [{ color: '#f5f5f5' }] },
    { featureType: 'road', elementType: 'geometry', stylers: [{ color: '#ffffff' }] }
  ],
  DARK: [
    { elementType: 'geometry', stylers: [{ color: '#212121' }] },
    { elementType: 'labels.icon', stylers: [{ visibility: 'off' }] },
    { elementType: 'labels.text.fill', stylers: [{ color: '#757575' }] },
    { elementType: 'labels.text.stroke', stylers: [{ color: '#212121' }] },
    { featureType: 'water', elementType: 'geometry', stylers: [{ color: '#000000' }] }
  ],
  AUBERGINE: [
    { elementType: 'geometry', stylers: [{ color: '#1d2c4d' }] },
    { elementType: 'labels.text.fill', stylers: [{ color: '#8ec3b9' }] },
    { elementType: 'labels.text.stroke', stylers: [{ color: '#1a3646' }] },
    { featureType: 'road', elementType: 'geometry', stylers: [{ color: '#304a7d' }] },
    { featureType: 'water', elementType: 'geometry', stylers: [{ color: '#0e1626' }] }
  ],
  GRAYSCALE: [
    { elementType: 'geometry', stylers: [{ color: '#f5f5f5' }] },
    { elementType: 'labels.text.fill', stylers: [{ color: '#616161' }] },
    { elementType: 'labels.text.stroke', stylers: [{ color: '#f5f5f5' }] },
    { featureType: 'road', elementType: 'geometry', stylers: [{ color: '#ffffff' }] },
    { featureType: 'water', elementType: 'geometry', stylers: [{ color: '#c9c9c9' }] }
  ],
  MIDNIGHT_BLUE: [
    { featureType: 'all', elementType: 'all', stylers: [{ invert_lightness: true }] },
    { featureType: 'road', elementType: 'all', stylers: [{ hue: '#0800ff' }] },
    { featureType: 'poi', elementType: 'all', stylers: [{ hue: '#1900ff' }] },
    { featureType: 'water', elementType: 'all', stylers: [{ hue: '#0008ff' }] }
  ],
  SEPIA_VINTAGE: [
    {
      featureType: 'all', elementType: 'all', stylers: [
        { saturation: -100 }, { lightness: 20 }, { gamma: 0.5 }
      ]
    },
    { featureType: 'water', elementType: 'all', stylers: [{ color: '#a0d3d3' }] }
  ],
  CUSTOM_BLUE: [
    {
      featureType: 'landscape', elementType: 'geometry.stroke', stylers: [
        { color: '#d2222c' }, { visibility: 'on' }
      ]
    },
    { featureType: 'water', elementType: 'geometry.stroke', stylers: [{ color: '#ff0000' }] }
  ],
  DARK_COMPLEX: [
    { featureType: 'all', elementType: 'geometry', stylers: [{ color: '#afd9f2' }] },
    {
      featureType: 'all', elementType: 'labels.text.fill', stylers: [
        { gamma: 0.01 }, { lightness: 20 }
      ]
    },
    {
      featureType: 'all', elementType: 'labels.text.stroke', stylers: [
        { saturation: -31 }, { lightness: -33 }, { weight: 2 }, { gamma: 0.8 }
      ]
    },
    {
      featureType: 'all', elementType: 'labels.icon', stylers: [
        { visibility: 'off' }, { saturation: 73 }, { color: '#df1010' }
      ]
    },
    {
      featureType: 'administrative.country', elementType: 'geometry.fill', stylers: [
        { visibility: 'on' }, { hue: '#00ff71' }
      ]
    },
    {
      featureType: 'landscape', elementType: 'geometry', stylers: [
        { lightness: 30 }, { saturation: 30 }
      ]
    },
    { featureType: 'poi', elementType: 'geometry', stylers: [{ saturation: 20 }] },
    {
      featureType: 'poi.park', elementType: 'geometry', stylers: [
        { lightness: 20 }, { saturation: -20 }
      ]
    },
    {
      featureType: 'road', elementType: 'geometry', stylers: [
        { lightness: 10 }, { saturation: -30 }
      ]
    },
    {
      featureType: 'road', elementType: 'geometry.stroke', stylers: [
        { saturation: 25 }, { lightness: 25 }
      ]
    },
    { featureType: 'water', elementType: 'all', stylers: [{ lightness: -20 }] }
  ],
  BEST_STYLE: [
    {
      featureType: "administrative",
      elementType: "all",
      stylers: [
        { visibility: "off" },
        { color: "#ffffff" }
      ]
    },
    {
      featureType: "landscape",
      elementType: "all",
      stylers: [
        { color: "#f2f2f2" }
      ]
    },
    {
      featureType: "road",
      elementType: "all",
      stylers: [
        { saturation: -100 },
        { lightness: 45 },
        { visibility: "simplified" }
      ]
    },
    {
      featureType: "water",
      elementType: "all",
      stylers: [
        { color: "#2997c6" },
        { visibility: "simplified" }
      ]
    }
  ],
  UBER_STYLE: [
    {
      featureType: "landscape.natural",
      elementType: "geometry",
      stylers: [
        { color: "#dde2e3" },
        { visibility: "on" }
      ]
    },
    {
      featureType: "poi.park",
      elementType: "geometry.fill",
      stylers: [
        { color: "#c6e8b3" },
        { visibility: "on" }
      ]
    },
    {
      featureType: "road",
      elementType: "geometry.fill",
      stylers: [
        { visibility: "on" }
      ]
    },
    {
      featureType: "road.highway",
      elementType: "geometry.fill",
      stylers: [
        { color: "#c1d1d6" },
        { visibility: "on" }
      ]
    },
    {
      featureType: "water",
      elementType: "geometry.fill",
      stylers: [
        { color: "#a6cbe3" },
        { visibility: "on" }
      ]
    }
  ],
  MIDNIGHT: [
    {
      featureType: "all",
      elementType: "all",
      stylers: [
        { invert_lightness: true }
      ]
    },
    {
      featureType: "road",
      elementType: "all",
      stylers: [
        { hue: "#0800ff" }
      ]
    },
    {
      featureType: "poi",
      elementType: "all",
      stylers: [
        { hue: "#1900ff" }
      ]
    },
    {
      featureType: "water",
      elementType: "all",
      stylers: [
        { hue: "#0008ff" }
      ]
    }
  ],
  GREEN_LANDSCAPE: [
    {
      featureType: "landscape",
      elementType: "all",
      stylers: [
        { saturation: 100 },
        { lightness: -50 },
        { hue: "#1aff00" },
        { gamma: 0.5 }
      ]
    },
    {
      featureType: "water",
      elementType: "geometry",
      stylers: [
        { lightness: 50 }
      ]
    }
  ],
  SEPIA: [
    {
      featureType: "all",
      elementType: "all",
      stylers: [
        { hue: "#ffbb00" },
        { saturation: 20 },
        { lightness: 20 }
      ],
      TBGSTILES: [
        { elementType: 'geometry', stylers: [{ color: '#e9e9e9' }] },
        { elementType: 'labels.icon', stylers: [{ visibility: 'off' }] },
        { elementType: 'labels.text.fill', stylers: [{ color: '#333333' }] },
        { elementType: 'labels.text.stroke', stylers: [{ color: '#ffffff' }] },
        { featureType: 'road', elementType: 'geometry', stylers: [{ color: '#bdbdbd' }] },
        { featureType: 'water', elementType: 'geometry', stylers: [{ color: '#9e9e9e' }] }
      ],
      STYLED: [
        { elementType: 'geometry', stylers: [{ color: '#f5f5f5' }] },
        { elementType: 'labels.icon', stylers: [{ visibility: 'off' }] },
        { elementType: 'labels.text.fill', stylers: [{ color: '#616161' }] },
        { elementType: 'labels.text.stroke', stylers: [{ color: '#ffffff' }] },
        { featureType: 'road', elementType: 'geometry', stylers: [{ color: '#ffffff' }] },
        { featureType: 'water', elementType: 'geometry', stylers: [{ color: '#a4b1c2' }] }
      ],
      PLAYFUL: [
        { elementType: 'geometry', stylers: [{ color: '#ffeb3b' }] },
        { elementType: 'labels.icon', stylers: [{ visibility: 'off' }] },
        { elementType: 'labels.text.fill', stylers: [{ color: '#000000' }] },
        { elementType: 'labels.text.stroke', stylers: [{ color: '#ffffff' }] },
        { featureType: 'road', elementType: 'geometry', stylers: [{ color: '#ff9800' }] },
        { featureType: 'water', elementType: 'geometry', stylers: [{ color: '#009688' }] }
      ]

    }
  ],
  PLAYFUL: [
    { elementType: 'geometry', stylers: [{ color: '#ffeb3b' }] },
    { elementType: 'labels.icon', stylers: [{ visibility: 'off' }] },
    { elementType: 'labels.text.fill', stylers: [{ color: '#000000' }] },
    { elementType: 'labels.text.stroke', stylers: [{ color: '#ffffff' }] },
    { featureType: 'road', elementType: 'geometry', stylers: [{ color: '#ff9800' }] },
    { featureType: 'water', elementType: 'geometry', stylers: [{ color: '#009688' }] }
  ]
};

export const GA_TRACKING_ID = 'G-1VDGG62Q2B';

export enum CHART_CONFIG {
  LINE = 0,
  COLUMN = 1,
  PIE = 2,
  STACK_VERTICAL_COLUMN = 3,
  GROUPED_VERTICAL_COLUMN = 4,
  STACK_AREA = 5,
  AREA = 6,
  STACK_HORIZONTAL_COLUMN = 7,
  ADVANCED_PIE = 8,
  APEX_PIE = 9,
  NORMALIZED_VERTICAL_COLUMN = 10,
  NORMALIZED_HORIZONTAL_COLUMN = 11,
  PIE_GRID = 12,
  HIGHCHART_STACKED_GROUPED_VERTICAL_COLUMN = 13
}

export const CHART_DEFAULTS = {
  DEFAULT_THEME: 'COOL',
  HEIGHT: 0,
  LEGEND_POSITION: LegendPosition.Below,  // right or below
  WIDTH: 0
};

export enum CONTROL_CONFIG {
  REC_ID = 1,
  DESC_BOX = 2,
  IMG_BOX = 3,
  INPUT_BOX = 4,
  SELECT_BOX = 5,
  DROPDOWN_BOX = 6,
  DATE_BOX = 7,
  RADIO_BOX = 8,
}

export enum EDIT_MODAL_ONCHANGE {
  PROJECTS = 1,
  CITY = 2,
  TEAMS = 3,
}

export enum USER_ACTION {
  EDIT = 1,
  DEL = 2,
  MAP = 3,
  DWN_IMG = 4,
  DEL_IMG = 5,
  UNLK = 6,
  RESTORE = 7,
  ADD = 8,
  CHG_TOKEN = 9,
  OPEN_LIST = 11,
  OPEN_STATS = 12,
  MARK_COMP = 13,
}

export enum ACTION {
  LIMIT = 1,
  SORT = 2,
  SEARCH = 3,
  BULK = 4,
  EXPORT_XLSX = 5,
  EXPORT_PDF = 6,
  PRINT_PDF = 7,
  ADD = 8,
  DOWNLOAD = 9,
  MODAL_BTN_CLICK = 10,
}

export const CARD_COLOR_ARRAY = ['c-red', 'c-blue', 'c-purple', 'c-green', 'c-yellow', 'c-skyblue', 'c-grey', 'c-orange'];

export const DATE_FORMAT = {
  YYYYMMDD: 'YYYY-MM-DD'
};
