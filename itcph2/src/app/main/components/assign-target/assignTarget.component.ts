import { AfterViewInit, Component, OnDestroy, OnInit } from '@angular/core';
import { UntypedFormArray, UntypedFormBuilder, UntypedFormGroup } from '@angular/forms';
import { Subscription } from 'rxjs';
import { finalize } from 'rxjs/operators';

import { ToastrService } from "src/app/core/services/toastr.service";
import { REQUEST_STATUS } from 'src/app/app.constants';
import { CanGoBackGuard } from 'src/app/core/guards/can-go-back-guard.service';
import { ReadyStockPickupResponse, StockProduct } from 'src/app/core/interfaces/http-response.interface';
import { ConfirmationModalService } from 'src/app/core/services/confirmation-modal.service';
import { FormService } from 'src/app/core/services/form.service';
import { LoaderService } from 'src/app/core/services/loader.service';
import { Functions } from 'src/app/core/utils/functions.list';
import { GROUPED_VALUES_ALL_OR_NONE_VALIDATOR } from 'src/app/core/validators/common.validator';
import { COMMON_VALIDATORS } from 'src/app/core/validators/validations.list';
import { environment } from 'src/environments/environment';

@Component({
  templateUrl: './assignTarget.component.html',
  styleUrls: ['./assignTarget.component.scss']
})
export class AssignTargetComponent implements AfterViewInit, OnDestroy, OnInit {
  private subscription: Subscription[] = [];
  group: UntypedFormGroup;
  tabCondition = false;
  stockProductsList: StockProduct[] = [];
  previousMonth = Functions.previousMonth();
  currentMonth = Functions.currentMonth();
  nextMonth = Functions.nextMonth();
  currentDate = Functions.currentDate();

  // monthOptions = [
  //   { label: 'January', value: '01' },
  //   { label: 'February', value: '02' },
  //   { label: 'March', value: '03' },
  //   { label: 'April', value: '04' },
  //   { label: 'May', value: '05' },
  //   { label: 'June', value: '06' },
  //   { label: 'July', value: '07' },
  //   { label: 'August', value: '08' },
  //   { label: 'September', value: '09' },
  //   { label: 'October', value: '10' },
  //   { label: 'November', value: '11' },
  //   { label: 'December', value: '12' }
  // ];
  teamsList = [];
  errorMessages = {
    qty: COMMON_VALIDATORS.messages.zeroAndFloatQtyStock,
  };
  isDisabled = false;
  rowClasses: { [key: string]: string } = {};
  currentColorIndex = 0;
  colors = ['bg-light-gray', 'bg-white'];
  product1: string;
  product2: string;
  tableColumnCondition = true;

  constructor(private formService: FormService, private fb: UntypedFormBuilder, private loaderService: LoaderService,
    private canGoBackGuard: CanGoBackGuard, private toastrService: ToastrService, private confirmationModalService: ConfirmationModalService) { }

  ngOnInit() {
    this.group = this.fb.group({
      qty: this.fb.array([]),
      monthCheck: [null]
    });

    // subscribe to confirmation modal
    this.subscription.push(
      this.confirmationModalService.modal()
        .subscribe(resp => {
          if (!resp.goBackGuard && !resp.show) {
            // user confirms
            if (resp.data) {
              this.confirmPickupStock();
            }
          }
        })
    );

    if (this.currentDate['day'] > 21) {
      this.tabCondition = true;
    }
    this.initialData();
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
    this.teamsList = [];
    this.stockProductsList = [];
    // this.resetDynamicForm();
    this.loaderService.startLoader();
    this.subscription.push(
      this.formService.getData<ReadyStockPickupResponse>(environment.editProductUrl, this.group.getRawValue())
        .pipe(
          finalize(() => {
            this.loaderService.stopLoader();
          })
        )
        .subscribe(resp => {
          if (resp && resp.status === REQUEST_STATUS.SUCCESS) {
            this.stockProductsList = resp.data.stockProductsList;
            this.teamsList = resp.data.teamsList;
            this.product1 = resp.data.product1;
            this.product2 = resp.data.product2;
            this.tableColumnCondition = resp.data.tableColumnCondition;

            if (this.stockProductsList.length > 0) {
              // create dynamic control
              this.createDynamicGroup();
            } else {

              this.toastrService.toastr({
                type: 'error',
                msg: 'Team Not Found'
              });
            }

          }
        })
    );
  }


