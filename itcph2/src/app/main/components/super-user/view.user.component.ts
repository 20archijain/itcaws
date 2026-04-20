import { Component, OnDestroy, OnInit } from '@angular/core';
import { UntypedFormBuilder, UntypedFormGroup } from '@angular/forms';
import { Subscription } from 'rxjs';
import { finalize } from 'rxjs/operators';

import { environment } from 'src/environments/environment';
import { FormService } from 'src/app/core/services/form.service';
import { CONTROL_CONFIG, REQUEST_STATUS } from 'src/app/app.constants';
import {
  COMMON_VALIDATORS, LOGIN_VALIDATORS, PROFILE_VALIDATORS, USER_VALIDATORS
} from 'src/app/core/validators/validations.list';
import { EditConfig } from 'src/app/core/interfaces/helpers.interface';
import { DropdownList, GetUserDataResponse } from 'src/app/core/interfaces/http-response.interface';
import { LoaderService } from 'src/app/core/services/loader.service';

@Component({
    templateUrl: './view.user.component.html',
    standalone: false
})
export class ViewUserComponent implements OnDestroy, OnInit {
  private subscription: Subscription[] = [];
  header: string[] = [];
  body: string[] = [];
  editConfig: EditConfig[] = [];
  sortOptions: DropdownList[] = [];
  groupOptions: DropdownList[] = [];
  loginOptions: DropdownList[] = [];
  form: UntypedFormGroup;
  url = environment.viewUsersUrl;
  unlockCondition: [string, boolean];

  constructor(private formService: FormService, private fb: UntypedFormBuilder, private loaderService: LoaderService) { }

  ngOnInit() {
    this.form = this.fb.group({
      group: [],
      name: [''],
      userType: [],
      username: [''],
    });

    this.getInitialData();
  }

  ngOnDestroy() {
    this.subscription.forEach(sub => sub.unsubscribe());
  }

