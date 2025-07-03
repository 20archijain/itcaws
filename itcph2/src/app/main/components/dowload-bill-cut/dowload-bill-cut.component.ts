import { Component, OnDestroy, OnInit } from '@angular/core';
import { Subscription } from 'rxjs';
import { UntypedFormBuilder, UntypedFormGroup } from '@angular/forms';
import { finalize } from 'rxjs/operators';

import { FormService } from 'src/app/core/services/form.service';
import { environment } from 'src/environments/environment';
import { REQUEST_STATUS, STATIC_MODULES } from 'src/app/app.constants';
import { Functions } from 'src/app/core/utils/functions.list';
import { COMMON_VALIDATORS } from 'src/app/core/validators/validations.list';
import { DropdownList, GetDownloadBillCutResponse, GetDownloadFileDetails } from 'src/app/core/interfaces/http-response.interface';
import { LoaderService } from 'src/app/core/services/loader.service';

@Component({
  templateUrl: './dowload-bill-cut.component.html'
})
export class DowloadBillCutComponent implements OnDestroy, OnInit {
  private subscription: Subscription[] = [];
  group: UntypedFormGroup;
  branchOptions: DropdownList[] = [];
  teamTypeOptions: DropdownList[] = [];
  productOptions: DropdownList[] = [];
  errorMessages = {
    branch: COMMON_VALIDATORS.messages.requiredOnly('Branch'),
    dateFrom: COMMON_VALIDATORS.messages.requiredOnly('From'),
    dateTo: COMMON_VALIDATORS.messages.requiredOnly('To'),
  };
  isDisabled = false;
  url = environment.getUobReportDataUrl;

  constructor(private formService: FormService, private fb: UntypedFormBuilder, private loaderService: LoaderService) { }

  ngOnInit() {
    this.group = this.fb.group({
      branch: ['', COMMON_VALIDATORS.validators.requiredOnly],
      dateFrom: ['', COMMON_VALIDATORS.validators.date],
      dateTo: ['', COMMON_VALIDATORS.validators.date],
      teamType: [''],
      product: ['']
    });

    this.loaderService.startLoader();
    this.subscription.push(
      this.formService.getData<GetDownloadBillCutResponse>(this.url)
        .pipe(
          finalize(() => this.loaderService.stopLoader()),
        )
        .subscribe(resp => {
          if (resp && resp.status === REQUEST_STATUS.SUCCESS) {
            this.branchOptions = resp.data.branchList;
          }
        })
    );
  }

  ngOnDestroy() {
    this.subscription.forEach(sub => sub.unsubscribe());
  }

  download() {
    if (!this.isDisabled && this.group.valid) {
      this.isDisabled = true;
      this.loaderService.startLoader();
      this.subscription.push(
        this.formService.customActionCall<GetDownloadFileDetails>(STATIC_MODULES.custom.getDownloadData, this.group.getRawValue(),
          null, environment.downloadExcelUrl)
          .pipe(
            finalize(() => {
              this.isDisabled = false;
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
  }

  getTeamsType() {
    this.teamTypeValue = null;

    this.loaderService.startLoader();
    this.subscription.push(
      this.formService.customActionCall<GetDownloadBillCutResponse>(STATIC_MODULES.custom.getTeamsTypeList, { branch: this.group.get('branch').value }, null, this.url)
        .pipe(
          finalize(() => this.loaderService.stopLoader())
        )
        .subscribe(resp => {
          if (resp && resp.status === REQUEST_STATUS.SUCCESS) {
            this.teamTypeOptions = resp.data.teamType;
            this.productOptions = resp.data.productList;
          }
        })
    );
  }

  getProducts() {
    this.group.get('product').setValue('')
    this.loaderService.startLoader();
    this.subscription.push(
      this.formService.customActionCall<GetDownloadBillCutResponse>('get_product_list', { type: this.group.get('teamType').value, branch: this.group.get('branch').value }, null, this.url)
        .pipe(
          finalize(() => this.loaderService.stopLoader())
        )
        .subscribe(resp => {
          if (resp && resp.status === REQUEST_STATUS.SUCCESS) {
            this.productOptions = resp.data.productList;
          }
        })
    );
  }

  get branchValue() {
    return this.group && this.group.get('branch').value;
  }
  set teamTypeValue(value: string) {
    this.teamTypeOptions = [];
    this.group.get('teamType').setValue(value);
  }

}

