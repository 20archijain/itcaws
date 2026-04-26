import { Component, EventEmitter, Input, OnDestroy, OnInit, Output, TemplateRef, ViewChild } from '@angular/core';
import { Subscription } from 'rxjs';
import { finalize } from 'rxjs/operators';
import { UntypedFormBuilder, UntypedFormControl, UntypedFormGroup } from '@angular/forms';
import { TranslateService } from '@ngx-translate/core';
import * as XLSX from 'xlsx';
import jsPDF from 'jspdf';
import autoTable from 'jspdf-autotable';

import { FormService } from 'src/app/core/services/form.service';
import { ACTION, LISTING, REQUEST_STATUS, STATIC_MODULES, USER_ACTION } from 'src/app/app.constants';
import { DropdownList, GetDownloadFileDetails, GetTableListingResponse } from 'src/app/core/interfaces/http-response.interface';
import {
  ChekboxOutput, CsvDataFormat, EditConfig, EditModalOutput,
  ListingActions, ListingBulkActionOutput, ListingExtraButtons
} from 'src/app/core/interfaces/helpers.interface';
import { ListingService } from 'src/app/core/services/listing.service';
import { PaginationComponent } from '../pagination/pagination.component';
import { ConfirmationModalService } from 'src/app/core/services/confirmation-modal.service';
import { RoutingService } from 'src/app/core/services/routing.service';
import { EditModalService } from 'src/app/core/services/edit-modal.service';
import { LocationOnMapModalService } from 'src/app/core/services/location-on-map-modal.service';
import { CustomGalleryConfig } from 'src/app/core/interfaces/common.interface';
import { Functions } from 'src/app/core/utils/functions.list';
import { environment } from 'src/environments/environment';
import { LoaderService } from 'src/app/core/services/loader.service';

@Component({
  selector: 'app-new-table-listing',
  templateUrl: './table-listing.new.component.html',
  standalone: false,
})
export class TableListingNewComponent implements OnInit, OnDestroy {
  @ViewChild('pagination', { static: false }) private pagination!: PaginationComponent;
  @Input() private inlineEdit = true;
  @Input() private editUrl?: string;
  private oldSortKey?: string;
  private subscription: Subscription[] = [];
  private actionData: any = null;
  @Output() private onClear = new EventEmitter();
  @Output() listingAction = new EventEmitter();
  @Input() heading = '';
  @Input() hideSearchbar = false;
  @Input() callListingApi = true;
  @Input() showPagination = true;
  @Input() apiListingKey = 'data0';
  @Input() isSelectable = true;
  @Input() url?: string;
  @Input() checkKey = '';
  @Input() deleteKey = '';
  @Input() editKey = 'id';
  // @Input() private openListingKey = 'openListingUrl';
  // @Input() private openStatsUrl: string = null;
  @Input() sortOptions: DropdownList[] = [];
  @Input() header: string[] = [];
  @Input() body: string[] = [];
  @Input() isSortable = true;
  @Input() searchTemplate?: TemplateRef<any>;
  @Input() searchGroup?: UntypedFormGroup;
  @Input() fixedTableHeader = true;
  @Input() smallTable = false;
  @Input() columnWrap = false;
  @Input() alignMiddle = true;
  @Input() dateColumnSortIndex: number[] = [];
  @Input() timeColumnSortIndex: number[] = [];
  @Input() downloadColumnIndex: number[] = [];
  @Input() isExpandable = false;
  @Input() expandableTemplate?: TemplateRef<any>;
  @Input() gallery = false;
  @Input() galleryKey = 'images';
  @Input() cgConfig?: CustomGalleryConfig | null = null;
  @Input() thumbnailNoWrap = false;
  @Input() editConfig: EditConfig[] = [];
  @Input() editLabel = '';
  @Input() editModalSize = 'modal-xl';
  @Input() tableClass = 'table-hover';
  @Input() tableData: any[] = [];
  @Input() totalRecords = 0;
  @Input() isSkeletonModeOn = false;
  @Input() clickableTableColumns = {};
  @Input() editCondition = null;
  @Input() unlockCondition?: [string, boolean];
  @Input() markCompleteCondition?: [string, boolean];
  @Input() deleteCondition?: [string, number];
  @Input() getListingOnInit = true;
  @Input() extraButtons: ListingExtraButtons[] = [];
  @Input() downloadTemplate?: TemplateRef<any>;
  @Input() showDownloadDataBtn = false;
  @Input() showExportTableToXlsxBtn = true;
  @Input() showExportTableToPdfBtn = true;
  @Input() showPrintBtn = true;
  @Input() downloadDataBtnTitle = 'button.export';
  @Input() showDownloadSummaryBtn = false;
  @Input() downloadSummaryBtnTitle = 'button.downloadSummary';
  @Input() downloadDataUrl = environment.downloadDataUrl;
  @Input() addLabel = '';
  @Input() addConfig: EditConfig[] = [];
  headingText = '';
  headersText: string[] = [];
  isDownloadDataBtnDisabled = false;
  isDownloadSummaryBtnDisabled = false;
  clickedIndex = -1;
  actions: (ListingActions | undefined)[] = [];
  singleActions: (ListingActions | undefined)[] = [];
  multiActions: (ListingActions | undefined)[] = [];
  selectedRecords: any[] = [];
  isAllChecked = false;
  isSorted = false;
  sortOrder = false;
  group!: UntypedFormGroup;
  searchbarGroup?: UntypedFormGroup;
  sketetonList = Array(5);

