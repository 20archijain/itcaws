import { Component, OnDestroy, OnInit } from '@angular/core';
import { UntypedFormBuilder, UntypedFormGroup } from '@angular/forms';
import { Subscription } from 'rxjs';
import { finalize } from 'rxjs/operators';

import { environment } from 'src/environments/environment';
import { FormService } from 'src/app/core/services/form.service';
import { DropdownList, GetAddTeamDataResponse } from 'src/app/core/interfaces/http-response.interface';
import { CsvDataFormat, EditConfig } from 'src/app/core/interfaces/helpers.interface';
import { LoaderService } from 'src/app/core/services/loader.service';
import { CONTROL_CONFIG, REQUEST_STATUS, STATIC_MODULES } from 'src/app/app.constants';
import { COMMON_VALIDATORS, TEAM_VALIDATORS } from 'src/app/core/validators/validations.list';
import { CUSTOM_VALIDATION_LENGTH } from 'src/app/core/validators/validators.list';
import { Functions } from 'src/app/core/utils/functions.list';

@Component({
  templateUrl: './mdoview.team.component.html'
})
export class MDOViewTeamComponent implements OnInit, OnDestroy {
  private subscription: Subscription[] = [];
  header: string[] = [];
  body: string[] = [];
  editConfig: EditConfig[] = [];
  sortOptions: DropdownList[] = [];
  mdoTypeOptions: DropdownList[] = [];
  branchOptions: DropdownList[] = [];
  statusOptions: DropdownList[] = [];
  accessOptions: DropdownList[] = [];
  form: UntypedFormGroup;
  url = environment.viewTeamsUrl;
  isExportBtnDisabled = false;

  constructor(private formService: FormService, private fb: UntypedFormBuilder, private loaderService: LoaderService) { }

  ngOnInit() {
    this.form = this.fb.group({
      branch: [],
      json: [''],
      password: [''],
      mdoType: [''],
      mdoName: [''],
      phone: [''],
      wdCode: [''],
      teamStatus: [''],
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
            this.mdoTypeOptions = resp.data.mdoTypeList;
            this.statusOptions = resp.data.statusList;
            this.accessOptions = resp.data.accessList;
            this.header = resp.data.viewHeader;
            this.body = resp.data.viewBody;

            this.editConfig = [
              {
                controlName: 'id', label: '', type: CONTROL_CONFIG.REC_ID,
              },
              {
                controlName: 'projectId', label: '', type: CONTROL_CONFIG.REC_ID,
              },
              {
                controlName: 'recId', label: '', type: CONTROL_CONFIG.REC_ID,
              },
              {
                controlName: 'teamName',
                errorMessages: COMMON_VALIDATORS.messages.name('Team Name', CUSTOM_VALIDATION_LENGTH.TEAM_NAME_MAXLENGTH),
                label: 'app.team.add.name', required: true, type: CONTROL_CONFIG.INPUT_BOX,
                validators: TEAM_VALIDATORS.validators.name,
              },
              {
                controlName: 'mobile', errorMessages: COMMON_VALIDATORS.messages.mobile('DS Phone'),
                label: 'app.team.add.dsNumber', type: CONTROL_CONFIG.INPUT_BOX, required: true,
                validators: COMMON_VALIDATORS.validators.mobile(true),
              },
              {
                controlName: 'type', label: 'Access Type', onChange: this.onUserTypeChange.bind(this),
                options: this.accessOptions, type: CONTROL_CONFIG.RADIO_BOX,
              },
              {
                controlName: 'wdCodes', errorMessages: COMMON_VALIDATORS.messages.requiredOnly('WD Code'), hide: true,
                label: 'app.team.view.wdCode', multiple: true, options: resp.data.wdCodeList,
                required: true, type: CONTROL_CONFIG.SELECT_BOX, validators: COMMON_VALIDATORS.validators.requiredOnly,
              },
              {
                controlName: 'team', errorMessages: COMMON_VALIDATORS.messages.requiredOnly('Team'), hide: true,
                label: 'app.team.view.team', multiple: true, options: resp.data.teamList,
                required: true, type: CONTROL_CONFIG.SELECT_BOX, validators: COMMON_VALIDATORS.validators.requiredOnly,
              },
              // {
              //   controlName: 'password', errorMessages: LOGIN_VALIDATORS.messages.password,
              //   label: 'auth.login.form.password', required: true, type: CONTROL_CONFIG.INPUT_BOX,
              //   validators: LOGIN_VALIDATORS.validators.password,
              // },
              // {
              //   controlName: 'json', errorMessages: TEAM_VALIDATORS.messages.json,
              //   label: 'app.team.add.json', required: true, type: CONTROL_CONFIG.INPUT_BOX,
              //   validators: TEAM_VALIDATORS.validators.json,
              // }
            ];
          }
        })
    );
  }

  exportTeams() {
    if (!this.isExportBtnDisabled) {
      this.isExportBtnDisabled = true;
      this.loaderService.startLoader();

      this.subscription.push(
        this.formService.customActionCall<CsvDataFormat>(STATIC_MODULES.custom.getDownloadData,
          this.form.getRawValue(), null, environment.downloadDataUrl)
          .pipe(
            finalize(() => {
              this.loaderService.stopLoader();
              this.isExportBtnDisabled = false;
            })
          )
          .subscribe(resp => {
            if (resp && resp.status === REQUEST_STATUS.SUCCESS) {
              Functions.createCSV(resp.data);
            }
          })
      );
    }
  }

  onUserTypeChange(form: UntypedFormGroup) {
    const type = form.get('type').value;

    switch (+type) {
      case 1:
        // WD CODE
        this.editConfig[6].hide = false;
        this.editConfig[7].hide = true;
        break;
      case 2:
        // Teams
        this.editConfig[6].hide = true;
        this.editConfig[7].hide = false;
        break;
    }
  }
}
