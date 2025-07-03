import { Component, OnDestroy, OnInit } from '@angular/core';
import { Subscription } from 'rxjs';
import { finalize } from 'rxjs/operators';
import { UntypedFormBuilder, UntypedFormGroup } from '@angular/forms';

import { REQUEST_STATUS, STATIC_MODULES } from 'src/app/app.constants';
import { ViewskuPickupResponse, SkuPickUpList, GetDownloadFileDetails } from 'src/app/core/interfaces/http-response.interface';
import { FormService } from 'src/app/core/services/form.service';
import { LoaderService } from 'src/app/core/services/loader.service';
import { environment } from 'src/environments/environment';
import { COMMON_VALIDATORS } from 'src/app/core/validators/validations.list';
import { Functions } from 'src/app/core/utils/functions.list';

@Component({
  templateUrl: './sku-pickup.component.html',
  styleUrls: ['./sku-pickup.component.scss'],
})
export class SkuPickupComponent implements OnDestroy, OnInit {
  private subscription: Subscription[] = [];
  body: string[] = [];
  url = environment.viewSkuPickupUrl;
  group: UntypedFormGroup;
  totalPresent: string;
  totalTeams: string;
  skupickupList: SkuPickUpList[] = [];

  errorMessages = {
    // dateFrom: COMMON_VALIDATORS.messages.date('From'),
    // dateTo: COMMON_VALIDATORS.messages.date('To'),
  };
  date: number;
  isDisabled: boolean;

  constructor(private fb: UntypedFormBuilder, private formService: FormService, private loaderService: LoaderService) { }

  ngOnInit() {
    const monthStartDate = Functions.currentDate(null, true);
    const currentDate = Functions.currentDate();
    this.group = this.fb.group({
      dateFrom: [monthStartDate, COMMON_VALIDATORS.validators.date],
      dateTo: [currentDate, COMMON_VALIDATORS.validators.date],
    });
  }

  ngOnDestroy() {
    this.subscription.forEach(sub => sub.unsubscribe());
  }

  getTableData() {
    if (this.group.valid) {
      this.skupickupList = [];
      this.totalPresent = null;
      this.totalTeams = null;
      this.loaderService.startLoader();
      this.subscription.push(
        this.formService.getData<ViewskuPickupResponse>(this.url, this.group.getRawValue())
          .pipe(
            finalize(() => this.loaderService.stopLoader()),
          )
          .subscribe(resp => {
            if (resp && resp.status === REQUEST_STATUS.SUCCESS) {
              this.skupickupList = resp.data.SkuPickUpList;
              this.totalPresent = resp.data.totalPresent;
              this.totalTeams = resp.data.totalTeams;
            }
          })
      );
    }
  }

  getProductNames(): string[] {
    const productNames: string[] = [];
    this.skupickupList.forEach(pickupItem => {
      pickupItem.productList.forEach(product => {
        if (!productNames.includes(product.productName)) {
          productNames.push(product.productName);
        }
      });
    });

    return productNames;
  }

  getUniqueDates(): string[] {
    const uniqueDates: string[] = [];
    this.skupickupList.forEach(pickupItem => {
      if (!uniqueDates.includes(pickupItem.date)) {
        uniqueDates.push(pickupItem.date);
      }
    });

    return uniqueDates;
  }

  downloadSkuPickup() {
    this.isDisabled = true;
    this.loaderService.startLoader();
    this.subscription.push(
      this.formService.customActionCall<GetDownloadFileDetails>(STATIC_MODULES.custom.getDownloadData,
        this.group.getRawValue(), null, environment.getListingExcelUrl)
        .pipe(
          finalize(() => {
            this.isDisabled = false;
            this.loaderService.stopLoader();
          }),
        )
        .subscribe(response => {
          if (response && response.status === REQUEST_STATUS.SUCCESS) {
            Functions.downloadFile(response.data.filePath, response.data.fileName);
          }
        })
    );
  }
}
