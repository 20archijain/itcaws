import { AfterViewInit, Component, OnDestroy, OnInit } from '@angular/core';
import { UntypedFormBuilder, UntypedFormGroup } from '@angular/forms';
import { Subscription } from 'rxjs';
import { finalize } from 'rxjs/operators';
import { TranslateService } from '@ngx-translate/core';

import { FormService } from 'src/app/core/services/form.service';
import { CONSTANTS, CUSTOM_VALIDATOR_KEYS, REQUEST_STATUS } from 'src/app/app.constants';
import { environment } from 'src/environments/environment';
import {
  COMMON_VALIDATORS, LOGIN_VALIDATORS, PROFILE_VALIDATORS, USER_VALIDATORS
} from 'src/app/core/validators/validations.list';
import { CanGoBackGuard } from 'src/app/core/guards/can-go-back-guard.service';
import { CONFIRM_PASSWORD_VALIDATOR } from 'src/app/core/validators/common.validator';
import { DropdownList, GetUserDataResponse } from 'src/app/core/interfaces/http-response.interface';
import { ToastrService } from 'src/app/core/services/toastr.service';
import { LoaderService } from 'src/app/core/services/loader.service';

@Component({
    templateUrl: './add.user.component.html',
    standalone: false
})
export class AddUserComponent implements AfterViewInit, OnInit, OnDestroy {
  private passwordNotMatchError = 'err.passwordNotSame';
  private subscription: Subscription[] = [];
  form: UntypedFormGroup;
  clientOptions: DropdownList[] = [];
  projectOptions: DropdownList[] = [];
  branchOptions: DropdownList[] = [];
  wdCodeOptions: DropdownList[] = [];
  circleOptions: DropdownList[] = [];
  sectionOptions: DropdownList[] = [];
  teamOptions: DropdownList[] = [];
  teamTypeOptions: DropdownList[] = [];
  landingPageOptions: DropdownList[] = [];
  groupOptions: DropdownList[] = [];
  loginOptions: DropdownList[] = [];
  passwordTooltip = CONSTANTS.STRONG_PASSWORD_FUNC ? 'tooltip.strongPasswordBody' : 'tooltip.password';
  errorMessages = {
    branch: COMMON_VALIDATORS.messages.dropdown('Branch'),
    client: COMMON_VALIDATORS.messages.dropdown('Client Name'),
    confirmPassword: USER_VALIDATORS.messages.confirmPassword,
    email: PROFILE_VALIDATORS.messages.email,
    fullname: COMMON_VALIDATORS.messages.name('Full Name'),
    group: COMMON_VALIDATORS.messages.dropdown('Group Name'),
    landingPage: COMMON_VALIDATORS.messages.dropdown('Landing Page'),
    mobile: COMMON_VALIDATORS.messages.mobile('Mobile'),
    password: LOGIN_VALIDATORS.messages.password,
    project: COMMON_VALIDATORS.messages.dropdown('Project Name'),
    username: LOGIN_VALIDATORS.messages.username(),
    wdCode: COMMON_VALIDATORS.messages.requiredOnly('WD Code'),
    circle: COMMON_VALIDATORS.messages.requiredOnly('Circle'),
    section: COMMON_VALIDATORS.messages.requiredOnly('Section'),
    team: COMMON_VALIDATORS.messages.requiredOnly('Team'),
    teamType: COMMON_VALIDATORS.messages.requiredOnly('Team Type'),
  };
  controlVisibility = {
    hideBranch: true,
    hideClient: true,
    hideProject: true,
    hideWdCode: true,
    hideCircle: true,
    hideSection: true,
    hideTeam: true,
    hideTeamType: true,
  };
  validators = {
    branch: COMMON_VALIDATORS.validators.dropdown,
    client: COMMON_VALIDATORS.validators.dropdown,
    project: COMMON_VALIDATORS.validators.dropdown,
    wdCode: COMMON_VALIDATORS.validators.requiredOnly,
    circle: COMMON_VALIDATORS.validators.requiredOnly,
    section: COMMON_VALIDATORS.validators.requiredOnly,
    team: COMMON_VALIDATORS.validators.requiredOnly,
    teamType: COMMON_VALIDATORS.validators.requiredOnly,
  };
  showSpinner = false;

  constructor(private fb: UntypedFormBuilder, private formService: FormService,
    private canGoBackGuard: CanGoBackGuard, private translate: TranslateService,
    private toastr: ToastrService, private loaderService: LoaderService) { }