  constructor(private fb: UntypedFormBuilder, private formService: FormService, private listingService: ListingService,
    private confirmationModalService: ConfirmationModalService, private routerService: RoutingService,
    private editModalService: EditModalService, private locationOnMapModalService: LocationOnMapModalService,
    private loaderService: LoaderService, private translate: TranslateService) { }

  private extractResponseData(data: any) {
    let extractedData: any;
    const keys = this.apiListingKey.split('.');
    keys.forEach(key => {
      extractedData = extractedData ? extractedData[key] : data[key];
    });

    return extractedData;
  }

  ngOnInit() {
    this.group = this.fb.group({
      action: [''],
      limit: [''],
      localSearch: [''],
      page: [1],
      sort: ['']
    });

    // create searchbar control
    if (!this.hideSearchbar) {
      if (this.searchTemplate) {
        this.group.addControl('searchbar', this.searchGroup);
      } else {
        this.group.addControl('searchbar', new UntypedFormGroup({
          date: new UntypedFormControl()
        }));
      }
      this.searchbarGroup = this.group.get('searchbar') as UntypedFormGroup;
    }

    if (this.getListingOnInit && this.callListingApi) {
      this.getListing();
    }

    this.actions = this.listingService.getModuleActions();
    this.singleActions = this.actions.filter(action => !!(action && action.allowSingle));
    this.multiActions = this.actions.filter(action => !!(action && action.allowMulti));

    // subscribe to confirmation modal
    this.subscription.push(
      this.confirmationModalService.modal()
        .subscribe(resp => {
          if (!resp.goBackGuard && !resp.show) {
            this.group.get('action')?.setValue('');

            // Delete action chosen
            if (this.confirmationModalService.data === 'modal.confirmation.delete') {
              // user confirms delete
              if (resp.data && this.actionData !== null) {
                this.onDeleteConfirm();
              }
            } else if (this.confirmationModalService.data === 'modal.confirmation.unlock') {
              // user confirms unlock
              if (resp.data && this.actionData !== null) {
                this.onUnlockConfirm();
              }
            } else if (this.confirmationModalService.data === 'modal.confirmation.restore') {
              // Restore action chosen

              // user confirms Restore
              if (resp.data && this.actionData !== null) {
                this.onRestoreConfirm();
              }
            } else if (this.confirmationModalService.data === 'modal.confirmation.change_token') {
              // Change token chosen

              // user confirms
              if (resp.data && this.actionData !== null) {
                this.onChangeTokenConfirm();
              }
            }
          }
        })
    );

    // refresh listing
    this.subscription.push(
      this.listingService.onRefreshListing()
        .subscribe(() => this.getListing())
    );

    if (this.heading) {
      this.subscription.push(
        this.translate.get(this.heading)
          .subscribe(translatedMsg => this.headingText = translatedMsg)
      );
    }
  }

  ngOnDestroy() {
    this.subscription.forEach(sub => sub.unsubscribe());
  }