  getButtonCondition(): boolean {
    if (this.group.get('monthCheck').value === 2) {
      if (this.currentDate['day'] > 21 || this.currentDate['day'] < 11) {
        return true;
      } else {
        return false;
      }
    } else {
      if (this.currentDate['day'] < 11) {
        return true;
      } else {
        return false;
      }
    }
  }

  onTypeChange(monthCheck: number) {
    if (monthCheck === 2) {
      this.group.get('monthCheck').setValue(monthCheck);
    } else {
      this.group.get('monthCheck').setValue(null);
    }
    this.resetDynamicForm();
    this.initialData();
  }

  createDynamicGroup() {

    if (!Functions.isEmptyArray(this.teamsList)) {
      this.stockProductsList.forEach(product => {
        const controls = [];
        this.teamsList.forEach(team => {
          controls[`qty-${team.value}-${product.value}`] = ["", COMMON_VALIDATORS.validators.zeroAndFloatQtyStock];
        });
        (this.group.get('qty') as UntypedFormArray).push(this.fb.group(controls));
      });
      (this.group.get('qty') as UntypedFormArray).setValidators([GROUPED_VALUES_ALL_OR_NONE_VALIDATOR(3)]);

      this.canGoBackGuard.markAsPristine();
    }
  }

  confirmStock() {
    if (this.group.valid) {
      this.confirmationModalService.show('modal.confirmation.assignTarget');
    }
    // } else {
    //   this.toastrService.toastr({
    //     msg: 'Production date must be within the last 3 days from previous day',
    //     type: 'error'
    //   });
    // }
  }


  confirmPickupStock() {
    this.isDisabled = true;
    this.loaderService.startLoader();

    this.subscription.push(
      this.formService.addData<string>(this.group.getRawValue(), null, environment.editProductUrl)
        .subscribe(response => {
          // not using finalize() to avoid loader visibility issue
          this.loaderService.stopLoader();
          this.isDisabled = false;
          if (response && response.status === REQUEST_STATUS.SUCCESS) {
            this.refreshList();
          }
        }, () => {
          this.loaderService.stopLoader();
          this.isDisabled = false;
        })
    );
  }

  refreshList() {
    this.stockProductsList = [];
    this.teamsList = [];
    // this.group.reset();
    this.resetDynamicForm();
    this.initialData();
    this.canGoBackGuard.markAsPristine();
  }

  resetDynamicForm() {
    this.group.removeControl('qty');
    this.group.addControl('qty', new UntypedFormArray([], [GROUPED_VALUES_ALL_OR_NONE_VALIDATOR(3)]));
  }

  getTeamTotalQty(teamId: string): number {
    let sum = 0;
    if (this.stockProductsList?.length && this.group.get('qty') && this.group.get('qty')["controls"]) {
      this.stockProductsList.forEach((product, productIndex) => {
        const qty = (this.group.get('qty') as UntypedFormArray).controls[productIndex]?.get(`qty-${teamId}-${product.value}`)?.value;
        sum += +qty;
      });
    }

    return parseFloat(sum.toFixed(2));
  }

  showComponents() {
    this.resetDynamicForm();
    this.initialData();
  }

  getRowClass(brand: string): string {
    // console.log(brand);
    if (!this.rowClasses[brand]) {
      this.rowClasses[brand] = this.colors[this.currentColorIndex];
      this.currentColorIndex = 1 - this.currentColorIndex; // Toggle index
    }
    return this.rowClasses[brand];
  }

}

