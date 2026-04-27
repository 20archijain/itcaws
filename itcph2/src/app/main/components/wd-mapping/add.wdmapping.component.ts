import { AfterViewInit, Component, OnDestroy, OnInit } from '@angular/core';
import { UntypedFormBuilder, UntypedFormGroup } from '@angular/forms';
import { Subscription } from 'rxjs';
import { finalize } from 'rxjs/operators';

import { FormService } from 'src/app/core/services/form.service';
import { REQUEST_STATUS } from 'src/app/app.constants';
import { environment } from 'src/environments/environment';
import { COMMON_VALIDATORS } from 'src/app/core/validators/validations.list';
import { CanGoBackGuard } from 'src/app/core/guards/can-go-back-guard.service';
import { DropdownList, GetAddProjectDataResponse } from 'src/app/core/interfaces/http-response.interface';
import { LoaderService } from 'src/app/core/services/loader.service';

@Component({
  templateUrl: './add.wdmapping.component.html',
  standalone: false,
})
export class AddWdMappingComponent implements AfterViewInit, OnInit, OnDestroy {
  private subscription: Subscription[] = [];
  form!: UntypedFormGroup;
  errorMessages = {
    district: COMMON_VALIDATORS.messages.requiredOnly('District'),
    branch: COMMON_VALIDATORS.messages.requiredOnly('Branch'),
    wd_code: COMMON_VALIDATORS.messages.requiredOnly('WD Code'),
  };
  isDisabled = false;
  districtOptions: DropdownList[] = [];
  branchOptions: DropdownList[] = [];
  landingPageOptions: DropdownList[] = [];
  checkKey = 'value';
  selectedRecords = [];

  constructor(private fb: UntypedFormBuilder, private formService: FormService,
    private canGoBackGuard: CanGoBackGuard, private loaderService: LoaderService) { }

  ngOnInit() {
    this.form = this.fb.group({
      district: [null, COMMON_VALIDATORS.validators.requiredOnly],
      branch: [null, COMMON_VALIDATORS.validators.requiredOnly],
      circle: [""],
      circle_name: [""],
      section: [""],
      section_name: [""],
      wd_code: ["", COMMON_VALIDATORS.validators.requiredOnly],
      wd_firm_name: [""],
      wd_market: [""],
      wd_pop_group: [""],
    });

    this.initialData();
  }

  ngAfterViewInit() {
    this.canGoBackGuard.markAsPristine();

    this.subscription.push(
      this.form.valueChanges
        .subscribe(() => this.canGoBackGuard.markAsDirty())
    );
  }

  ngOnDestroy() {
    this.subscription.forEach(sub => sub.unsubscribe());
  }

  initialData() {
    this.loaderService.startLoader();
    this.subscription.push(
      this.formService.getData<GetAddProjectDataResponse>(environment.getAddProjectDataUrl)
        .pipe(
          finalize(() => this.loaderService.stopLoader())
        )
        .subscribe(response => {
          if (response && response.status === REQUEST_STATUS.SUCCESS && response.data) {
            this.districtOptions = response.data.districtList;
            this.branchOptions = response.data.branchList;
          }
        })
    );
  }

  addProject() {
    if (this.form.valid && !this.isDisabled) {
      this.isDisabled = true;
      this.subscription.push(
        this.formService.addData<string>(this.form, null, environment.addProjectUrl)
          .pipe(
            finalize(() => this.isDisabled = false)
          )
          .subscribe(resp => {
            if (resp && resp.status === REQUEST_STATUS.SUCCESS) {
              this.form.reset();
              this.selectedRecords = [];
              this.canGoBackGuard.markAsPristine();
            }
          })
      );
    }
  }

}