  ngOnInit() {
    this.subscription.push(
      this.translate.get(this.passwordNotMatchError)
        .subscribe(translatedMsg => {
          this.passwordNotMatchError = translatedMsg;
        })
    );

    this.form = this.fb.group({
      branch: ['', COMMON_VALIDATORS.validators.dropdown],
      client: ['', COMMON_VALIDATORS.validators.dropdown],
      confirmPassword: ['', LOGIN_VALIDATORS.validators.password],
      email: ['', PROFILE_VALIDATORS.validators.email],
      fullname: ['', COMMON_VALIDATORS.validators.name()],
      group: [null, COMMON_VALIDATORS.validators.dropdown],
      landing: [null, COMMON_VALIDATORS.validators.dropdown],
      mobile: ['', COMMON_VALIDATORS.validators.mobile()],
      password: ['', LOGIN_VALIDATORS.validators.password],
      project: ['', COMMON_VALIDATORS.validators.dropdown],
      type: ['1', COMMON_VALIDATORS.validators.requiredOnly],
      username: ['', LOGIN_VALIDATORS.validators.username()],
      wdCode: ['', COMMON_VALIDATORS.validators.requiredOnly],
      circle: ['', COMMON_VALIDATORS.validators.requiredOnly],
      section: ['', COMMON_VALIDATORS.validators.requiredOnly],
      team: ['', COMMON_VALIDATORS.validators.requiredOnly],
      teamType: ['', COMMON_VALIDATORS.validators.requiredOnly],
    }, {
      validator: [CONFIRM_PASSWORD_VALIDATOR({ newPass: 'password', confPass: 'confirmPassword' })]
    });

    this.loaderService.startLoader();
    this.subscription.push(
      this.formService.getData<GetUserDataResponse>(environment.getUserDataUrl)
        .pipe(
          finalize(() => this.loaderService.stopLoader())
        )
        .subscribe(resp => {
          if (resp && resp.status === REQUEST_STATUS.SUCCESS) {
            this.clientOptions = resp.data.clientList;
            this.projectOptions = resp.data.projectList;
            this.branchOptions = resp.data.branchList;
            this.circleOptions = resp.data.circleList;
            this.sectionOptions = resp.data.sectionList;
            this.teamOptions = resp.data.teamList;
            this.teamTypeOptions = resp.data.teamTypeList;
            this.wdCodeOptions = resp.data.wdCodeList;
            this.landingPageOptions = resp.data.landingPageList;
            this.groupOptions = resp.data.groupList;
            this.loginOptions = resp.data.loginTypeList;
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
      this.form.valueChanges
        .subscribe(() => this.canGoBackGuard.markAsDirty())
    );
  }

  onTypeChange() {
    switch (this.form.get('type').value) {
      // Admin
      case '1':
        this.controlVisibility = {
          hideBranch: true,
          hideClient: true,
          hideProject: true,
          hideWdCode: true,
          hideCircle: true,
          hideSection: true,
          hideTeam: true,
          hideTeamType: true,
        };
        break;
      // Client
      case '2':
        this.controlVisibility = {
          hideBranch: true,
          hideClient: false,
          hideProject: true,
          hideWdCode: true,
          hideCircle: true,
          hideSection: true,
          hideTeam: true,
          hideTeamType: true,
        };
        break;
      // Project
      case '3':
        this.controlVisibility = {
          hideBranch: true,
          hideClient: true,
          hideProject: false,
          hideWdCode: true,
          hideCircle: true,
          hideSection: true,
          hideTeam: true,
          hideTeamType: true,
        };
        break;
      // Branch
      case '4':
        this.controlVisibility = {
          hideBranch: false,
          hideClient: true,
          hideProject: true,
          hideWdCode: true,
          hideCircle: true,
          hideSection: true,
          hideTeam: true,
          hideTeamType: true,
        };
        break;
      // WD Code
      case '5':
        this.controlVisibility = {
          hideBranch: true,
          hideClient: true,
          hideProject: true,
          hideWdCode: false,
          hideCircle: true,
          hideSection: true,
          hideTeam: true,
          hideTeamType: true,
        };
        break;
      // Circle
      case '6':
        this.controlVisibility = {
          hideBranch: true,
          hideClient: true,
          hideProject: true,
          hideWdCode: true,
          hideCircle: false,
          hideSection: true,
          hideTeam: true,
          hideTeamType: true,
        };
        break;
      // Section
      case '7':
        this.controlVisibility = {
          hideBranch: true,
          hideClient: true,
          hideProject: true,
          hideWdCode: true,
          hideCircle: true,
          hideSection: false,
          hideTeam: true,
          hideTeamType: true,
        };
        break;
      // Section
      case '8':
        this.controlVisibility = {
          hideBranch: true,
          hideClient: true,
          hideProject: true,
          hideWdCode: true,
          hideCircle: true,
          hideSection: true,
          hideTeam: false,
          hideTeamType: true,
        };
        break;
      // Section
      case '9':
        this.controlVisibility = {
          hideBranch: true,
          hideClient: true,
          hideProject: true,
          hideWdCode: true,
          hideCircle: true,
          hideSection: true,
          hideTeam: true,
          hideTeamType: false,
        };
        break;
    }
  }

  addUser() {
    if (this.form.valid && !this.showSpinner) {
      this.showSpinner = true;
      this.loaderService.startLoader();

      this.subscription.push(
        this.formService.addData<string>(this.form, null, environment.addUserUrl)
          .pipe(
            finalize(() => {
              this.showSpinner = false;
              this.loaderService.stopLoader();
            })
          )
          .subscribe(resp => {
            if (resp && resp.status === REQUEST_STATUS.SUCCESS) {
              this.canGoBackGuard.markAsPristine();
            }
          })
      );
    }
  }

  onPasswordBlur() {
    if (this.form.hasError(CUSTOM_VALIDATOR_KEYS.PASSWOR_NOT_MATCH)) {
      this.toastr.toastr({ type: 'error', msg: this.passwordNotMatchError });
    }
  }
}
