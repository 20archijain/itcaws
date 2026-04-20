import { Component, OnDestroy, OnInit } from '@angular/core';
import { Subscription } from 'rxjs';
import { UntypedFormBuilder, UntypedFormGroup } from '@angular/forms';
import { finalize } from 'rxjs/operators';

import { FormService } from 'src/app/core/services/form.service';
import { environment } from 'src/environments/environment';
import { REQUEST_STATUS, STATIC_MODULES } from 'src/app/app.constants';
import { Functions } from 'src/app/core/utils/functions.list';
import { COMMON_VALIDATORS } from 'src/app/core/validators/validations.list';
import { DropdownList, GetProductiveReportDataResponse, GetDownloadFileDetails  } from 'src/app/core/interfaces/http-response.interface';
import { LoaderService } from 'src/app/core/services/loader.service';

@Component({
    selector: 'app-productivity-report',
    templateUrl: './productivity-report.component.html',
    standalone: false
})
export class ProductivityReportComponent implements OnDestroy, OnInit {
  private subscription: Subscription[] = [];
  group: UntypedFormGroup;
  yearList: DropdownList<number, number>[] = [];
  monthList: DropdownList[] = [];
  errorMessages = {
    year: COMMON_VALIDATORS.messages.requiredOnly('Year'),
    month: COMMON_VALIDATORS.messages.requiredOnly('Month'),
  };
  isDisabled = false;
  url = environment.getProductiveReportDataUrl;
  constructor(private formService: FormService, private fb: UntypedFormBuilder, private loaderService: LoaderService) { }

  ngOnInit() {
    const currentDate = Functions.currentDate();
    const currentMonthValue = new Date().getMonth() + 1; // Months are 0-based in JavaScript, so +1 for correct month
    const defaultMonthValue = currentMonthValue < 10 ? `0${currentMonthValue}` : currentMonthValue.toString();
    this.group = this.fb.group({
      year: [currentDate.year, COMMON_VALIDATORS.validators.requiredOnly],
      month: [defaultMonthValue, COMMON_VALIDATORS.validators.requiredOnly],
    });

    this.loaderService.startLoader();
    this.subscription.push(
      this.formService.getData<GetProductiveReportDataResponse>(this.url)
        .pipe(
          finalize(() => this.loaderService.stopLoader()),
        )
        .subscribe(resp => {
          if (resp && resp.status === REQUEST_STATUS.SUCCESS) {
            this.monthList = resp.data.monthList;
            this.yearList = resp.data.yearList;
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
          null, environment.downloadAttendanceUrl)
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

}

