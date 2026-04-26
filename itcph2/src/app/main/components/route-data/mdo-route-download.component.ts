import { Component, OnDestroy, OnInit, ViewChild } from '@angular/core';
import { Subscription } from 'rxjs';
import { UntypedFormBuilder, UntypedFormGroup } from '@angular/forms';
import { finalize } from 'rxjs/operators';
import { TranslateService } from '@ngx-translate/core';

import { PaginationComponent } from 'src/app/shared/components/tables/pagination/pagination.component';
import { FormService } from 'src/app/core/services/form.service';
import { LocationOnMapModalService } from 'src/app/core/services/location-on-map-modal.service';
import { LoaderService } from 'src/app/core/services/loader.service';
import { REQUEST_STATUS, STATIC_MODULES } from 'src/app/app.constants';
import { DashboardData, DropdownList, MdoListing, VanDsListingData } from 'src/app/core/interfaces/http-response.interface';
import { environment } from 'src/environments/environment';
import { Functions } from 'src/app/core/utils/functions.list';
import { CsvDataFormat } from 'src/app/core/interfaces/helpers.interface';
import { ListingService } from 'src/app/core/services/listing.service';
import { CustomGalleryConfig } from 'src/app/core/interfaces/common.interface';
import { ToastrService } from 'src/app/core/services/toastr.service';
import { COMMON_VALIDATORS } from 'src/app/core/validators/validations.list';

@Component({
  templateUrl: './mdo-route-download.component.html',
  standalone: false
})
export class MdoDownloadRouteComponent implements OnDestroy, OnInit {
  @ViewChild('pagination', { static: false }) private pagination!: PaginationComponent;
  private subscription: Subscription[] = [];
  tableData: MdoListing[] = [];
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
  group!: UntypedFormGroup;
  isSkeletonModeOn = false;
  isDownloading = false;
  totalRecords = 0;
  isMapAllowed = false;
  branchFilter = false;
  skeletonArray = Array(5);
  cgConfig: CustomGalleryConfig = {
    showThumbnailText: false,
    thumbnailMaxHeight: '60px',
    thumbnailMaxWidth: '60px',
  };
  branchSelectError = 'err.branchError';
  showTransactionDownloadBtn = false;
  showSummaryDownloadBtn = false;

  errorMessages = {
    branch: COMMON_VALIDATORS.messages.requiredOnly('Branch'),
  };
  searchbarForm!: UntypedFormGroup;

  constructor(private fb: UntypedFormBuilder, private formService: FormService, private listingService: ListingService,
    private locationOnMapModalService: LocationOnMapModalService, private loaderService: LoaderService,
    private toastr: ToastrService, private translate: TranslateService) { }