  getListing() {
    if (this.extraButtons && this.extraButtons.length) {
      this.extraButtons.forEach(extraButton => extraButton.showTemplate = -1);
    }
    this.tableData = [];
    this.isSkeletonModeOn = true;
    this.clickedIndex = -1;

    this.subscription.push(
      this.formService.getList<GetTableListingResponse>(this.url, this.group.getRawValue())
        .pipe(
          finalize(() => this.isSkeletonModeOn = false),
        )
        .subscribe(resp => {
          if (resp && resp.status === REQUEST_STATUS.SUCCESS) {
            const data: any[] = this.extractResponseData(resp.data);
            this.totalRecords = data.splice(data.length - 1, 1)[0]['total'];
            this.tableData = data;
          }
        })
    );
  }

  // sort data based on column clicked
  sortData(index: number) {
    if (this.isSortable) {
      this.isSorted = true;
      const sortKey = this.body[index];
      if (sortKey !== this.oldSortKey) {
        this.oldSortKey = sortKey;
        // ascending
        this.sortOrder = true;
      }
      const isDateColumn = this.dateColumnSortIndex && this.dateColumnSortIndex.indexOf(index) > -1 ? true : false;
      const isTimeColumn = this.timeColumnSortIndex && this.timeColumnSortIndex.indexOf(index) > -1 ? true : false;

      const sortData = this.tableData.sort((a, b) => {
        if (this.sortOrder && (a || b)) {
          // if date or datetime column
          if (isDateColumn) {
            const date1 = new Date(Functions.getValidDatetimeForSorting(a[sortKey])).getTime();
            const date2 = new Date(Functions.getValidDatetimeForSorting(b[sortKey])).getTime();

            return date1 - date2;
          } else if (isTimeColumn) {
            // if time column
            const date1 = new Date('1970/01/01 ' + a[sortKey]).getTime();
            const date2 = new Date('1970/01/01 ' + b[sortKey]).getTime();

            return date1 - date2;
          } else {
            if (typeof a[sortKey] === 'number' ||
              (!Number.isNaN(parseInt(a[sortKey], 10)) && typeof parseInt(a[sortKey], 10) === 'number')) {
              return a[sortKey] - b[sortKey];
            } else {
              return a[sortKey] > b[sortKey] ? 1 : -1;
            }
          }
        } else {
          // if date or datetime column
          if (isDateColumn) {
            const date1 = new Date(Functions.getValidDatetimeForSorting(b[sortKey])).getTime();
            const date2 = new Date(Functions.getValidDatetimeForSorting(a[sortKey])).getTime();

            return date1 - date2;
          } else if (isTimeColumn) {
            // if time column
            const date1 = new Date('1970/01/01 ' + b[sortKey]).getTime();
            const date2 = new Date('1970/01/01 ' + a[sortKey]).getTime();

            return date1 - date2;
          } else {
            if (typeof b[sortKey] === 'number' ||
              (!Number.isNaN(parseInt(b[sortKey], 10)) && typeof parseInt(b[sortKey], 10) === 'number')) {
              return b[sortKey] - a[sortKey];
            } else {
              return b[sortKey] > a[sortKey] ? 1 : -1;
            }
          }
        }
      });
      this.tableData = [...sortData];

      this.sortOrder = !this.sortOrder;
    }
  }

  get limit() {
    return this.group.get('limit')?.value || LISTING.display[0];
  }

  get searchField() {
    return this.group.get('search');
  }

  // check/uncheck clicked record
  isChecked(data: any) {
    return this.listingService.isChecked(data, this.selectedRecords, this.checkKey);
  }

  // get the record id based on which check/uncheck is done
  getCheckedKeyValue(data: any) {
    return this.listingService.getCheckedKeyValue(data, this.deleteKey || this.checkKey);
  }

  // get all checked records
  emitSelectedRecords($event: ChekboxOutput) {
    this.selectedRecords = $event.selectedRecords;
    this.isAllChecked = $event.isAllSelected;
  }

  // reset values and get listing called on page change, limit change, delete or edit confirm
  onSearch(resetPage = true) {
    if (resetPage && this.pagination) {
      this.pagination.changePage(1);
      if (this.callListingApi) {
        this.totalRecords = 0;
      }
    }
    this.isSorted = false;
    this.isAllChecked = false;
    this.selectedRecords = [];
    if (this.callListingApi) {
      this.getListing();
    } else {
      this.listingAction.emit();
    }
  }

