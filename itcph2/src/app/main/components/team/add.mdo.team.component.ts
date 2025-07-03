import { AfterViewInit, Component, OnDestroy, OnInit } from '@angular/core';
import { UntypedFormBuilder, UntypedFormGroup } from '@angular/forms';
import { Subscription } from 'rxjs';
import { finalize } from 'rxjs/operators';
import { TranslateService } from '@ngx-translate/core';

import { DropdownList, GetAddTeamDataResponse } from 'src/app/core/interfaces/http-response.interface';
import { CONSTANTS, CONTROL_CONFIG, REQUEST_STATUS, STATIC_MODULES } from 'src/app/app.constants';
import { COMMON_VALIDATORS, LOGIN_VALIDATORS, TEAM_VALIDATORS } from 'src/app/core/validators/validations.list';
import { FormService } from 'src/app/core/services/form.service';
import { CanGoBackGuard } from 'src/app/core/guards/can-go-back-guard.service';
import { LoaderService } from 'src/app/core/services/loader.service';
import { environment } from 'src/environments/environment';
import { InputConfig, OnRadioChangeEvent } from 'src/app/core/interfaces/helpers.interface';
import { CUSTOM_VALIDATION_LENGTH } from 'src/app/core/validators/validators.list';
import { ToastrService } from 'src/app/core/services/toastr.service';
import { HttpRequestResponse } from 'src/app/core/interfaces/common.interface';

@Component({
  templateUrl: './add.mdo.team.component.html'
})
export class AddMdoTeamComponent implements AfterViewInit, OnInit, OnDestroy {
  private subscription: Subscription[] = [];
  private addMethodOptions = ['app.team.add.usingNames', 'app.team.add.usingIndex', 'app.team.add.endError'];
  private endError = '';
  form: UntypedFormGroup;
  projectOptions: DropdownList[] = [];
  branchOptions: DropdownList[] = [];
  circleOptions: DropdownList[] = [];
  sectionOptions: DropdownList[] = [];
  wdCodeOptions: DropdownList[] = [];
  teamOptions: DropdownList[] = [];
  aeNameOptions: DropdownList[] = [];
  jsonName: string;
  amNameOptions: DropdownList[] = [];
  dsTypeOptions: DropdownList[] = [];
  jsonIdOptions: DropdownList[] = [];
  addMethodList: DropdownList<number>[] = [];
  separatorList: DropdownList[] = [];
  accessOptions: DropdownList[] = [];
  addResponse: HttpRequestResponse = null;
  passwordTooltip = CONSTANTS.STRONG_PASSWORD_FUNC ? 'tooltip.strongPasswordBody' : 'tooltip.password';
  errorMessages = {
    branch: COMMON_VALIDATORS.messages.dropdown('Branch'),
    team: COMMON_VALIDATORS.messages.requiredOnly('team'),
    // section: COMMON_VALIDATORS.messages.requiredOnly('Section'),
    jsonId: COMMON_VALIDATORS.messages.requiredOnly('JSON ID'),
    dsType: COMMON_VALIDATORS.messages.requiredOnly('Surveyor Type'),
    endIndex: TEAM_VALIDATORS.messages.index('End Index'),
    json: TEAM_VALIDATORS.messages.json,
    password: LOGIN_VALIDATORS.messages.password,
    project: COMMON_VALIDATORS.messages.dropdown('Project Name'),
    separator: COMMON_VALIDATORS.messages.dropdown('Separator'),
    startIndex: TEAM_VALIDATORS.messages.index('Start Index'),
    dsName: COMMON_VALIDATORS.messages.name('Surveyor Name', CUSTOM_VALIDATION_LENGTH.TEAM_NAME_MAXLENGTH),
    wdCode: TEAM_VALIDATORS.messages.wdCode,
    aeName: COMMON_VALIDATORS.messages.requiredOnly('AE Name'),
    accessType: COMMON_VALIDATORS.messages.requiredOnly('Access Type'),
    // amNumber: COMMON_VALIDATORS.messages.phone('AE Phone'),
  };
  validators = {
    index: TEAM_VALIDATORS.validators.index,
    separator: COMMON_VALIDATORS.validators.dropdown,
    dsName: TEAM_VALIDATORS.validators.name,
  };
  isDisabled = false;
  controlVisibility = {
    hideTeamsUsingIndex: true,
    hideTeamsUsingName: false,
  };
  teamsConfig: InputConfig[] = [
    {
      controlName: 'dsName', errorMessages: COMMON_VALIDATORS.messages.name('MDO Name', CUSTOM_VALIDATION_LENGTH.TEAM_NAME_MAXLENGTH),
      label: 'app.team.add.mdoName', required: true, type: CONTROL_CONFIG.INPUT_BOX,
      validators: TEAM_VALIDATORS.validators.name,
    },
    {
      controlName: 'username', errorMessages: LOGIN_VALIDATORS.messages.username(CUSTOM_VALIDATION_LENGTH.USERNAME_MAXLENGTH),
      label: 'auth.login.form.username', type: CONTROL_CONFIG.INPUT_BOX,
      validators: LOGIN_VALIDATORS.validators.username(true, CUSTOM_VALIDATION_LENGTH.USERNAME_MAXLENGTH),
    },
    {
      controlName: 'dsPhone', errorMessages: COMMON_VALIDATORS.messages.mobile('MDO Phone'),
      label: 'app.team.add.mdoNumber', type: CONTROL_CONFIG.INPUT_BOX, required: true,
      validators: COMMON_VALIDATORS.validators.mobile(true),
    },
  ];