  ngOnInit() {
    this.group = this.fb.group({
      searchbar: this.fb.group({
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
    });

    this.searchbarForm = this.group.get('searchbar') as UntypedFormGroup;

    this.subscription.push(
      this.translate.get(this.branchSelectError)
        .subscribe(translatedMsg => {
          this.branchSelectError = translatedMsg;
        })
    );

    this.loaderService.startLoader();
    this.subscription.push(
      this.formService.getData<VanDsListingData>(environment.viewVanDsDataUrl)
        .pipe(
          finalize(() => this.loaderService.stopLoader()),
        )
        .subscribe(resp => {
          if (resp && resp.status === REQUEST_STATUS.SUCCESS && resp.data) {
            this.districtOptions = resp.data.districtList;
            this.branchOptions = resp.data.branchList;
            this.circleOptions = resp.data.circleList;
            this.sectionOptions = resp.data.sectionList;
            this.wdCodeOptions = resp.data.wdCodeList;
            this.teamOptions = resp.data.teamList;
            this.teamTypeOptions = resp.data.teamType;
            this.wdMarketOptions = resp.data.wdMarketList;
            this.wdPopGroupOptions = resp.data.wdPopGroupList;
            this.showTransactionDownloadBtn = resp.data.showTransactionDownloadBtn ?? false;
            this.showSummaryDownloadBtn = resp.data.showSummaryDownloadBtn ?? false;
            this.branchFilter = resp.data.branchFilter ?? false;
            if (resp.data.userBranch) {
              this.group.get('searchbar')?.get('branch')?.setValue(resp.data.userBranch);
            }
          }
        })
    );

    this.isMapAllowed = this.listingService.isMapAllowed();
  }

  ngOnDestroy() {
    this.subscription.forEach(sub => sub.unsubscribe());
  }

  downloadData() {
    if (this.branchValue && this.branchValue.length) {
      this.isDownloading = true;
      this.loaderService.startLoader();

      this.subscription.push(
        this.formService.customActionCall<CsvDataFormat>(STATIC_MODULES.custom.getDownloadData,
          this.group.getRawValue(), null, environment.getListingExcelUrl)
          .pipe(
            finalize(() => {
              this.isDownloading = false;
              this.loaderService.stopLoader();
            }),
          )
          .subscribe(response => {
            if (response && response.status === REQUEST_STATUS.SUCCESS && response.data) {
              Functions.createCSV(response.data);
            }
          })
      );
    } else {
      this.displayBranchError();
    }
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
      this.formService.customActionCall<DashboardData>(STATIC_MODULES.custom.getBranch, { district: this.group.get('searchbar')?.get('district')?.value }, null, environment.viewVanDsDataUrl)
        .pipe(finalize(() => this.loaderService.stopLoader()))
        .subscribe(resp => {
          if (resp && resp.status === REQUEST_STATUS.SUCCESS && resp.data) {
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
      this.formService.customActionCall<DashboardData>(STATIC_MODULES.custom.getCircle, { branch: this.group.get('searchbar')?.get('branch')?.value }, null, environment.viewVanDsDataUrl)
        .pipe(finalize(() => this.loaderService.stopLoader()))
        .subscribe(resp => {
          if (resp && resp.status === REQUEST_STATUS.SUCCESS && resp.data) {
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
      this.formService.customActionCall<DashboardData>(STATIC_MODULES.custom.getSection, { branch: this.group.get('searchbar')?.get('branch')?.value, circle: this.group.get('searchbar')?.get('circle')?.value }, null, environment.viewVanDsDataUrl)
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
      this.formService.customActionCall<DashboardData>(STATIC_MODULES.custom.getWDList, { branch: this.group.get('searchbar')?.get('branch')?.value, circle: this.group.get('searchbar')?.get('circle')?.value, section: this.group.get('searchbar')?.get('section')?.value }, null, environment.viewVanDsDataUrl)
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
      this.formService.customActionCall<DashboardData>(STATIC_MODULES.custom.getTeamsTypeList, { branch: this.group.get('searchbar')?.get('branch')?.value, circle: this.group.get('searchbar')?.get('circle')?.value, section: this.group.get('searchbar')?.get('section')?.value, wdCode: this.group.get('searchbar')?.get('wdCode')?.value }, null, environment.viewVanDsDataUrl)
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
      this.formService.customActionCall<DashboardData>(STATIC_MODULES.custom.getTeamsList, { branch: this.group.get('searchbar')?.get('branch')?.value, circle: this.group.get('searchbar')?.get('circle')?.value, section: this.group.get('searchbar')?.get('section')?.value, wdCode: this.group.get('searchbar')?.get('wdCode')?.value, dsType: this.group.get('searchbar')?.get('dsType')?.value }, null, environment.viewVanDsDataUrl)
        .pipe(finalize(() => this.loaderService.stopLoader()))
        .subscribe(resp => {
          if (resp && resp.status === REQUEST_STATUS.SUCCESS && resp.data) {
            this.teamOptions = resp.data.teamList;
          }
        })
    );
  }

  displayBranchError() {
    this.toastr.toastr({ type: 'error', msg: this.branchSelectError });
  }

  get branchValue() {
    return this.group && this.group.get('searchbar')?.get('branch')?.value;
  }

  set branchValue(value: string | null) {
    this.branchOptions = [];
    this.group.get('searchbar')?.get('branch')?.setValue(value);
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

  clearForm() {
    this.group.reset();
  }
}
