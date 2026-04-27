import { Component, OnDestroy, OnInit } from '@angular/core';
import { UntypedFormBuilder, UntypedFormGroup } from '@angular/forms';
import { Subscription } from 'rxjs';
import { finalize } from 'rxjs/operators';

import { environment } from 'src/environments/environment';
import { FormService } from 'src/app/core/services/form.service';
import { CONTROL_CONFIG, REQUEST_STATUS } from 'src/app/app.constants';
import { COMMON_VALIDATORS, MODULE_VALIDATORS } from 'src/app/core/validators/validations.list';
import { EditConfig } from 'src/app/core/interfaces/helpers.interface';
import { DropdownList, ModuleDataResponse } from 'src/app/core/interfaces/http-response.interface';
import { CUSTOM_VALIDATION_LENGTH } from 'src/app/core/validators/validators.list';
import { LoaderService } from 'src/app/core/services/loader.service';

@Component({
  templateUrl: './view.module.component.html',
  standalone: false,
})
export class ViewModuleComponent implements OnDestroy, OnInit {
  private subscription: Subscription[] = [];
  header: string[] = [];
  body: string[] = [];
  editConfig: EditConfig[] = [];
  sortOptions: DropdownList[] = [];
  form!: UntypedFormGroup;
  url = environment.viewModulesUrl;

  constructor(private formService: FormService, private fb: UntypedFormBuilder, private loaderService: LoaderService) { }

  ngOnInit() {
    this.form = this.fb.group({
      moduleCode: [''],
      moduleComponent: [''],
      moduleUrl: [''],
      name: [''],
    });

    this.getInitialData();
  }

  ngOnDestroy() {
    this.subscription.forEach(sub => sub.unsubscribe());
  }

  getInitialData() {
    this.loaderService.startLoader();
    this.subscription.push(
      this.formService.getData<ModuleDataResponse>(environment.getModuleDataUrl)
        .pipe(
          finalize(() => this.loaderService.stopLoader())
        )
        .subscribe(resp => {
          if (resp && resp.status === REQUEST_STATUS.SUCCESS && resp.data) {
            this.sortOptions = resp.data.sortOptions || [];
            this.header = resp.data.viewHeader || [];
            this.body = resp.data.viewBody || [];

            this.editConfig = [
              {
                controlName: 'id', label: '', type: CONTROL_CONFIG.REC_ID,
              },
              {
                controlName: 'name',
                errorMessages: COMMON_VALIDATORS.messages.name('Module Name', CUSTOM_VALIDATION_LENGTH.MODULE_NAME_MAXLENGTH),
                label: 'app.user.module.add.moduleName', required: true, type: CONTROL_CONFIG.INPUT_BOX,
                validators: COMMON_VALIDATORS.validators.name(CUSTOM_VALIDATION_LENGTH.MODULE_NAME_MAXLENGTH),
              },
              {
                controlName: 'modc', errorMessages: MODULE_VALIDATORS.messages.modc('Module Code'), label: 'app.user.module.add.moduleCode',
                required: true, type: CONTROL_CONFIG.INPUT_BOX, validators: MODULE_VALIDATORS.validators.modc,
              },
              {
                controlName: 'pmodc', errorMessages: MODULE_VALIDATORS.messages.modc('Parent Module Code'),
                label: 'app.user.module.add.parentModuleCode',
                required: true, type: CONTROL_CONFIG.INPUT_BOX, validators: MODULE_VALIDATORS.validators.modc,
              },
              {
                controlName: 'moduleComponent', errorMessages: MODULE_VALIDATORS.messages.moduleComponent,
                label: 'app.user.module.add.moduleComponent',
                type: CONTROL_CONFIG.INPUT_BOX, validators: MODULE_VALIDATORS.validators.moduleComponent,
              },
              {
                controlName: 'moduleActionCode', errorMessages: MODULE_VALIDATORS.messages.modc('Module Action Code'),
                label: 'app.user.module.add.moduleActionCode', options: resp.data.moduleActionCodeList,
                required: true, type: CONTROL_CONFIG.DROPDOWN_BOX,
                validators: MODULE_VALIDATORS.validators.moduleActionCode(CUSTOM_VALIDATION_LENGTH.MODULE_CODE_MAXLENGTH),
              },
              {
                controlName: 'modulePos', errorMessages: MODULE_VALIDATORS.messages.modulePos,
                label: 'app.user.module.add.modulePos', options: resp.data.modulePositionList,
                required: true, type: CONTROL_CONFIG.DROPDOWN_BOX,
                validators: MODULE_VALIDATORS.validators.moduleActionCode(CUSTOM_VALIDATION_LENGTH.MODULE_POSITION_MAXLENGTH),
              },
              {
                controlName: 'icon', errorMessages: MODULE_VALIDATORS.messages.icon, label: 'app.user.module.add.moduleIcon',
                type: CONTROL_CONFIG.INPUT_BOX, validators: MODULE_VALIDATORS.validators.icon,
              },
              {
                controlName: 'url', errorMessages: MODULE_VALIDATORS.messages.url, label: 'app.user.module.add.moduleUrl',
                type: CONTROL_CONFIG.INPUT_BOX, validators: MODULE_VALIDATORS.validators.url,
              },
              {
                controlName: 'sort', errorMessages: COMMON_VALIDATORS.messages.dropdown('Module Sort'),
                label: 'app.user.module.add.moduleSort',
                required: true, type: CONTROL_CONFIG.INPUT_BOX, validators: COMMON_VALIDATORS.validators.dropdown,
              },
              {
                controlName: 'breadcrumb', errorMessages: MODULE_VALIDATORS.messages.breadcrumb,
                label: 'app.user.module.add.breadcrumb', options: resp.data.breadcrumbList,
                required: true, type: CONTROL_CONFIG.DROPDOWN_BOX,
                validators: MODULE_VALIDATORS.validators.breadcrumb,
              },
            ];
          }
        })
    );
  }
}
