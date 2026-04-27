import { Component, OnDestroy, OnInit, ViewChild } from '@angular/core';
import { UntypedFormBuilder, UntypedFormControl, UntypedFormGroup } from '@angular/forms';
import { Subscription } from 'rxjs';
import { finalize } from 'rxjs/operators';
import { TranslateService } from '@ngx-translate/core';

import { FormService } from 'src/app/core/services/form.service';
import { CUSTOM_VALIDATOR_KEYS, REQUEST_STATUS, STATIC_MODULES, UPLOAD_FILES } from 'src/app/app.constants';
import { environment } from 'src/environments/environment';
import { COMMON_VALIDATORS } from 'src/app/core/validators/validations.list';
import { CanGoBackGuard } from 'src/app/core/guards/can-go-back-guard.service';
import { FileUploadComponent } from 'src/app/shared/controls/file-upload/file-upload.component';
import { FileUploadEvent } from 'src/app/core/interfaces/helpers.interface';
import { GetDownloadFileDetails, RouteDataUploadExcelData, RouteDataUploadResponse } from 'src/app/core/interfaces/http-response.interface';
import { ATLEAST_ONE_VALUE_REQUIRED_VALIDATOR } from 'src/app/core/validators/common.validator';
import { ToastrService } from 'src/app/core/services/toastr.service';
import { LoaderService } from 'src/app/core/services/loader.service';
import { ConfirmationModalService } from 'src/app/core/services/confirmation-modal.service';
import { Functions } from 'src/app/core/utils/functions.list';

@Component({
  templateUrl: './route-data-upload.component.html',
  standalone: false,
})
export class RouteDataUploadComponent implements OnInit, OnDestroy {
  @ViewChild(FileUploadComponent, { static: false }) private fileUploadComponent!: FileUploadComponent;
  private subscription: Subscription[] = [];
  private excelFile: File | null | undefined = null;
  isDisabled = false;
  form!: UntypedFormGroup;
  excelData: RouteDataUploadExcelData | null = null;
  excelHeader: string[] = [];
  tableColumns: string[] = [];
  excel = UPLOAD_FILES.fileTypes.excel.mimeTypes.join(',');
  errorMessages = {
    excelFile: COMMON_VALIDATORS.messages.file('Route File',
      UPLOAD_FILES.maxFileSizeInBytes, UPLOAD_FILES.fileTypes.excel.fileExtensions),
  };
  atleastOneColumnError = 'app.routeData.atleastOneColumnError';
  duplicateColumnError = 'app.routeData.duplicateColumnError';
  noHeaderFoundError = 'app.routeData.noHeaderFoundError';
  columnErrors = {
    [this.atleastOneColumnError]: this.atleastOneColumnError,
    [this.duplicateColumnError]: this.duplicateColumnError,
    [this.noHeaderFoundError]: this.noHeaderFoundError
  };

  constructor(private fb: UntypedFormBuilder, private formService: FormService,
    private canGoBackGuard: CanGoBackGuard, private toast: ToastrService, private loaderService: LoaderService,
    private translate: TranslateService, private confirmationModalService: ConfirmationModalService) { }

  ngOnInit() {
    this.form = this.fb.group({
      excelFile: ['', COMMON_VALIDATORS.validators.file(true)],
      columns: this.fb.group({}),
    });

    this.subscription.push(
      this.translate.get(Object.keys(this.columnErrors))
        .subscribe(translatedMsg => {
          this.columnErrors = translatedMsg;
        })
    );

    this.canGoBackGuard.markAsPristine();

    this.subscription.push(
      this.form.valueChanges
        .subscribe(() => this.canGoBackGuard.markAsDirty())
    );

    // on add confirm
    this.subscription.push(
      this.confirmationModalService.modal()
        .subscribe(resp => {
          if (!resp.goBackGuard && !resp.show) {
            if (resp.data && this.confirmationModalService.data === 'modal.confirmation.add') {
              this.confirmAddData();
            }
          }
        })
    );
  }

  ngOnDestroy() {
    this.subscription.forEach(sub => sub.unsubscribe());
  }

  get columnsForm(): UntypedFormGroup {
    return this.form.get('columns') as UntypedFormGroup;
  }

  onSelect($event: FileUploadEvent) {
    this.resetColumns();
    this.excelFile = $event && $event.files && $event.files[0];
  }