  constructor(private fb: UntypedFormBuilder, private formService: FormService,
    private canGoBackGuard: CanGoBackGuard, private loaderService: LoaderService,
    private translate: TranslateService, private toastr: ToastrService) { }

  ngOnInit() {
    this.subscription.push(
      this.translate.get(this.addMethodOptions)
        .subscribe(translatedMsg => {
          this.addMethodList = [
            { label: translatedMsg[this.addMethodOptions[0]], value: 1 },
            // { label: translatedMsg[this.addMethodOptions[1]], value: 2 }
          ];
          this.endError = translatedMsg[this.addMethodOptions[2]];
        })
    );

    this.form = this.fb.group({
      addMethodType: [1],
      branch: [null, COMMON_VALIDATORS.validators.dropdown],
      circle: [null],
      section: [null],
      // wdCode: ['', COMMON_VALIDATORS.validators.requiredOnly],
      dsType: ['', COMMON_VALIDATORS.validators.requiredOnly],
      jsonId: ['', COMMON_VALIDATORS.validators.requiredOnly],
      aeName: [null],
      // aeNumber: ['', LOGIN_VALIDATORS.validators.phone(false, VALIDATION_LENGTH.MOBILE_MINLENGTH, VALIDATION_LENGTH.MOBILE_MAXLENGTH)],
      // amName: ['', COMMON_VALIDATORS.validators.requiredOnly],
      // amNumber: ['', LOGIN_VALIDATORS.validators.phone(false, VALIDATION_LENGTH.MOBILE_MINLENGTH, VALIDATION_LENGTH.MOBILE_MAXLENGTH)],
      // endIndex: ['', TEAM_VALIDATORS.validators.index],
      json: ['', TEAM_VALIDATORS.validators.json],
      password: ['', LOGIN_VALIDATORS.validators.passwordOptional],
      project: [null, COMMON_VALIDATORS.validators.dropdown],
      // separator: ['', COMMON_VALIDATORS.validators.dropdown],
      // startIndex: ['', TEAM_VALIDATORS.validators.index],
      team: [null],
      teams: this.fb.array([]),
      wdCode: ['', TEAM_VALIDATORS.validators.wdCode],
      accessType: ['', COMMON_VALIDATORS.validators.requiredOnly],
    });

    this.loaderService.startLoader();
    this.subscription.push(
      this.formService.getData<GetAddTeamDataResponse>(environment.getTeamDataUrl)
        .pipe(
          finalize(() => this.loaderService.stopLoader())
        )
        .subscribe(resp => {
          if (resp && resp.status === REQUEST_STATUS.SUCCESS) {
            this.projectOptions = resp.data.projectList;
            this.branchOptions = resp.data.branchList;
            this.circleOptions = resp.data.circleList;
            this.sectionOptions = resp.data.sectionList;
            this.wdCodeOptions = resp.data.wdList;
            this.aeNameOptions = resp.data.aeNameList;
            // this.amNameOptions = resp.data.amNameList;
            this.dsTypeOptions = resp.data.dsTypeList;
            this.teamOptions = resp.data.teamList;
            this.jsonIdOptions = resp.data.jsonIdList;
            this.separatorList = resp.data.separatorList;
            this.accessOptions = resp.data.accessList;
          }
        })
    );
  }