  getInitialData() {
    this.loaderService.startLoader();
    this.subscription.push(
      this.formService.getData<GetUserDataResponse>(environment.getUserDataUrl, { fromListing: true })
        .pipe(
          finalize(() => this.loaderService.stopLoader())
        )
        .subscribe(resp => {
          if (resp && resp.status === REQUEST_STATUS.SUCCESS) {
            this.sortOptions = resp.data.sortOptions || [];
            this.groupOptions = resp.data.groupList;
            this.loginOptions = resp.data.loginTypeList;
            this.header = resp.data.viewHeader;
            this.body = resp.data.viewBody;
            this.unlockCondition = resp.data.unlockCondition;

            this.editConfig = [
              {
                controlName: 'id', label: '', type: CONTROL_CONFIG.REC_ID,
              },
              {
                controlName: 'name', errorMessages: COMMON_VALIDATORS.messages.name('Full Name'), label: 'app.user.user.add.fullname',
                required: true, type: CONTROL_CONFIG.INPUT_BOX, validators: COMMON_VALIDATORS.validators.name(),
              },
              {
                controlName: 'email', errorMessages: PROFILE_VALIDATORS.messages.email, label: 'app.profile.form.email',
                subtype: 'email', type: CONTROL_CONFIG.INPUT_BOX, validators: PROFILE_VALIDATORS.validators.email,
              },
              {
                controlName: 'mobile', errorMessages: COMMON_VALIDATORS.messages.mobile('Mobile'), label: 'app.user.user.add.mobile',
                type: CONTROL_CONFIG.INPUT_BOX, validators: COMMON_VALIDATORS.validators.mobile(),
              },
              {
                controlName: 'landingPageId', errorMessages: COMMON_VALIDATORS.messages.dropdown('Landing Page'),
                label: 'app.user.user.add.landingPage', options: resp.data.landingPageList,
                required: true, type: CONTROL_CONFIG.SELECT_BOX, validators: COMMON_VALIDATORS.validators.dropdown,
              },
              {
                controlName: 'groupId', errorMessages: COMMON_VALIDATORS.messages.dropdown('Group Name'),
                label: 'app.user.group.form.groupName', options: this.groupOptions,
                required: true, type: CONTROL_CONFIG.SELECT_BOX, validators: COMMON_VALIDATORS.validators.dropdown,
              },
              {
                controlName: 'username', errorMessages: LOGIN_VALIDATORS.messages.username(), label: 'auth.login.form.username',
                required: true, type: CONTROL_CONFIG.INPUT_BOX, validators: LOGIN_VALIDATORS.validators.username(),
              },
              {
                controlName: 'password', errorMessages: LOGIN_VALIDATORS.messages.password, label: 'auth.login.form.password',
                type: CONTROL_CONFIG.INPUT_BOX, validators: LOGIN_VALIDATORS.validators.passwordOptional,
              },
              {
                controlName: 'confirmPassword', errorMessages: USER_VALIDATORS.messages.confirmPassword,
                label: 'app.user.user.add.confirmPassword',
                type: CONTROL_CONFIG.INPUT_BOX, validators: LOGIN_VALIDATORS.validators.passwordOptional,
              },
              {
                controlName: 'type', label: 'app.user.user.add.userType', onChange: this.onUserTypeChange.bind(this),
                options: this.loginOptions, type: CONTROL_CONFIG.RADIO_BOX,
              },
              {
                controlName: 'client', errorMessages: COMMON_VALIDATORS.messages.dropdown('Client Name'), hide: true,
                label: 'app.client.add.name', multiple: true, options: resp.data.clientList,
                required: true, type: CONTROL_CONFIG.SELECT_BOX, validators: COMMON_VALIDATORS.validators.dropdown,
              },
              {
                controlName: 'project', errorMessages: COMMON_VALIDATORS.messages.dropdown('Project Name'), hide: true,
                label: 'app.project.add.name', multiple: true, options: resp.data.projectList,
                required: true, type: CONTROL_CONFIG.SELECT_BOX, validators: COMMON_VALIDATORS.validators.dropdown,
              },
              {
                controlName: 'branch', errorMessages: COMMON_VALIDATORS.messages.dropdown('Branch'), hide: true,
                label: 'app.team.add.branch', multiple: true, options: resp.data.branchList,
                required: true, type: CONTROL_CONFIG.SELECT_BOX, validators: COMMON_VALIDATORS.validators.dropdown,
              },
              {
                controlName: 'wdCode', errorMessages: COMMON_VALIDATORS.messages.requiredOnly('WD Code'), hide: true,
                label: 'app.team.view.wdCode', multiple: true, options: resp.data.wdCodeList,
                required: true, type: CONTROL_CONFIG.SELECT_BOX, validators: COMMON_VALIDATORS.validators.requiredOnly,
              },
              {
                controlName: 'circle', errorMessages: COMMON_VALIDATORS.messages.requiredOnly('Circle'), hide: true,
                label: 'app.team.view.circle', multiple: true, options: resp.data.circleList,
                required: true, type: CONTROL_CONFIG.SELECT_BOX, validators: COMMON_VALIDATORS.validators.requiredOnly,
              },
              {
                controlName: 'section', errorMessages: COMMON_VALIDATORS.messages.requiredOnly('Section'), hide: true,
                label: 'app.team.view.section', multiple: true, options: resp.data.sectionList,
                required: true, type: CONTROL_CONFIG.SELECT_BOX, validators: COMMON_VALIDATORS.validators.requiredOnly,
              },
              {
                controlName: 'team', errorMessages: COMMON_VALIDATORS.messages.requiredOnly('Team'), hide: true,
                label: 'app.team.view.team', multiple: true, options: resp.data.teamList,
                required: true, type: CONTROL_CONFIG.SELECT_BOX, validators: COMMON_VALIDATORS.validators.requiredOnly,
              },
              {
                controlName: 'teamType', errorMessages: COMMON_VALIDATORS.messages.requiredOnly('Team Type'), hide: true,
                label: 'app.team.view.teamType', multiple: true, options: resp.data.teamTypeList,
                required: true, type: CONTROL_CONFIG.SELECT_BOX, validators: COMMON_VALIDATORS.validators.requiredOnly,
              },
            ];
          }
        })
    );
  }

