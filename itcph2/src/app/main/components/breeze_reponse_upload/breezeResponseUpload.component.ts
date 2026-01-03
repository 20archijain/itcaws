import { Component, OnDestroy, OnInit, ViewChild } from '@angular/core';
import { UntypedFormBuilder, UntypedFormGroup } from '@angular/forms';
import { Subscription } from 'rxjs';
import { finalize } from 'rxjs/operators';
import { TranslateService } from '@ngx-translate/core';

import { FormService } from 'src/app/core/services/form.service';
import { REQUEST_STATUS, STATIC_MODULES, UPLOAD_FILES } from 'src/app/app.constants';
import { environment } from 'src/environments/environment';
import { COMMON_VALIDATORS } from 'src/app/core/validators/validations.list';
import { CanGoBackGuard } from 'src/app/core/guards/can-go-back-guard.service';
import { FileUploadComponent } from 'src/app/shared/controls/file-upload/file-upload.component';
import { FileUploadEvent } from 'src/app/core/interfaces/helpers.interface';
import { GetDownloadFileDetails } from 'src/app/core/interfaces/http-response.interface';
import { ToastrService } from 'src/app/core/services/toastr.service';
import { LoaderService } from 'src/app/core/services/loader.service';
import { Functions } from 'src/app/core/utils/functions.list';

@Component({
  templateUrl: './breezeResponseUpload.component.html',
})
export class BreezeResponseUploadComponent implements OnInit, OnDestroy {

  @ViewChild(FileUploadComponent, { static: false }) private fileUploadComponent: FileUploadComponent;

  private subscription: Subscription[] = [];

  excelFile: File = null;
  isDisabled = false;
  form: UntypedFormGroup;
  excelData: any[] = [];
  excel = UPLOAD_FILES.fileTypes.excel.mimeTypes.join(',');

  errorMessages = {
    excelFile: COMMON_VALIDATORS.messages.file(
      'Breeze File'
    ),
  };

  constructor(
    private fb: UntypedFormBuilder,
    private formService: FormService,
    private canGoBackGuard: CanGoBackGuard,
    private toast: ToastrService,
    private loaderService: LoaderService,
    private translate: TranslateService
  ) { }

  ngOnInit() {
    this.form = this.fb.group({
      excelFile: ['', COMMON_VALIDATORS.validators.file(true)],
    });

    this.canGoBackGuard.markAsPristine();

    this.subscription.push(
      this.form.valueChanges.subscribe(() => this.canGoBackGuard.markAsDirty())
    );
  }

  ngOnDestroy() {
    this.subscription.forEach(sub => sub.unsubscribe());
  }

  onSelect(event: FileUploadEvent) {
    this.excelData = [];
    this.excelFile = event && event.files && event.files[0];

  }

  uploadData() {
    this.isDisabled = true;
    this.loaderService.startLoader();

    this.subscription.push(
      this.formService
        .customActionCall<string>(
          STATIC_MODULES.listing.addData,
          this.form,
          this.excelFile,
          environment.uploadRouteDataUrl
        )
        .pipe(
          finalize(() => {
            this.isDisabled = false;
            this.loaderService.stopLoader();
          })
        )
        .subscribe(resp => {
          if (resp && resp.status === REQUEST_STATUS.SUCCESS) {
            this.fileUploadComponent.clear();
            this.clearForm();
            this.canGoBackGuard.markAsPristine();
          }
        })
    );
  }

  format() {
    this.loaderService.startLoader();
    this.isDisabled = true;

    this.subscription.push(
      this.formService
        .customActionCall<GetDownloadFileDetails>(
          STATIC_MODULES.custom.getDownloadData,
          null,
          null,
          environment.getListingExcelUrl
        )
        .pipe(
          finalize(() => {
            this.isDisabled = false;
            this.loaderService.stopLoader();
          })
        )
        .subscribe(response => {
          if (response && response.status === REQUEST_STATUS.SUCCESS) {
            Functions.downloadFile(response.data.filePath, response.data.fileName);
          }
        })
    );
  }

  clearForm() {
    this.form.reset();
    this.excelData = [];
  }
}
