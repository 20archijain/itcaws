import { AfterViewInit, Component, OnDestroy, OnInit } from '@angular/core';
import { UntypedFormBuilder, UntypedFormGroup } from '@angular/forms';
import { Subscription } from 'rxjs';
import { finalize } from 'rxjs/operators';

import { FormService } from 'src/app/core/services/form.service';
import { REQUEST_STATUS } from 'src/app/app.constants';
import { environment } from 'src/environments/environment';
import { COMMON_VALIDATORS, MODULE_VALIDATORS } from 'src/app/core/validators/validations.list';
import { CanGoBackGuard } from 'src/app/core/guards/can-go-back-guard.service';
import { DropdownList, ModuleDataResponse } from 'src/app/core/interfaces/http-response.interface';
import { CUSTOM_VALIDATION_LENGTH } from 'src/app/core/validators/validators.list';
import { LoaderService } from 'src/app/core/services/loader.service';

@Component({
  templateUrl: './add.module.component.html'
})
export class AddModuleComponent implements AfterViewInit, OnInit, OnDestroy {
  private subscription: Subscription[] = [];
  form: UntypedFormGroup;
  moduleActionCodeOptions: DropdownList[] = [];
  modulePosOptions: DropdownList[] = [];
  breadcrumbOptions: DropdownList<number>[] = [];
  errorMessages = {
    breadcrumb: MODULE_VALIDATORS.messages.breadcrumb,
    icon: MODULE_VALIDATORS.messages.icon,
    id: MODULE_VALIDATORS.messages.id,
    modc: MODULE_VALIDATORS.messages.modc('Module Code'),
    moduleActionCode: MODULE_VALIDATORS.messages.modc('Module Action Code'),
    moduleComponent: MODULE_VALIDATORS.messages.moduleComponent,
    modulePos: MODULE_VALIDATORS.messages.modulePos,
    name: COMMON_VALIDATORS.messages.name('Module Name', CUSTOM_VALIDATION_LENGTH.MODULE_NAME_MAXLENGTH),
    pmodc: MODULE_VALIDATORS.messages.modc('Parent Module Code'),
    sort: COMMON_VALIDATORS.messages.dropdown('Module Sort'),
    url: MODULE_VALIDATORS.messages.url,
  };
  isDisabled = false;

  constructor(private fb: UntypedFormBuilder, private formService: FormService,
    private canGoBackGuard: CanGoBackGuard, private loaderService: LoaderService) { }

  ngOnInit() {
    this.form = this.fb.group({
      breadcrumb: ['', MODULE_VALIDATORS.validators.breadcrumb],
      icon: ['', MODULE_VALIDATORS.validators.icon],
      id: ['', MODULE_VALIDATORS.validators.id],
      modc: ['', MODULE_VALIDATORS.validators.modc],
      moduleActionCode: ['', MODULE_VALIDATORS.validators.moduleActionCode(CUSTOM_VALIDATION_LENGTH.MODULE_CODE_MAXLENGTH)],
      moduleComponent: ['', MODULE_VALIDATORS.validators.moduleComponent],
      modulePos: ['', MODULE_VALIDATORS.validators.moduleActionCode(CUSTOM_VALIDATION_LENGTH.MODULE_POSITION_MAXLENGTH)],
      name: ['', COMMON_VALIDATORS.validators.name(CUSTOM_VALIDATION_LENGTH.MODULE_NAME_MAXLENGTH)],
      pmodc: ['', MODULE_VALIDATORS.validators.modc],
      sort: ['', COMMON_VALIDATORS.validators.dropdown],
      url: ['', MODULE_VALIDATORS.validators.url],
    });

    this.loaderService.startLoader();
    this.subscription.push(
      this.formService.getData<ModuleDataResponse>(environment.getModuleDataUrl)
        .pipe(
          finalize(() => this.loaderService.stopLoader())
        )
        .subscribe(resp => {
          if (resp && resp.status === REQUEST_STATUS.SUCCESS) {
            this.moduleActionCodeOptions = resp.data.moduleActionCodeList;
            this.modulePosOptions = resp.data.modulePositionList;
            this.breadcrumbOptions = resp.data.breadcrumbList;
          } else {
            this.moduleActionCodeOptions = [];
            this.modulePosOptions = [];
          }
        })
    );

    this.canGoBackGuard.markAsPristine();
  }

  ngOnDestroy() {
    this.subscription.forEach(sub => sub.unsubscribe());
  }

  ngAfterViewInit() {
    this.subscription.push(
      this.form.valueChanges.subscribe(() => this.canGoBackGuard.markAsDirty())
    );
  }

  addModule() {
    if (!this.isDisabled && this.form.valid) {
      this.isDisabled = true;
      this.subscription.push(
        this.formService.addData<string>(this.form, null, environment.addModuleUrl)
          .pipe(
            finalize(() => this.isDisabled = false)
          )
          .subscribe(resp => {
            if (resp && resp.status === REQUEST_STATUS.SUCCESS) {
              this.canGoBackGuard.markAsPristine();
            }
          })
      );
    }
  }
}