  onUserTypeChange(form: UntypedFormGroup) {
    const type = form.get('type').value;

    switch (+type) {
      case 1:
        // Admin
        this.editConfig[10].hide = true;
        this.editConfig[11].hide = true;
        this.editConfig[12].hide = true;
        this.editConfig[13].hide = true;
        this.editConfig[14].hide = true;
        this.editConfig[15].hide = true;
        this.editConfig[16].hide = true;
        this.editConfig[17].hide = true;
        break;
      case 2:
        // Client
        this.editConfig[10].hide = false;
        this.editConfig[11].hide = true;
        this.editConfig[12].hide = true;
        this.editConfig[13].hide = true;
        this.editConfig[14].hide = true;
        this.editConfig[15].hide = true;
        this.editConfig[16].hide = true;
        this.editConfig[17].hide = true;
        break;
      case 3:
        // Project
        this.editConfig[10].hide = true;
        this.editConfig[11].hide = false;
        this.editConfig[12].hide = true;
        this.editConfig[13].hide = true;
        this.editConfig[14].hide = true;
        this.editConfig[15].hide = true;
        this.editConfig[16].hide = true;
        this.editConfig[17].hide = true;
        break;
      case 4:
        // Branch
        this.editConfig[10].hide = true;
        this.editConfig[11].hide = true;
        this.editConfig[12].hide = false;
        this.editConfig[13].hide = true;
        this.editConfig[14].hide = true;
        this.editConfig[15].hide = true;
        this.editConfig[16].hide = true;
        this.editConfig[17].hide = true;
        break;
      case 5:
        // WD Code
        this.editConfig[10].hide = true;
        this.editConfig[11].hide = true;
        this.editConfig[12].hide = true;
        this.editConfig[13].hide = false;
        this.editConfig[14].hide = true;
        this.editConfig[15].hide = true;
        this.editConfig[16].hide = true;
        this.editConfig[17].hide = true;
        break;
      case 6:
        // Circle
        this.editConfig[10].hide = true;
        this.editConfig[11].hide = true;
        this.editConfig[12].hide = true;
        this.editConfig[13].hide = true;
        this.editConfig[14].hide = false;
        this.editConfig[15].hide = true;
        this.editConfig[16].hide = true;
        this.editConfig[17].hide = true;
        break;
      case 7:
        // Section
        this.editConfig[10].hide = true;
        this.editConfig[11].hide = true;
        this.editConfig[12].hide = true;
        this.editConfig[13].hide = true;
        this.editConfig[14].hide = true;
        this.editConfig[15].hide = false;
        this.editConfig[16].hide = true;
        this.editConfig[17].hide = true;
        break;
      case 8:
        // Section
        this.editConfig[10].hide = true;
        this.editConfig[11].hide = true;
        this.editConfig[12].hide = true;
        this.editConfig[13].hide = true;
        this.editConfig[14].hide = true;
        this.editConfig[15].hide = true;
        this.editConfig[16].hide = false;
        this.editConfig[17].hide = true;
        break;
      case 9:
        // Section
        this.editConfig[10].hide = true;
        this.editConfig[11].hide = true;
        this.editConfig[12].hide = true;
        this.editConfig[13].hide = true;
        this.editConfig[14].hide = true;
        this.editConfig[15].hide = true;
        this.editConfig[16].hide = true;
        this.editConfig[17].hide = false;
        break;
    }
  }

  clearForm() {
    this.form.reset();
  }
}