  // called on action triggered in searchbar
  onSearchAction($event: ListingBulkActionOutput) {
    switch ($event.type) {
      // on limit change
      case ACTION.LIMIT:
      // on sort
      // eslint-disable-next-line no-fallthrough
      case ACTION.SORT:
      // on sebar search
      // eslint-disable-next-line no-fallthrough
      case ACTION.SEARCH:
        if (this.searchTemplate && this.group && this.group.get('searchbar') && this.group.get('searchbar')?.valid) {
          this.onSearch();
        }
        break;
      // on bulk action dropdown
      // case ACTION.BULK:
      //   this.onBulkAction($event.action);
      //   break;
      case ACTION.EXPORT_XLSX:
        this.exportTableToXlsx();
        break;
      case ACTION.EXPORT_PDF:
        this.exportTableToPdf();
        break;
      case ACTION.PRINT_PDF:
        this.printPdf();
        break;
      // case ACTION.ADD:
      //   this.displayAddModal();
      //   break;
    }
  }

  // called when clicked on any action displayed under Action dropdown
  // onBulkAction(action: ListingActions) {
  //   this.onActionClick(action || JSON.parse(this.group.get('action').value));
  // }

  // onActionClick(action: ListingActions, data = null) {
  //   if (action) {
  //     switch (action.id) {
  //       // edit
  //       case USER_ACTION.EDIT:
  //         if (this.inlineEdit) {
  //           this.editModalService.show(true, this.editConfig, this.editLabel, data);
  //         } else {
  //           this.routerService.navigate(this.editUrl, [data[this.editKey]]);
  //         }
  //         break;
  //       // delete
  //       case USER_ACTION.DEL:
  //         this.confirmationModalService.show('modal.confirmation.delete', 'bin.json');
  //         this.actionData = data ? this.getCheckedKeyValue(data) : this.selectedRecords;
  //         break;
  //       // Unlock
  //       case USER_ACTION.UNLK:
  //         this.confirmationModalService.show('modal.confirmation.unlock');
  //         this.actionData = data ? this.getCheckedKeyValue(data) : this.selectedRecords;
  //         break;
  //       // Map
  //       case USER_ACTION.MAP:
  //         if (data && data[LISTING.mapKeys.lt]) {
  //           this.locationOnMapModalService.show({
  //             [LISTING.mapKeys.lt]: data[LISTING.mapKeys.lt],
  //             [LISTING.mapKeys.lg]: data[LISTING.mapKeys.lg],
  //           });
  //         }
  //         break;
  //       case USER_ACTION.RESTORE:
  //         this.confirmationModalService.show('modal.confirmation.restore');
  //         this.actionData = data ? this.getCheckedKeyValue(data) : this.selectedRecords;
  //         break;
  //       case USER_ACTION.CHG_TOKEN:
  //         this.confirmationModalService.show('modal.confirmation.change_token');
  //         this.actionData = data ? this.getCheckedKeyValue(data) : this.selectedRecords;
  //         break;
  //       case USER_ACTION.OPEN_LIST:
  //         this.routerService.navigate(data[this.openListingKey], [data[this.checkKey]]);
  //         break;
  //       case USER_ACTION.OPEN_STATS:
  //         this.routerService.navigate(this.openStatsUrl, [data[this.checkKey]]);
  //         break;
  //       case USER_ACTION.MARK_COMP:
  //         this.confirmationModalService.show('modal.confirmation.mark_complete');
  //         this.actionData = data ? this.getCheckedKeyValue(data) : this.selectedRecords;
  //         break;
  //     }
  //   }
  // }

  onClearFilters() {
    if (this.group.get('searchbar')) {
      this.group.get('searchbar')?.reset();
      this.onClear.emit();
    }
  }

  // delete record
  onDeleteConfirm() {
    this.subscription.push(
      this.formService.deleteData(this.url, this.actionData)
        .subscribe(resp => {
          if (resp && resp.status === REQUEST_STATUS.SUCCESS) {
            this.onSearch();
            this.actionData = null;
          }
        })
    );
  }

  // restore record
  onRestoreConfirm() {
    this.subscription.push(
      this.formService.restoreData(this.url, this.actionData)
        .subscribe(resp => {
          if (resp && resp.status === REQUEST_STATUS.SUCCESS) {
            this.onSearch();
            this.actionData = null;
          }
        })
    );
  }

  // change token for record
  onChangeTokenConfirm() {
    this.subscription.push(
      this.formService.customActionCall(STATIC_MODULES.custom.changeToken, this.actionData, null, this.url)
        .subscribe(resp => {
          if (resp && resp.status === REQUEST_STATUS.SUCCESS) {
            this.onSearch();
            this.actionData = null;
          }
        })
    );
  }

