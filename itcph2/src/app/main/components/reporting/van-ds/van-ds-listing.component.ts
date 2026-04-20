import { Component, OnDestroy, OnInit, ViewChild } from '@angular/core';
import { Subscription } from 'rxjs';
import { UntypedFormBuilder, UntypedFormGroup } from '@angular/forms';
import { finalize } from 'rxjs/operators';
import { TranslateService } from '@ngx-translate/core';

import { PaginationComponent } from 'src/app/shared/components/tables/pagination/pagination.component';
import { FormService } from 'src/app/core/services/form.service';
import { LocationOnMapModalService } from 'src/app/core/services/location-on-map-modal.service';
import { LoaderService } from 'src/app/core/services/loader.service';
import { LISTING, REQUEST_STATUS, STATIC_MODULES } from 'src/app/app.constants';
import { DashboardData, DropdownList, GetDownloadFileDetails, VanDsListing, VanDsListingData } from 'src/app/core/interfaces/http-response.interface';
import { environment } from 'src/environments/environment';
import { CsvDataFormat } from 'src/app/core/interfaces/helpers.interface';
import { Functions } from 'src/app/core/utils/functions.list';
import { ListingBulkActionOutput } from 'src/app/core/interfaces/helpers.interface';
import { ListingService } from 'src/app/core/services/listing.service';
import { CustomGalleryConfig } from 'src/app/core/interfaces/common.interface';
import { ToastrService } from 'src/app/core/services/toastr.service';
import { COMMON_VALIDATORS } from 'src/app/core/validators/validations.list';

@Component({
    templateUrl: './van-ds-listing.component.html',
    standalone: false
})
export class VanDsListingComponent implements OnDestroy, OnInit {
  @ViewChild('pagination', { static: false }) private pagination: PaginationComponent;
  private subscription: Subscription[] = [];
  tableData: VanDsListing[] = [];
  branchOptions: DropdownList[] = [];
  teamOptions: DropdownList[] = [];
  teamTypeOptions: DropdownList[] = [];
  rmdNameOptions: DropdownList[] = [];
  circleOptions: DropdownList[] = [];
  sectionOptions: DropdownList[] = [];
  wdCodeOptions: DropdownList[] = [];
  districtOptions: DropdownList[] = [];
  wdMarketOptions: DropdownList[] = [];
  wdPopGroupOptions: DropdownList[] = [];
  group: UntypedFormGroup;
  isSkeletonModeOn = false;
  isDownloading = false;
  totalRecords = 0;
  isMapAllowed = false;
  branchFilter = false;
  binderReportDownloadDays: number = null;
  transactionReportDownloadDays: number = null;
  summaryReportDownloadDays: number = null;
  skeletonArray = Array(5);
  cgConfig: CustomGalleryConfig = {
    showThumbnailText: false,
    thumbnailMaxHeight: '60px',
    thumbnailMaxWidth: '60px',
  };
  branchSelectError = 'err.branchError';
  dsTypeSelectError = 'err.dstypeError';
  showTransactionDownloadBtn = false;
  showSummaryDownloadBtn = false;

  errorMessages = {
    branch: COMMON_VALIDATORS.messages.requiredOnly('Branch'),
  };
  searchbarForm: UntypedFormGroup;

  constructor(private fb: UntypedFormBuilder, private formService: FormService, private listingService: ListingService,
    private locationOnMapModalService: LocationOnMapModalService, private loaderService: LoaderService,
    private toastr: ToastrService, private translate: TranslateService) { }

