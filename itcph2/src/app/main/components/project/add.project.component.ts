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
import { CUSTOM_VALIDATION_LENGTH } from 'src/app/core/validators/validators.list';
import { LoaderService } from 'src/app/core/services/loader.service';
import { ChekboxOutput } from 'src/app/core/interfaces/helpers.interface';

@Component({
  templateUrl: './add.project.component.html'
})
export class AddProjectComponent implements AfterViewInit, OnInit, OnDestroy {
  private subscription: Subscription[] = [];
  form: UntypedFormGroup;
  errorMessages = {
    client: COMMON_VALIDATORS.messages.dropdown('Client Name'),
    landingPage: COMMON_VALIDATORS.messages.dropdown('Landing Page'),
    projectName: COMMON_VALIDATORS.messages.name('Project Name'),
  };
  isDisabled = false;
  clientOptions: DropdownList[] = [];
  landingPageOptions: DropdownList[] = [];
  checkboxOptions: DropdownList[] = [];
  checkKey = 'value';
  selectedRecords = [];

  constructor(private fb: UntypedFormBuilder, private formService: FormService,
    private canGoBackGuard: CanGoBackGuard, private loaderService: LoaderService) { }

  ngOnInit() {
    this.form = this.fb.group({
      checkboxList: [],
      client: [null, COMMON_VALIDATORS.validators.dropdown],
      landingPage: [null, COMMON_VALIDATORS.validators.dropdown],
      projectName: ['', COMMON_VALIDATORS.validators.name(CUSTOM_VALIDATION_LENGTH.PROJECT_NAME_MAXLENGTH)],
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
          if (response && response.status === REQUEST_STATUS.SUCCESS) {
            this.clientOptions = response.data.clientList;
            this.landingPageOptions = response.data.landingPageList;
            this.checkboxOptions = response.data.checkboxList;
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

  isChecked(option: DropdownList) {
    return this.selectedRecords ? this.selectedRecords.indexOf(option[this.checkKey]) > -1 : false;
  }

  emitSelectedRecords($event: ChekboxOutput) {
    this.selectedRecords = $event.selectedRecords;
    if (this.form.get('checkboxList')) {
      this.form.get('checkboxList').setValue(this.selectedModules);
    }
  }

  get selectedModules() {
    // eslint-disable-next-line prefer-spread
    return [].concat.apply([], this.selectedRecords);
  }
}