  // Unlock record(s)
  onUnlockConfirm() {
    this.subscription.push(
      this.formService.customActionCall(STATIC_MODULES.listing.unlockData, { ids: this.actionData }, null, this.url)
        .subscribe(resp => {
          if (resp && resp.status === REQUEST_STATUS.SUCCESS) {
            this.onSearch(false);
            this.actionData = null;
          }
        })
    );
  }

  // called on edit modal button click
  onEdit($event: EditModalOutput) {
    // edit confirm
    if ($event.status) {
      this.onSearch(false);
    }
  }

  isActionAllowed(action: ListingActions, data: any) {
    let isActionAllowed = true;

    // if value is present and record is deleted, Don't allow any action except Restore
    // if value is present and record is not deleted, Don't allow restore option
    // if value is not present, Don't allow restore option
    const delVariableNameToCheck: string = this.deleteCondition?.length ? this.deleteCondition[0] : 'deleteValue';
    const delIconAllowedValue: number = this.deleteCondition?.length ? +this.deleteCondition[1] : 0;
    const currentDeleteValue = delVariableNameToCheck in data ? +data[delVariableNameToCheck] : null;

    // Don't allow any action except Restore
    if (currentDeleteValue !== null && delIconAllowedValue !== currentDeleteValue) {
      if (action.id !== USER_ACTION.RESTORE) {
        isActionAllowed = false;
      }
    } else if (currentDeleteValue !== null && delIconAllowedValue === currentDeleteValue && action.id === USER_ACTION.RESTORE) {
      // Don't allow restore option
      isActionAllowed = false;
    } else if (currentDeleteValue === null && action.id === USER_ACTION.RESTORE) {
      // if value is not present, Don't allow restore option
      isActionAllowed = false;
    } else if (action.id === USER_ACTION.EDIT) {
      if (this.editCondition) {
        const variablePositionToCheck: string = this.editCondition[0];
        const arrAllowedValues: string[] = this.editCondition[1];

        if (arrAllowedValues.indexOf(data[variablePositionToCheck]) === -1) {
          isActionAllowed = false;
        }
      }
    } else if (action.id === USER_ACTION.UNLK) {
      if (this.unlockCondition) {
        const variableNameToCheck: string = this.unlockCondition[0];
        const allowedValue: boolean = this.unlockCondition[1];

        if (allowedValue === data[variableNameToCheck]) {
          isActionAllowed = false;
        }
      }
    } else if (action.id === USER_ACTION.MARK_COMP) {
      if (this.markCompleteCondition) {
        const variableNameToCheck: string = this.markCompleteCondition[0];
        const allowedValue: boolean = this.markCompleteCondition[1];

        if (allowedValue === data[variableNameToCheck]) {
          isActionAllowed = false;
        }
      }
    }

    return isActionAllowed;
  }

  getColumnContent(data: any, label: string, index: number) {
    if (this.downloadColumnIndex && this.downloadColumnIndex.length && this.downloadColumnIndex.indexOf(index) > -1) {
      const file = data[label as string];

      if (file && file[0] && file[1]) {
        return `<p class="link text-primary">Download</p>`;
      }

      return '';
    }

    return data[label];
  }

  downloadFile(data: any, label: string, index: number) {
    if (this.downloadColumnIndex && this.downloadColumnIndex.length && this.downloadColumnIndex.indexOf(index) > -1) {
      const file = data[label as string];

      if (file && file[0] && file[1]) {
        Functions.downloadFile(file[0], file[1]);
      }
    }
  }

  onExtraButtonClick(dataIndex: number, btnIndex: number) {
    this.extraButtons.forEach((extraButton, index) => {
      if (btnIndex !== index) {
        extraButton.showTemplate = -1;
      }
    });
    this.extraButtons[btnIndex].showTemplate = this.extraButtons[btnIndex].showTemplate === (dataIndex + btnIndex) ?
      -1 : dataIndex + btnIndex;
  }

  get expandableColumnSize() {
    return 1 + this.body.length + (this.isSelectable ? 1 : 0) + this.actions.length;
  }