  ngOnInit() {
    this.group = this.fb.group({
      action: [''],
      limit: [''],
      page: [1],
      searchbar: this.fb.group({
        dateFrom: [''],
        dateTo: [''],
        branch: ['', COMMON_VALIDATORS.validators.requiredOnly],
        circle: [''],
        section: [''],
        wdCode: [''],
        dsType: [''],
        dsName: [''],
        district: [''],
        wdMarket: [''],
        wdPopGroup: [''],
      }),
      sort: [''],
    });

    this.searchbarForm = this.group.get('searchbar') as UntypedFormGroup;

    this.subscription.push(
      this.translate.get([this.branchSelectError, this.dsTypeSelectError])
        .subscribe(translatedMsg => {
          this.branchSelectError = translatedMsg[this.branchSelectError];
          this.dsTypeSelectError = translatedMsg[this.dsTypeSelectError];
        })
    );

    this.loaderService.startLoader();
    this.subscription.push(
      this.formService.getData<VanDsListingData>(environment.viewVanDsDataUrl)
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
            this.teamTypeOptions = resp.data.teamType;
            this.wdMarketOptions = resp.data.wdMarketList;
            this.wdPopGroupOptions = resp.data.wdPopGroupList;
            this.showTransactionDownloadBtn = resp.data.showTransactionDownloadBtn;
            this.showSummaryDownloadBtn = resp.data.showSummaryDownloadBtn;
            this.branchFilter = resp.data.branchFilter;
            this.binderReportDownloadDays = resp.data.binderReportDownloadDays;
            this.transactionReportDownloadDays = resp.data.transactionReportDownloadDays;
            this.summaryReportDownloadDays = resp.data.summaryReportDownloadDays;
            if (resp.data.userBranch) {
              this.group.get('searchbar').get('branch').setValue(resp.data.userBranch);
            }
          }
        })
    );

    this.isMapAllowed = this.listingService.isMapAllowed();
  }

  ngOnDestroy() {
    this.subscription.forEach(sub => sub.unsubscribe());
  }

  listingData() {
    this.tableData = [];
    this.isSkeletonModeOn = true;
    this.subscription.push(
      this.formService.getList<VanDsListingData<VanDsListing>>(environment.viewVanDsDataUrl, this.group.getRawValue())
        .pipe(
          finalize(() => this.isSkeletonModeOn = false),
        )
        .subscribe(resp => {
          if (resp && resp.status === REQUEST_STATUS.SUCCESS) {
            this.totalRecords = resp.data.total;
            this.tableData = resp.data.listingData;
          }
        })
    );
  }

  // eslint-disable-next-line @typescript-eslint/no-unused-vars
  onSearchAction($event: ListingBulkActionOutput) {
    this.pagination.changePage(1);
    this.totalRecords = 0;
    this.listingData();
  }

  downloadData() {
    const { dateFrom, dateTo } = this.group.getRawValue().searchbar || {};
    if (dateFrom && dateTo) {
      const fromDate = new Date(dateFrom.year, dateFrom.month - 1, dateFrom.day);
      const toDate = new Date(dateTo.year, dateTo.month - 1, dateTo.day);
      // Calculate difference in days
      const diffTime = toDate.getTime() - fromDate.getTime();
      const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

      if (diffDays + 1 > this.transactionReportDownloadDays) {
        this.toastr.toastr({
          type: 'error',
          msg: `You can only download this report for maximum ${this.transactionReportDownloadDays} days range`
        });
        return; //  stop execution
      }
    }

    if (this.branchValue && this.branchValue.length) {
      this.isDownloading = true;
      this.loaderService.startLoader();

      this.subscription.push(
        this.formService.customActionCall<GetDownloadFileDetails>(STATIC_MODULES.custom.getDownloadData,
          this.group.getRawValue(), null, environment.getListingExcelUrl)
          .pipe(
            finalize(() => {
              this.isDownloading = false;
              this.loaderService.stopLoader();
            }),
          )
          .subscribe(response => {
            if (response && response.status === REQUEST_STATUS.SUCCESS) {
              Functions.downloadFile(response.data.filePath, response.data.fileName);
            }
          })
      );
    } else {
      this.displayBranchError();
    }
  }

  downloadSummary() {
        const { dateFrom, dateTo } = this.group.getRawValue().searchbar || {};
    if (dateFrom && dateTo) {
      const fromDate = new Date(dateFrom.year, dateFrom.month - 1, dateFrom.day);
      const toDate = new Date(dateTo.year, dateTo.month - 1, dateTo.day);
      // Calculate difference in days
      const diffTime = toDate.getTime() - fromDate.getTime();
      const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

      if (diffDays + 1 > this.summaryReportDownloadDays) {
        this.toastr.toastr({
          type: 'error',
          msg: `You can only download this report for maximum ${this.summaryReportDownloadDays} days range`
        });
        return; //  stop execution
      }
    }

    if (this.branchValue && this.branchValue.length) {
      this.isDownloading = true;
      this.loaderService.startLoader();

      this.subscription.push(
        this.formService.customActionCall<GetDownloadFileDetails>(STATIC_MODULES.custom.getDownloadSummary,
          this.group.getRawValue(), null, environment.getListingExcelUrl)
          .pipe(
            finalize(() => {
              this.isDownloading = false;
              this.loaderService.stopLoader();
            }),
          )
          .subscribe(response => {
            if (response && response.status === REQUEST_STATUS.SUCCESS) {
              Functions.downloadFile(response.data.filePath, response.data.fileName);
            }
          })
      );
    } else {
      this.displayBranchError();
    }
  }

  downloadBinderReport() {
    const { dateFrom, dateTo } = this.group.getRawValue().searchbar || {};

    if (dateFrom && dateTo) {
      const fromDate = new Date(dateFrom.year, dateFrom.month - 1, dateFrom.day);
      const toDate = new Date(dateTo.year, dateTo.month - 1, dateTo.day);
      // Calculate difference in days
      const diffTime = toDate.getTime() - fromDate.getTime();
      const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

      if (diffDays + 1 > this.binderReportDownloadDays) {
        this.toastr.toastr({
          type: 'error',
          msg: `You can only download this report for maximum ${this.binderReportDownloadDays} days range`
        });
        return; //  stop execution
      }
    }

    //  if date validation passes
    if (this.branchValue && this.branchValue.length) {
      this.isDownloading = true;
      this.loaderService.startLoader();

      this.subscription.push(
        this.formService.customActionCall<CsvDataFormat>(
          STATIC_MODULES.custom.getDownloadBinderReport,
          this.group.getRawValue(),
          null,
          environment.getListingExcelUrl
        )
          .pipe(
            finalize(() => {
              this.isDownloading = false;
              this.loaderService.stopLoader();
            }),
          )
          .subscribe(response => {
            if (response && response.status === REQUEST_STATUS.SUCCESS) {
              Functions.createCSV(response.data);
            }
          })
      );
    } else {
      this.displayBranchError();
    }
  }

  downloadPDF() {
    const hasBranch = this.branchValue && this.branchValue.length;
    const hasDsType = this.dsTypeValue && this.dsTypeValue.length;
    if (hasBranch && hasDsType) {
      this.isDownloading = true;
      this.loaderService.startLoader();

      this.subscription.push(
        this.formService.customActionCall<GetDownloadFileDetails>(STATIC_MODULES.custom.getDownloadPdfReport,
          this.group.getRawValue(), null, environment.getListingExcelUrl)
          .pipe(
            finalize(() => {
              this.isDownloading = false;
              this.loaderService.stopLoader();
            }),
          )
          .subscribe(response => {
            if (response && response.status === REQUEST_STATUS.SUCCESS) {
              Functions.downloadFile(response.data.filePath, response.data.fileName);
            }
          })
      );
    } else {
      if (!hasBranch) {
        this.displayBranchError();
      }
      if (!hasDsType) {
        this.displayDSError();
      }
    }
  }

  showLocationOnMap(lt: number, lg: number) {
    this.locationOnMapModalService.show({
      [LISTING.mapKeys.lt]: lt,
      [LISTING.mapKeys.lg]: lg,
    });
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
      this.formService.customActionCall<DashboardData>(STATIC_MODULES.custom.getBranch, { district: this.group.get('searchbar').get('district').value }, null, environment.viewVanDsDataUrl)
        .pipe(finalize(() => this.loaderService.stopLoader()))
        .subscribe(resp => {
          if (resp && resp.status === REQUEST_STATUS.SUCCESS) {
            this.branchOptions = resp.data.branchList;
            this.circleOptions = resp.data.circleList;
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
      this.formService.customActionCall<DashboardData>(STATIC_MODULES.custom.getCircle, { branch: this.group.get('searchbar').get('branch').value }, null, environment.viewVanDsDataUrl)
        .pipe(finalize(() => this.loaderService.stopLoader()))
        .subscribe(resp => {
          if (resp && resp.status === REQUEST_STATUS.SUCCESS) {
            this.circleOptions = resp.data.circleList;
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

  getSection() {
    this.sectionValue = null;
    this.wdCodeValue = null;
    this.dsTypeValue = null;
    this.dsNameValue = null;
    this.wdMarketValue = null;
    this.wdPopGroupValue = null;
    this.loaderService.startLoader();
    this.subscription.push(
      this.formService.customActionCall<DashboardData>(STATIC_MODULES.custom.getSection, { branch: this.group.get('searchbar').get('branch').value, circle: this.group.get('searchbar').get('circle').value }, null, environment.viewVanDsDataUrl)
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
      this.formService.customActionCall<DashboardData>(STATIC_MODULES.custom.getWDList, { branch: this.group.get('searchbar').get('branch').value, circle: this.group.get('searchbar').get('circle').value, section: this.group.get('searchbar').get('section').value }, null, environment.viewVanDsDataUrl)
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
      this.formService.customActionCall<DashboardData>(STATIC_MODULES.custom.getTeamsTypeList, { branch: this.group.get('searchbar').get('branch').value, circle: this.group.get('searchbar').get('circle').value, section: this.group.get('searchbar').get('section').value, wdCode: this.group.get('searchbar').get('wdCode').value }, null, environment.viewVanDsDataUrl)
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
      this.formService.customActionCall<DashboardData>(STATIC_MODULES.custom.getTeamsList, { branch: this.group.get('searchbar').get('branch').value, circle: this.group.get('searchbar').get('circle').value, section: this.group.get('searchbar').get('section').value, wdCode: this.group.get('searchbar').get('wdCode').value, dsType: this.group.get('searchbar').get('dsType').value }, null, environment.viewVanDsDataUrl)
        .pipe(finalize(() => this.loaderService.stopLoader()))
        .subscribe(resp => {
          if (resp && resp.status === REQUEST_STATUS.SUCCESS) {
            this.teamOptions = resp.data.teamList;
          }
        })
    );
  }

  displayBranchError() {
    this.toastr.toastr({ type: 'error', msg: this.branchSelectError });
  }

  displayDSError() {
    this.toastr.toastr({ type: 'error', msg: this.dsTypeSelectError });
  }

  get branchValue() {
    return this.group && this.group.get('searchbar').get('branch').value;
  }

  set branchValue(value: string) {
    this.branchOptions = [];
    this.group.get('searchbar').get('branch').setValue(value);
  }
  set circleValue(value: string) {
    this.circleOptions = [];
    this.group.get('searchbar').get('circle').setValue(value);
  }
  set sectionValue(value: string) {
    this.sectionOptions = [];
    this.group.get('searchbar').get('section').setValue(value);
  }
  set wdCodeValue(value: string) {
    this.wdCodeOptions = [];
    this.group.get('searchbar').get('wdCode').setValue(value);
  }

  get dsTypeValue() {
    return this.group && this.group.get('searchbar').get('dsType').value;
  }

  set dsTypeValue(value: string) {
    this.teamTypeOptions = [];
    this.group.get('searchbar').get('dsType').setValue(value);
  }
  set dsNameValue(value: string) {
    this.teamOptions = [];
    this.group.get('searchbar').get('dsName').setValue(value);
  }
  set wdMarketValue(value: string) {
    this.wdMarketOptions = [];
    this.group.get('searchbar').get('wdMarket').setValue(value);
  }
  set wdPopGroupValue(value: string) {
    this.wdPopGroupOptions = [];
    this.group.get('searchbar').get('wdPopGroup').setValue(value);
  }

  get limit() {
    return this.group.get('limit').value || LISTING.display[0];
  }
  clearForm() {
    this.group.reset();
  }
}
