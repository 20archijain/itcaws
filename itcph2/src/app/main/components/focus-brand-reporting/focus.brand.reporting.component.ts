import { Component, OnDestroy, OnInit } from '@angular/core';
import { UntypedFormBuilder, UntypedFormGroup } from '@angular/forms';
import { Subscription } from 'rxjs';
import { finalize } from 'rxjs/operators';

import { environment } from 'src/environments/environment';
import { FormService } from 'src/app/core/services/form.service';
import { DropdownList, GetAddTeamDataResponse } from 'src/app/core/interfaces/http-response.interface';
import { EditConfig } from 'src/app/core/interfaces/helpers.interface';
import { LoaderService } from 'src/app/core/services/loader.service';
import { CONTROL_CONFIG, REQUEST_STATUS } from 'src/app/app.constants';
import { COMMON_VALIDATORS } from 'src/app/core/validators/validations.list';
import { Functions } from 'src/app/core/utils/functions.list';

@Component({
    templateUrl: './focus.brand.reporting.component.html',
    standalone: false
})
export class FocusBrandReportingComponent implements OnInit, OnDestroy {
  private subscription: Subscription[] = [];
  header: string[] = [];
  body: string[] = [];
  editConfig: EditConfig[] = [];
  branchOptions: DropdownList[] = [];
  yearOptions: DropdownList[] = [];
  monthOptions: DropdownList[] = [];
  sortOptions: DropdownList[] = [];
  form: UntypedFormGroup;
  url = environment.viewTeamsUrl;
  isExportBtnDisabled = false;

  errorMessages = {
      branch: COMMON_VALIDATORS.messages.requiredOnly('Branch Name'),
      year: COMMON_VALIDATORS.messages.requiredOnly('Year'),
      month: COMMON_VALIDATORS.messages.requiredOnly('Month'),
    };


  constructor(private formService: FormService, private fb: UntypedFormBuilder, private loaderService: LoaderService) { }

  ngOnInit() {
    const currentDate = Functions.currentDate();
    const currentMonthValue = new Date().getMonth() + 1; // Months are 0-based in JavaScript, so +1 for correct month
    const defaultMonthValue = currentMonthValue < 10 ? `0${currentMonthValue}` : currentMonthValue.toString();
    this.form = this.fb.group({
      branch: ['',  COMMON_VALIDATORS.validators.requiredOnly],
      year: [currentDate.year, COMMON_VALIDATORS.validators.requiredOnly],
      month: [defaultMonthValue, COMMON_VALIDATORS.validators.requiredOnly],
    });

    this.getInitialData();
  }

  ngOnDestroy() {
    this.subscription.forEach(sub => sub.unsubscribe());
  }

  getInitialData() {
    this.loaderService.startLoader();
    this.subscription.push(
      this.formService.getData<GetAddTeamDataResponse>(environment.getTeamDataUrl)
        .pipe(
          finalize(() => this.loaderService.stopLoader())
        )
        .subscribe(resp => {
          if (resp && resp.status === REQUEST_STATUS.SUCCESS) {
            this.sortOptions = resp.data.sortOptions;
            this.branchOptions = resp.data.branchList;
            this.yearOptions = resp.data.yearList;
            this.monthOptions = resp.data.monthList;
            this.header = resp.data.viewHeader;
            this.body = resp.data.viewBody;

            this.editConfig = [
              {
                controlName: 'id', label: '', type: CONTROL_CONFIG.REC_ID,
              },
              {
                controlName: 'summary_column_name',
                errorMessages: COMMON_VALIDATORS.messages.requiredOnly("Product Name"),
                label: 'app.focusBrandReporting.product_name', required: true, type: CONTROL_CONFIG.SELECT_BOX,
                validators: COMMON_VALIDATORS.validators.requiredOnly, options : resp.data.productList
              },
            ];
          }
        })
    );
  }
}
