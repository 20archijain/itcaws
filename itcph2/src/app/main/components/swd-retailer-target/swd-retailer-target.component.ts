import { Component, Input, OnDestroy, OnInit, ViewChildren } from '@angular/core';
import { UntypedFormBuilder, UntypedFormGroup } from "@angular/forms";
import { Subscription } from "rxjs";
import { finalize } from 'rxjs/operators';

import { environment } from "src/environments/environment";
import { REQUEST_STATUS, STATIC_MODULES, imagePath } from "src/app/app.constants";
import { FileUploadComponent } from 'src/app/shared/controls/file-upload/file-upload.component';
import { FileUploadEvent } from 'src/app/core/interfaces/helpers.interface';
import { FormService } from "src/app/core/services/form.service";
import { DropdownList, GetDownloadFileDetails } from "src/app/core/interfaces/http-response.interface";
import {
  COMMON_VALIDATORS,
} from "src/app/core/validators/validations.list";
import { LoaderService } from "src/app/core/services/loader.service";
import { ConfirmationModalService } from 'src/app/core/services/confirmation-modal.service';
import { CanGoBackGuard } from 'src/app/core/guards/can-go-back-guard.service';
import { Functions } from 'src/app/core/utils/functions.list';
import { ToastrService } from 'src/app/core/services/toastr.service';
import { TranslateService } from '@ngx-translate/core';

@Component({
  templateUrl: './swd-retailer-target.component.html',
  styleUrls: ['./swd-retailer-target.component.scss']
})
export class SWDTargetUploadComponent implements OnDestroy, OnInit {
  @ViewChildren(FileUploadComponent) private fileUploadComponents: FileUploadComponent[];
  private subscription: Subscription[] = [];
  private excelFile: File = null;

  url = "";
  districtOptions: DropdownList[] = [];
  branchOptions: DropdownList[] = [];
  bmCodeOptions: DropdownList[] = [];
  stateOptions: DropdownList[] = [];
  teamOptions: DropdownList[] = [];
  wallOptions: DropdownList[] = [];
  @Input() firstnamefloatingInput: string;
  form: UntypedFormGroup;
  errorMessages = {
    excelFile: COMMON_VALIDATORS.messages.requiredOnly('Excel File'),
  };

  body: string[];
  isDisabled = false;
  dummyImage1 = imagePath + 'file.jpg';

  constructor(
    private formService: FormService,
    private fb: UntypedFormBuilder,
    private loaderService: LoaderService,
    private canGoBackGuard: CanGoBackGuard,
    private confirmationModalService: ConfirmationModalService,
    private toastr: ToastrService, private translate: TranslateService) { }

  ngOnInit() {
    this.form = this.fb.group({
      excelFile: [null, COMMON_VALIDATORS.validators.requiredOnly],
    });

    // subscribe to confirmation modal
    this.subscription.push(
      this.confirmationModalService.modal()
        .subscribe(resp => {
          if (!resp.goBackGuard && !resp.show) {
            // user confirms
            if (resp.data) {
              this.confirmAddData();
            }
          }
        })
    );

  }



  ngOnDestroy() {
    this.subscription.forEach((sub) => sub.unsubscribe());
  }


  addData() {
    if (this.form.valid && !this.isDisabled) {
      this.confirmationModalService.show('modal.confirmation.add');
    } else {
      this.displayExcelError();
    }
  }

  onSelect($event: FileUploadEvent, $imageNo: number) {
    if ($imageNo == 1) {
      this.excelFile = $event && $event.files && $event.files[0];
    }
  }

  downloadFormat() {
    this.loaderService.startLoader();
    this.subscription.push(
      this.formService.customActionCall<GetDownloadFileDetails>(STATIC_MODULES.custom.getDownloadData, null,
        null, environment.downloadAttendanceUrl)
        .pipe(
          finalize(() => {
            this.loaderService.stopLoader();
          })
        )
        .subscribe(resp => {
          if (resp && resp.status === REQUEST_STATUS.SUCCESS) {
            Functions.downloadFile(resp.data.filePath, resp.data.fileName);
          }
        })
    );
  }

  confirmAddData() {
    if (this.excelFile) {
      this.loaderService.startLoader();
      this.isDisabled = true;
      this.subscription.push(
        this.formService.addData<string>(this.form, [this.excelFile], this.url)
          .pipe(
            finalize(() => {
              this.loaderService.stopLoader();
              this.isDisabled = false;
            })
          )
          .subscribe(resp => {
            if (resp && resp.status === REQUEST_STATUS.SUCCESS) {
              // this.form.reset();
              this.reset();
              // this.fileUploadComponent.controlName = null;
              this.canGoBackGuard.markAsPristine();
            }
          })
      );
    } else {
      this.displayExcelError();
    }
  }

  reset() {
    // Reset file variables
    this.fileUploadComponents.forEach(comp => comp.clear());
    this.excelFile = null;
    this.form.reset();
  }

  displayExcelError() {
    this.toastr.toastr({ type: 'error', msg: "Please select Excel file to upload." });
  }
}