  showColumns() {
    if (this.form.get('excelFile')?.valid) {
      this.isDisabled = true;
      this.loaderService.startLoader();
      this.resetColumns();

      this.subscription.push(
        this.formService.customActionCall<RouteDataUploadResponse>(STATIC_MODULES.custom.getHeader,
          this.form, this.excelFile, environment.uploadRouteDataUrl)
          .pipe(
            finalize(() => {
              this.isDisabled = false;
              this.loaderService.stopLoader();
            })
          )
          .subscribe(resp => {
            if (resp && resp.status === REQUEST_STATUS.SUCCESS && resp.data) {
              this.excelHeader = this.filterNullAndEmpty(resp.data.excelHeader);
              this.tableColumns = resp.data.tableColumns;
              this.excelData = resp.data.excelData;

              if (this.excelHeader?.length) {
                this.form.patchValue({
                  columns: []
                });
                this.generateFormControls();
              } else {
                this.toast.toastr({ type: 'error', msg: this.columnErrors[this.noHeaderFoundError] });
              }
            }
          })
      );
    }
  }

  filterNullAndEmpty(headers: string[]) {
    return headers.filter((header: string) => header);
  }

  generateFormControls() {
    const columnSelections = (this.form.get('columns') as UntypedFormGroup);

    this.excelHeader.forEach((column, index) => {
      columnSelections.addControl('columnSelections-' + index + '-' + column, new UntypedFormControl());
    });
    columnSelections.addValidators([ATLEAST_ONE_VALUE_REQUIRED_VALIDATOR()]);
  }

  addData() {
    if (this.form.get('columns')?.valid && !this.isDisabled) {
      this.confirmationModalService.show('modal.confirmation.add');
    } else if (this.form.get('columns')?.hasError(CUSTOM_VALIDATOR_KEYS.ATLEAST_ONE_VALUE_REQUIRED)) {
      this.toast.toastr({ type: 'error', msg: this.columnErrors[this.atleastOneColumnError] });
    }
  }

  confirmAddData() {
    const columnNames = this.form.get('columns')?.value;

    const errorMessage = this.getDuplicateErrorMessage(columnNames);
    if (errorMessage) {
      this.toast.toastr({ type: 'error', msg: errorMessage });
      return;
    }

    this.loaderService.startLoader();
    this.isDisabled = true;
    this.subscription.push(
      this.formService.customActionCall<string>(STATIC_MODULES.listing.addData,
        { ...this.form.getRawValue(), excelData: this.excelData }, null, environment.confirmUploadRouteDataUrl)
        .pipe(
          finalize(() => {
            this.isDisabled = false;
            this.loaderService.stopLoader();
          })
        )
        .subscribe(resp => {
          if (resp && resp.status === REQUEST_STATUS.SUCCESS) {
            this.excelFile = null;
            this.fileUploadComponent.clear();
            this.clearForm();
            this.removeDynamicControls();
            this.canGoBackGuard.markAsPristine();
          }
        })
    );
  }

  format() {
    this.loaderService.startLoader();
    this.isDisabled = true;
    this.subscription.push(
      this.formService.customActionCall<GetDownloadFileDetails>(STATIC_MODULES.custom.getDownloadData,
        null, null, environment.getListingExcelUrl)
        .pipe(
          finalize(() => {
            this.isDisabled = false;
            this.loaderService.stopLoader();
          })
        )
        .subscribe(response => {
          if (response && response.status === REQUEST_STATUS.SUCCESS && response.data) {
            Functions.downloadFile(response.data.filePath, response.data.fileName);
          }
        })
    );
  }

  getDuplicateErrorMessage(columnNames: string[]) {
    const seen: any = {};

    for (let i = 0; i < Object.values(columnNames).length; i++) {
      const item = Object.values(columnNames)[i];
      if (item) {
        if (seen[item]) {
          return this.columnErrors[this.duplicateColumnError].replace('{COLUMN}', item);
        } else {
          seen[item] = true;
        }
      }
    }

    return null;
  }

  resetSelection(column: string) {
    const control = this.form.get('columns');
    if (control) {
      const columnControl = (control as UntypedFormGroup).controls[column];
      if (columnControl) {
        columnControl.reset();
      }
    }
  }

  clearForm() {
    this.form.reset();
  }

  resetColumns() {
    this.excelHeader = [];
    this.tableColumns = [];
    this.excelData = null;
    this.removeDynamicControls();
  }

  removeDynamicControls() {
    if (this.form.get('columns')) {
      this.form.removeControl('columns');
      this.form.addControl('columns', new UntypedFormGroup({}));
    }
  }
}
