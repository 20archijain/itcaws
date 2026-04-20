import { AfterViewInit, Component, OnDestroy, OnInit } from '@angular/core';
import { UntypedFormArray, UntypedFormBuilder, UntypedFormControl, UntypedFormGroup, ValidatorFn, AbstractControl, ValidationErrors } from "@angular/forms";
import { Subscription } from "rxjs";
import { finalize } from "rxjs/operators";

import { REQUEST_STATUS, STATIC_MODULES } from "src/app/app.constants";
import { CanGoBackGuard } from "src/app/core/guards/can-go-back-guard.service";
import { DropdownList, EditProductResponse } from "src/app/core/interfaces/http-response.interface";
import { ConfirmationModalService } from "src/app/core/services/confirmation-modal.service";
import { FormService } from "src/app/core/services/form.service";
import { LoaderService } from "src/app/core/services/loader.service";
import { ATLEAST_ONE_VALUE_REQUIRED_VALIDATOR } from 'src/app/core/validators/common.validator';
import { COMMON_VALIDATORS } from "src/app/core/validators/validations.list";
import { environment } from "src/environments/environment";

// Custom Validator
function oneOfTwoRequiredValidator(): ValidatorFn {
  return (group: AbstractControl): ValidationErrors | null => {
    const branch = group.get('branch').value;
    const wdCode = group.get('wdCode').value;

    return (branch && !wdCode) || (!branch && wdCode) ? null : { oneOfTwoRequired: true };
  };
}

@Component({
    selector: 'app-edit-product-price',
    templateUrl: './edit-product-price.component.html',
    standalone: false
})
export class EditProductPriceComponent implements AfterViewInit, OnDestroy, OnInit {
  private subscription: Subscription[] = [];
  searchGroup: UntypedFormGroup;
  group: UntypedFormGroup;
  branchOptions: DropdownList[];
  wdOptions: DropdownList[];
  teamTypeOptions: DropdownList[];
  productsNamePriceList: { productName: string; sellingPrice: number; productId: number; branchId: number; wdCode: string; }[] = [];
  isSearching = false;
  isDisabled = false;
  editProductHeading: string;
  branchLabel: string;
  wdLabel: string;
  teamTypeLabel: string;
  productPrice: number;
  branchId: number;
  wdCode: string;
  errorMessages = {
    sellingPrice: COMMON_VALIDATORS.messages.dropdownAllOptional('Price')
  };
  sellingPriceForm: UntypedFormGroup;

  constructor(private formService: FormService, private fb: UntypedFormBuilder, private loaderService: LoaderService,
    private canGoBackGuard: CanGoBackGuard, private confirmationModalService: ConfirmationModalService) { }

  ngOnInit() {
    this.searchGroup = this.fb.group({
      branch: [''],
      wdCode: [''],
      teamType: ['']
    }, { validators: oneOfTwoRequiredValidator() });

    this.group = this.fb.group({
      product: [''],
      sellingPrice: this.fb.group({}),
    });

    this.sellingPriceForm = this.group.get('sellingPrice') as UntypedFormGroup;

    this.initialData();

    // subscribe to confirmation modal

    this.subscription.push(
      this.confirmationModalService.modal()
        .subscribe(resp => {
          if (!resp.goBackGuard && !resp.show) {
            // user confirms
            if (resp.data) {
              this.updateSellingPrice();
            }
          }
        })
    );
  }

  isSelectionInvalid(): boolean {
    return this.searchGroup.hasError('oneOfTwoRequired');
  }

  ngOnDestroy() {
    this.subscription.forEach(sub => sub.unsubscribe());
  }

  ngAfterViewInit() {
    this.canGoBackGuard.markAsPristine();

    this.subscription.push(
      this.group.valueChanges
        .subscribe(() => this.canGoBackGuard.markAsDirty())
    );
  }

