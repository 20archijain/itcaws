import { Component, OnDestroy, OnInit } from '@angular/core';
import { UntypedFormBuilder, UntypedFormGroup } from '@angular/forms';
import { Subscription } from 'rxjs';
import { finalize } from 'rxjs/operators';

import { environment } from 'src/environments/environment';
import { CONTROL_CONFIG, REQUEST_STATUS } from 'src/app/app.constants';
import { FormService } from 'src/app/core/services/form.service';
import { DropdownList, ViewProjectsResponse } from 'src/app/core/interfaces/http-response.interface';
import { EditConfig } from 'src/app/core/interfaces/helpers.interface';
import { COMMON_VALIDATORS } from 'src/app/core/validators/validations.list';
import { CUSTOM_VALIDATION_LENGTH } from 'src/app/core/validators/validators.list';
import { LoaderService } from 'src/app/core/services/loader.service';

@Component({
    templateUrl: './view.project.component.html',
    standalone: false
})
export class ViewProjectComponent implements OnDestroy, OnInit {
  private subscription: Subscription[] = [];
  header: string[] = [];
  body: string[] = [];
  url = environment.viewProjectsUrl;
  sortOptions: DropdownList[] = [];
  clientOptions: DropdownList[] = [];
  form: UntypedFormGroup;
  editConfig: EditConfig[] = [];

  constructor(private formService: FormService, private fb: UntypedFormBuilder, private loaderService: LoaderService) { }

  ngOnInit() {
    this.form = this.fb.group({
      client: [],
      projectName: [''],
    });

    this.getInitialData();
  }

  getInitialData() {
    this.loaderService.startLoader();

    this.subscription.push(
      this.formService.getData<ViewProjectsResponse>(this.url)
        .pipe(
          finalize(() => this.loaderService.stopLoader())
        )
        .subscribe(resp => {
          if (resp && resp.status === REQUEST_STATUS.SUCCESS) {
            this.sortOptions = resp.data.sortOptions;
            this.clientOptions = resp.data.clientList;
            this.header = resp.data.viewHeader;
            this.body = resp.data.viewBody;

            this.editConfig = [
              {
                controlName: 'id', label: '', type: CONTROL_CONFIG.REC_ID,
              },
              {
                controlName: 'clientId', label: '', type: CONTROL_CONFIG.REC_ID,
              },
              {
                controlName: 'projectName', errorMessages: COMMON_VALIDATORS.messages.name('Project Name'),
                label: 'app.project.add.name', required: true, type: CONTROL_CONFIG.INPUT_BOX,
                validators: COMMON_VALIDATORS.validators.name(CUSTOM_VALIDATION_LENGTH.PROJECT_NAME_MAXLENGTH),
              },
              {
                controlName: 'landingPageId', errorMessages: COMMON_VALIDATORS.messages.dropdown('Landing Page'),
                label: 'app.user.user.add.landingPage', options: resp.data.landingPageList, required: true,
                type: CONTROL_CONFIG.SELECT_BOX, validators: COMMON_VALIDATORS.validators.dropdown,
              },
            ];
          } else {
            this.sortOptions = [];
          }
        })
    );
  }

  ngOnDestroy() {
    this.subscription.forEach(sub => sub.unsubscribe());
  }
}
