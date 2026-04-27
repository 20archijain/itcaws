import { Component, OnDestroy, OnInit } from '@angular/core';
import { UntypedFormBuilder, UntypedFormControl, UntypedFormGroup } from '@angular/forms';
import { Subscription } from 'rxjs';
import { finalize } from 'rxjs/operators';

import { FormService } from 'src/app/core/services/form.service';
import { DropdownList, ManualAssignTargetResponse } from 'src/app/core/interfaces/http-response.interface';
import { LoaderService } from 'src/app/core/services/loader.service';
import { REQUEST_STATUS, STATIC_MODULES } from 'src/app/app.constants';
import { environment } from 'src/environments/environment';
import { COMMON_VALIDATORS } from 'src/app/core/validators/validations.list';
import { STOCK_NUMBER_MAX_3_REGEX } from 'src/app/core/validators/regex';

@Component({
  templateUrl: './ManualAssignTarget.component.html',
  standalone: false,
})
export class ManualAssignTargetComponent implements OnInit, OnDestroy {
  private subscription: Subscription[] = [];
  header: string[] = [];
  body: string[] = [];
  branchOptions: DropdownList[] = [];
  productOptions = [];
  form!: UntypedFormGroup;
  showDownloadDataBtn = false;
  downloadDataBtnTitle = false;
  showInputOption = false;
  product1 = '';
  product2 = '';
  product3 = '';
  url = environment.getActiveVariantsDataUrl;

  errorMessages: { [key: string]: any } = {
    branch: COMMON_VALIDATORS.messages.requiredOnly('Branch'),
  };
  constructor(private formService: FormService, private fb: UntypedFormBuilder, private loaderService: LoaderService) { }

  ngOnInit() {
    this.form = this.fb.group({
      branch: [null, COMMON_VALIDATORS.validators.requiredOnly],
      quantity: this.fb.group({}),
    });

    this.getInitialData();
  }

  ngOnDestroy() {
    this.subscription.forEach(sub => sub.unsubscribe());
  }

  get quantityFormGroup() {
    return this.form.get('quantity') as UntypedFormGroup;
  }


  getInitialData() {
    this.loaderService.startLoader();
    this.subscription.push(
      this.formService.getData<ManualAssignTargetResponse>(this.url, this.form.getRawValue())
        .pipe(
          finalize(() => this.loaderService.stopLoader())
        )
        .subscribe(resp => {
          if (resp && resp.status === REQUEST_STATUS.SUCCESS) {
            this.branchOptions = resp?.data?.branchList || [];
          }
        })
    );
  }


  getProductsList() {
    this.loaderService.startLoader();
    this.subscription.push(
      this.formService.customActionCall<ManualAssignTargetResponse>(STATIC_MODULES.custom.getproduct, { branch: this.form?.get('branch')?.value }, null, environment.viewVanDsDataUrl)
        .pipe(finalize(() => this.loaderService.stopLoader()))
        .subscribe(resp => {
          if (resp && resp.status === REQUEST_STATUS.SUCCESS && resp.data) {
            this.productOptions = resp.data.productList;
            this.form.removeControl('quantity');
            this.form.addControl('quantity', new UntypedFormGroup({}));


            // if (this.form.get('quantity')) {
            //   this.form.removeControl('quantity');
            //   this.form.addControl('quantity', new UntypedFormGroup({}));
            // }
            const quantity = this.form.get('quantity') as UntypedFormGroup;

            this.productOptions.forEach(item => {
              const controlName = `${item[1]}`;
              const label = item[0];

              quantity.addControl(
                controlName,
                new UntypedFormControl(
                  null,
                  COMMON_VALIDATORS.validators.nonZeroNumberWithMaxAndMin(
                    STOCK_NUMBER_MAX_3_REGEX,
                    true,
                    999,
                    0.01
                  )
                )
              );

              this.errorMessages[controlName] =
                COMMON_VALIDATORS.messages.nonZeroNumberWithMaxAndMin(
                  label,
                  999,
                  0.01
                );
            });

          }
        })
    );
  }


  submitData() {
    if (this.form.valid) {
      this.loaderService.startLoader();
      this.subscription.push(
        this.formService.customActionCall(STATIC_MODULES.custom.submitSelectedProducts, this.form.getRawValue(), null, environment.downloadExcelUrl)
          .pipe(
            finalize(() => {
              this.loaderService.stopLoader();
            })
          )
          .subscribe(resp => {
            if (resp && resp.status === REQUEST_STATUS.SUCCESS) {
              this.form.reset();
              this.form.removeControl('quantity');
              this.form.addControl('quantity', new UntypedFormGroup({}));
              this.getInitialData();
              this.productOptions = [];
            }
          })
      );
    }
  }

  clearForm() {
    // this.getInitialData();
    this.form.reset();
  }

}