  initialData() {
    this.loaderService.startLoader();
    this.subscription.push(
      this.formService.getData<EditProductResponse>(environment.editProductUrl)
        .pipe(
          finalize(() => this.loaderService.stopLoader())
        )
        .subscribe(resp => {
          if (resp && resp.status === REQUEST_STATUS.SUCCESS) {
            this.branchOptions = resp.data.branchList;
            this.wdOptions = resp.data.wdList;
            this.editProductHeading = resp.data.editProductHeading;
            this.branchLabel = resp.data.branchLabel;
            this.wdLabel = resp.data.wdLabel;
            this.teamTypeLabel = resp.data.teamTypeLabel;
          }
        })
    );
  }

  getProductList() {
    if (this.searchGroup.valid && !this.isDisabled) {
      this.isDisabled = true;
      this.loaderService.startLoader();
      this.subscription.push(
        this.formService.getList<EditProductResponse>(environment.editProductUrl,
          { branchId: this.searchGroup.get('branch').value, wdCode: this.searchGroup.get('wdCode').value })
          .pipe(
            finalize(() => {
              this.isDisabled = false;
              this.loaderService.stopLoader();
            })
          )
          .subscribe(resp => {
            if (resp && resp.status === REQUEST_STATUS.SUCCESS) {
              this.productsNamePriceList = resp.data.productsList;

              this.initializePriceControls();
            }
          })
      );
    }
  }

  initializePriceControls() {
    if (this.group.get('sellingPrice')) {
      this.group.removeControl('sellingPrice');
      this.group.addControl('sellingPrice', new UntypedFormGroup({}));
    }
    const sellingPriceControl = this.group.get('sellingPrice') as UntypedFormGroup;
    if (sellingPriceControl) {
      this.productsNamePriceList.forEach((product, index) => {
        this.productPrice = product.sellingPrice;
        this.branchId = product.branchId;
        this.wdCode = product.wdCode;
        const controlName = `${product.productName}-${product.productId}-${index + 1}`;
        let controlValue = null;
        controlValue = this.productPrice;
        sellingPriceControl.addControl(controlName, new UntypedFormControl(controlValue, [...COMMON_VALIDATORS.validators.zeroAndFloatQty]));
      });
    }
  }

  getTeamsType() {
    this.teamTypeValue = null;

    this.loaderService.startLoader();
    this.subscription.push(
      this.formService.customActionCall<EditProductResponse>(STATIC_MODULES.custom.getTeamsTypeList, { branch: this.searchGroup.get('branch').value },
        null, environment.editProductUrl)
        .pipe(
          finalize(() => this.loaderService.stopLoader())
        )
        .subscribe(resp => {
          if (resp && resp.status === REQUEST_STATUS.SUCCESS) {
            this.teamTypeOptions = resp.data.teamsTypeList;
          }
        })
    );
  }

  set teamTypeValue(value: string) {
    this.teamTypeOptions = [];
    this.searchGroup.get('teamType').setValue(value);
  }

  confirmPrice() {
    if (this.group.valid) {
      this.confirmationModalService.show('modal.confirmation.confirmMaterial');
    }
  }

  updateSellingPrice(): void {
    this.isDisabled = true;
    this.loaderService.startLoader();

    this.subscription.push(
      this.formService.addData<string>({ ...this.group.getRawValue(), branchId: this.branchId, wdCode: this.wdCode }, null, environment.editProductUrl)
        .pipe(
          finalize(() => {
            this.loaderService.stopLoader();
            this.isDisabled = false;
          }),
        )
        .subscribe(response => {
          if (response && response.status === REQUEST_STATUS.SUCCESS) {
            this.resetAllData();
          }
        })
    );
  }

  // New method to reset all data
  resetAllData(): void {
    this.clearForm();           // Clear the search form
    this.resetDynamicForm();     // Clear dynamic form controls
    this.productsNamePriceList = []; // Clear product list
    this.group.reset();          // Reset the main group form
    this.initialData();          // Reload initial data and dropdown options
    this.canGoBackGuard.markAsPristine(); // Mark form as pristine after reset
  }

  resetDynamicForm() {
    this.group.removeControl('sellingPrice');
    this.group.addControl('sellingPrice', new UntypedFormArray([], [ATLEAST_ONE_VALUE_REQUIRED_VALIDATOR()]));
  }

  clearForm() {
    this.searchGroup.reset();
  }
}