  ngOnDestroy() {
    this.subscription.forEach(sub => sub.unsubscribe());
  }

  ngAfterViewInit() {
    this.canGoBackGuard.markAsPristine();

    this.subscription.push(
      this.form.valueChanges
        .subscribe(() => this.canGoBackGuard.markAsDirty())
    );
  }

  getCircle() {
    this.circleValue = null;
    this.sectionValue = null;
    this.wdCodeValue = null;
    this.dsTypeValue = null;
    this.aeNameValue = null;
    this.teamValue = null;
    this.loaderService.startLoader();
    this.subscription.push(
      this.formService.customActionCall<GetAddTeamDataResponse>(STATIC_MODULES.custom.getCircle, { branch: this.form.get('branch').value },
        null, environment.getTeamDataUrl)
        .pipe(finalize(() => this.loaderService.stopLoader()))
        .subscribe(resp => {
          if (resp && resp.status === REQUEST_STATUS.SUCCESS) {
            this.circleOptions = resp.data.circleList;
            this.sectionOptions = resp.data.sectionList;
          }
        })
    );
  }

  getSection() {
    this.sectionValue = null;
    this.wdCodeValue = null;
    this.dsTypeValue = null;
    this.aeNameValue = null;
    this.teamValue = null;
    this.loaderService.startLoader();
    this.subscription.push(
      this.formService.customActionCall<GetAddTeamDataResponse>(STATIC_MODULES.custom.getSection, { branch: this.form.get('branch').value, circle: this.form.get('circle').value },
        null, environment.getTeamDataUrl)
        .pipe(finalize(() => this.loaderService.stopLoader()))
        .subscribe(resp => {
          if (resp && resp.status === REQUEST_STATUS.SUCCESS) {
            this.sectionOptions = resp.data.sectionList;
          }
        })
    );
  }

  getWdCode() {
    this.dsTypeValue = null;
    this.wdCodeValue = null;
    this.teamValue = null;
    this.loaderService.startLoader();
    this.subscription.push(
      this.formService.customActionCall<GetAddTeamDataResponse>(STATIC_MODULES.custom.getWDList, { section: this.form.get('section').value },
        null, environment.getTeamDataUrl)
        .pipe(finalize(() => this.loaderService.stopLoader()))
        .subscribe(resp => {
          if (resp && resp.status === REQUEST_STATUS.SUCCESS) {
            this.wdCodeOptions = resp.data.wdList;
          }
        })
    );
  }

  getTeamType() {
    this.dsTypeValue = null;
    this.teamValue = null;
    this.loaderService.startLoader();
    this.subscription.push(
      this.formService.customActionCall<GetAddTeamDataResponse>(STATIC_MODULES.custom.getTeamsTypeList, { wdCode: this.form.get('wdCode').value },
        null, environment.getTeamDataUrl)
        .pipe(finalize(() => this.loaderService.stopLoader()))
        .subscribe(resp => {
          if (resp && resp.status === REQUEST_STATUS.SUCCESS) {
            this.dsTypeOptions = resp.data.dsTypeList;
          }
        })
    );
  }