  downloadData() {
    if (!this.isDownloadDataBtnDisabled && this.group.get('searchbar')?.valid) {
      this.isDownloadDataBtnDisabled = true;
      this.loaderService.startLoader();

      this.subscription.push(
        this.formService.customActionCall<CsvDataFormat | GetDownloadFileDetails>(STATIC_MODULES.custom.getDownloadData,
          this.group.get('searchbar')?.getRawValue(), null, this.downloadDataUrl)
          .pipe(
            finalize(() => {
              this.loaderService.stopLoader();
              this.isDownloadDataBtnDisabled = false;
            })
          )
          .subscribe(resp => {
            if (resp && resp.status === REQUEST_STATUS.SUCCESS && resp.data) {
              if ((resp?.data as GetDownloadFileDetails)?.filePath) {
                Functions.downloadFile((resp.data as GetDownloadFileDetails).filePath, resp.data.fileName);
              } else {
                Functions.createCSV(resp?.data as CsvDataFormat);
              }
            }
          })
      );
    }
  }

  downloadSummary() {
    if (!this.isDownloadSummaryBtnDisabled && this.group.get('searchbar')?.valid) {
      this.isDownloadSummaryBtnDisabled = true;
      this.loaderService.startLoader();

      this.subscription.push(
        this.formService.customActionCall<CsvDataFormat | GetDownloadFileDetails>(STATIC_MODULES.custom.getDownloadSummary,
          this.group.get('searchbar')?.getRawValue(), null, this.downloadDataUrl)
          .pipe(
            finalize(() => {
              this.loaderService.stopLoader();
              this.isDownloadSummaryBtnDisabled = false;
            })
          )
          .subscribe(resp => {
            if (resp && resp.status === REQUEST_STATUS.SUCCESS && resp.data) {
              if ((resp?.data as GetDownloadFileDetails)?.filePath) {
                Functions.downloadFile((resp.data as GetDownloadFileDetails).filePath, resp.data.fileName);
              } else {
                Functions.createCSV(resp?.data as CsvDataFormat);
              }
            }
          })
      );
    }
  }

  exportTableToXlsx(): void {
    if (this.tableData?.length) {
      const fileName = this.headingText ? this.headingText.replace(/[^a-zA-Z0-9]/, '_') : 'File';
      const bodyLength = this.body.length;
      const checkboxIndex = 0;
      const galleryIndex = bodyLength + 1;
      const actionsIndex = this.gallery ? bodyLength + 2 : bodyLength + 1;

      /* table id is passed over here */
      const element = document.getElementById('excel-table');
      const ws: XLSX.WorkSheet = XLSX.utils.table_to_sheet(element);

      // Don't add Checkbox, Images, Actions columns
      if (this.isSelectable && this.tableData?.length > 0 && this.multiActions?.length > 0 && ws['!cols']) {
        ws['!cols'][checkboxIndex] = { hidden: true };
      }
      if (this.gallery && ws['!cols']) {
        ws['!cols'][galleryIndex] = { hidden: true };
      }
      if (this.singleActions?.length && ws['!cols']) {
        ws['!cols'][actionsIndex] = { hidden: true };
      }

      /* generate workbook and add the worksheet */
      const wb: XLSX.WorkBook = XLSX.utils.book_new();
      XLSX.utils.book_append_sheet(wb, ws, 'Sheet1');

      /* save to file */
      XLSX.writeFile(wb, `${fileName}.xlsx`);
    }
  }

  private getPdfData() {
    if (!this.headersText?.length) {
      this.subscription.push(
        this.translate.get(this.header)
          .subscribe(translatedMsg => {
            this.headersText = translatedMsg;
          })
      );
    }

    const doc = new jsPDF();
    autoTable(doc, {
      head: [
        Object.values(this.headersText)
      ],
      body: this.tableData.map(data => {
        return this.body.map(bodyKey => data[bodyKey]);
      })
    });

    return doc;
  }

  exportTableToPdf() {
    if (this.tableData?.length) {
      const fileName = this.headingText ? this.headingText.replace(/[^a-zA-Z0-9]/, '_') : 'File';
      const doc = this.getPdfData();
      doc.save(`${fileName}.pdf`);
    }
  }

  printPdf(): void {
    if (this.tableData?.length) {
      const doc = this.getPdfData();
      const blob = doc.output('blob');
      Functions.printBlob(blob);
    }
  }

  // displayAddModal() {
  //   this.editModalService.show(false, this.addConfig, this.addLabel);
  // }
}
