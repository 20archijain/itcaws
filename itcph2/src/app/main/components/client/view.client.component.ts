import { Component, OnDestroy, OnInit } from '@angular/core';
import { UntypedFormBuilder, UntypedFormGroup } from '@angular/forms';
import { Subscription } from 'rxjs';
import { finalize } from 'rxjs/operators';

import { environment } from 'src/environments/environment';
import { CONTROL_CONFIG, REQUEST_STATUS } from 'src/app/app.constants';
import { FormService } from 'src/app/core/services/form.service';
import { DropdownList, ViewClientsResponse } from 'src/app/core/interfaces/http-response.interface';
import { EditConfig } from 'src/app/core/interfaces/helpers.interface';
import { CLIENT_VALIDATORS, COMMON_VALIDATORS } from 'src/app/core/validators/validations.list';
import { CUSTOM_VALIDATION_LENGTH } from 'src/app/core/validators/validators.list';
import { LoaderService } from 'src/app/core/services/loader.service';

@Component({
  templateUrl: './view.client.component.html'
})
export class ViewClientComponent implements OnDestroy, OnInit {
  private subscription: Subscription[] = [];
  header: string[] = [];
  body: string[] = [];
  url = environment.viewClientsUrl;
  sortOptions: DropdownList[] = [];
  form: UntypedFormGroup;
  editConfig: EditConfig[] = [
    {
      controlName: 'id', label: '', type: CONTROL_CONFIG.REC_ID,
    },
    {
      controlName: 'name', errorMessages: COMMON_VALIDATORS.messages.name('Client Name'),
      label: 'app.client.add.name', required: true, type: CONTROL_CONFIG.INPUT_BOX,
      validators: COMMON_VALIDATORS.validators.name(CUSTOM_VALIDATION_LENGTH.CLIENT_NAME_MAXLENGTH),
    },
    {
      controlName: 'desc', errorMessages: CLIENT_VALIDATORS.messages.desc,
      label: 'app.client.add.desc', required: true, type: CONTROL_CONFIG.DESC_BOX,
      validators: CLIENT_VALIDATORS.validators.desc,
    },
  ];

  constructor(private formService: FormService, private fb: UntypedFormBuilder, private loaderService: LoaderService) { }

  ngOnInit() {
    this.form = this.fb.group({
      name: ['']
    });

    this.getInitialData();
  }

  getInitialData() {
    this.loaderService.startLoader();
    this.subscription.push(
      this.formService.getData<ViewClientsResponse>(this.url)
        .pipe(
          finalize(() => this.loaderService.stopLoader())
        )
        .subscribe(resp => {
          if (resp && resp.status === REQUEST_STATUS.SUCCESS) {
            this.sortOptions = resp.data.sortOptions;
            this.header = resp.data.viewHeader;
            this.body = resp.data.viewBody;
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