  getAeName() {
    this.loaderService.startLoader();
    this.subscription.push(
      this.formService.customActionCall<GetAddTeamDataResponse>(STATIC_MODULES.custom.getAeName, { section: this.form.get('section').value },
        null, environment.getTeamDataUrl)
        .pipe(finalize(() => this.loaderService.stopLoader()))
        .subscribe(resp => {
          if (resp && resp.status === REQUEST_STATUS.SUCCESS) {
            this.aeNameOptions = resp.data.aeNameList;
          }
        })
    );
  }

  getTeams() {
    this.teamValue = null;
    this.loaderService.startLoader();
    this.subscription.push(
      this.formService.customActionCall<GetAddTeamDataResponse>(STATIC_MODULES.custom.getTeamsList, { dsType: this.form.get('dsType').value, wdCode: this.form.get('wdCode').value },
        null, environment.getTeamDataUrl)
        .pipe(finalize(() => this.loaderService.stopLoader()))
        .subscribe(resp => {
          if (resp && resp.status === REQUEST_STATUS.SUCCESS) {
            this.teamOptions = resp.data.teamList;
          }
        })
    );
  }

  getJsonName() {
    this.loaderService.startLoader();
    this.subscription.push(
      this.formService.customActionCall<GetAddTeamDataResponse>(STATIC_MODULES.custom.getJson, { jsonId: this.form.get('jsonId').value },
        null, environment.getTeamDataUrl)
        .pipe(finalize(() => this.loaderService.stopLoader()))
        .subscribe(resp => {
          if (resp && resp.status === REQUEST_STATUS.SUCCESS) {
            this.jsonName = resp.data.jsonName;
            if (this.jsonName) {
              this.form.get('json').setValue(this.jsonName);
            }
          }
        })
    );
  }

  addTeam() {
    if (this.form && !this.isDisabled && this.type === 1) {
      this.isDisabled = true;
      this.addResponse = null;

      this.subscription.push(
        this.formService.addData<string>(this.form, null, environment.addTeamUrl)
          .pipe(
            finalize(() => this.isDisabled = false)
          )
          .subscribe(resp => {
            if (resp && resp.status === REQUEST_STATUS.SUCCESS) {
              this.canGoBackGuard.markAsPristine();
            } else {
              this.addResponse = resp;
            }
          })
      );
    } else {
      if (this.type === 2 && this.form.get('endIndex').value < this.form.get('startIndex').value) {
        this.toastr.toastr({ type: 'error', msg: this.endError });
      }
    }
  }

  set circleValue(value: string) {
    this.circleOptions = [];
    this.form.get('circle').setValue(value);
  }
  set sectionValue(value: string) {
    this.sectionOptions = [];
    this.form.get('section').setValue(value);
  }
  set wdCodeValue(value: string) {
    this.wdCodeOptions = [];
    this.form.get('wdCode').setValue(value);
  }
  set dsTypeValue(value: string) {
    this.dsTypeOptions = [];
    this.form.get('dsType').setValue(value);
  }
  set aeNameValue(value: string) {
    this.wdCodeOptions = [];
    this.form.get('aeName').setValue(value);
  }
  set teamValue(value: string) {
    this.teamOptions = [];
    this.form.get('team').setValue(value);
  }

  resetControls(type: OnRadioChangeEvent) {
    switch (type.value) {
      case 2:
        // Using Index
        this.controlVisibility = {
          hideTeamsUsingIndex: false,
          hideTeamsUsingName: true,
        };
        break;
      default:
        // Using Names
        this.controlVisibility = {
          hideTeamsUsingIndex: true,
          hideTeamsUsingName: false,
        };
        break;
    }
  }

  get type() {
    return this.form.get('addMethodType').value;
  }
}
