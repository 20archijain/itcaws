import { Component, OnDestroy, OnInit } from '@angular/core';
import { UntypedFormBuilder, UntypedFormGroup } from '@angular/forms';
import { Subscription } from 'rxjs';
import { TranslateService } from '@ngx-translate/core';
import { finalize } from 'rxjs/operators';

import { CanGoBackGuard } from 'src/app/core/guards/can-go-back-guard.service';
import { FormService } from 'src/app/core/services/form.service';
import { COMMON_VALIDATORS, LOGIN_VALIDATORS, PROFILE_VALIDATORS } from 'src/app/core/validators/validations.list';
import { SessionUtil } from 'src/app/core/utils/session.util';
import { CONFIRM_PASSWORD_VALIDATOR } from 'src/app/core/validators/common.validator';
import { environment } from 'src/environments/environment';
import { CONSTANTS, CUSTOM_VALIDATOR_KEYS, REQUEST_STATUS, STATIC_MODULES } from 'src/app/app.constants';
import { ToastrService } from 'src/app/core/services/toastr.service';
import { LoaderService } from 'src/app/core/services/loader.service';

@Component({
  templateUrl: './my-profile.component.html'
})
export class MyProfileComponent implements OnDestroy, OnInit {
  private subscription: Subscription[] = [];
  private passwordNotMatchError = 'err.passwordNotMatch';
  personalForm: UntypedFormGroup;
  passwordForm: UntypedFormGroup;
  errorMessages = {
    confirmNewPassword: PROFILE_VALIDATORS.messages.confirmNewPassword,
    currentPassword: PROFILE_VALIDATORS.messages.currentPassword,
    email: PROFILE_VALIDATORS.messages.email,
    name: PROFILE_VALIDATORS.messages.name,
    newPassword: PROFILE_VALIDATORS.messages.newPassword
  };
  isDisabled = false;
  passwordTooltip = CONSTANTS.STRONG_PASSWORD_FUNC ? 'tooltip.strongPasswordBody' : 'tooltip.password';
  active = 1;

  constructor(private fb: UntypedFormBuilder, private canGoBackGuard: CanGoBackGuard, private formService: FormService,
    private translate: TranslateService, private toastr: ToastrService, private loaderService: LoaderService) { }

  ngOnInit() {
    this.subscription.push(
      this.translate.get(this.passwordNotMatchError)
        .subscribe(translatedMsg => {
          this.passwordNotMatchError = translatedMsg;
        })
    );

    const userDetails = JSON.parse(SessionUtil.getItem('user'));
    const name = userDetails && userDetails.name ? userDetails.name : '';
    const email = userDetails && userDetails.email ? userDetails.email : '';

    this.personalForm = this.fb.group({
      email: [email, PROFILE_VALIDATORS.validators.email],
      name: [name, COMMON_VALIDATORS.validators.name()],
    });

    this.passwordForm = this.fb.group({
      confirmNewPassword: ['', LOGIN_VALIDATORS.validators.password],
      currentPassword: ['', LOGIN_VALIDATORS.validators.password],
      newPassword: ['', LOGIN_VALIDATORS.validators.password],
    }, {
      validator: [CONFIRM_PASSWORD_VALIDATOR({ newPass: 'newPassword', confPass: 'confirmNewPassword' })]
    });

    this.canGoBackGuard.markAsPristine();

    this.subscription.push(
      this.personalForm.valueChanges
        .subscribe(() => this.canGoBackGuard.markAsDirty())
    );

    this.subscription.push(
      this.passwordForm.valueChanges
        .subscribe(() => this.canGoBackGuard.markAsDirty())
    );
  }

  ngOnDestroy() {
    this.subscription.forEach(sub => sub.unsubscribe());
  }

  editProfile() {
    if (!this.isDisabled && this.personalForm.valid) {
      this.isDisabled = true;
      this.loaderService.startLoader();

      this.subscription.push(
        this.formService.editData<string>(this.personalForm, null, environment.editProfileUrl)
          .pipe(
            finalize(() => {
              this.isDisabled = false;
              this.loaderService.stopLoader();
            }),
          )
          .subscribe(resp => {
            if (resp && resp.status === REQUEST_STATUS.SUCCESS) {
              this.canGoBackGuard.markAsPristine();
              const userDetails = JSON.stringify(this.personalForm.getRawValue());
              SessionUtil.setItem('user', userDetails);
            }
          })
      );
    }
  }

  changePassword() {
    if (!this.isDisabled && this.passwordForm.valid) {
      this.isDisabled = true;
      this.loaderService.startLoader();

      this.subscription.push(
        this.formService.customActionCall<string>(STATIC_MODULES.custom.changePassword, this.passwordForm, null, environment.changePasswordUrl)
          .pipe(
            finalize(() => {
              this.isDisabled = false;
              this.loaderService.stopLoader();
            }),
          )
          .subscribe(resp => {
            if (resp && resp.status === REQUEST_STATUS.SUCCESS) {
              this.clearForm();
              this.canGoBackGuard.markAsPristine();
            }
          })
      );
    } else {
      if (this.passwordForm.hasError(CUSTOM_VALIDATOR_KEYS.PASSWOR_NOT_MATCH)) {
        this.toastr.toastr({ type: 'error', msg: this.passwordNotMatchError });
      }
    }
  }

  clearForm() {
    this.passwordForm.reset();
  }
}
