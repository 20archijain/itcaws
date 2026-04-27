import { Component, OnDestroy, OnInit } from '@angular/core';
import { UntypedFormBuilder, UntypedFormGroup } from '@angular/forms';
import { Subscription } from 'rxjs';

import { LOGIN_VALIDATORS } from 'src/app/core/validators/validations.list';
import { AuthService } from 'src/app/core/services/auth.service';
import { RoutingService } from 'src/app/core/services/routing.service';
// import { CaptchaComponent } from 'src/app/shared/components/captcha/captcha.component';
import { REQUEST_STATUS } from 'src/app/app.constants';
import { Functions } from 'src/app/core/utils/functions.list';
import { GAService } from 'src/app/core/services/ga.service';
import { GA_ACTION_LIST } from 'src/app/core/utils/GAMapping';
import { HttpRequestResponse } from 'src/app/core/interfaces/common.interface';

@Component({
  styleUrls: ['./login.component.scss'],
  templateUrl: './login.component.html',
  standalone: false,
})
export class LoginComponent implements OnDestroy, OnInit {
  // @ViewChild(CaptchaComponent, { static: false }) private captchaComponent: CaptchaComponent;
  private subscription: Subscription[] = [];
  private gaLabels = GA_ACTION_LIST.auth.login;
  group!: UntypedFormGroup;
  isDisabled = false;
  errorMessages = {
    // captcha: LOGIN_VALIDATORS.messages.captcha,
    password: LOGIN_VALIDATORS.messages.password,
    username: LOGIN_VALIDATORS.messages.username(),
  };

  constructor(private fb: UntypedFormBuilder, private routerService: RoutingService, private authService: AuthService,
    private gaService: GAService) {
  }

  ngOnInit() {
    this.authService.isComingAfterLogin = false;
    this.group = this.fb.group({
      // captcha: ['', LOGIN_VALIDATORS.validators.captcha],
      password: ['', LOGIN_VALIDATORS.validators.password],
      username: ['', LOGIN_VALIDATORS.validators.username()],
    });
  }

  ngOnDestroy() {
    this.subscription.forEach(sub => sub.unsubscribe());
  }

  navigateToForgot() {
    this.routerService.navigate('/auth/forgot');
  }

  navigateToTwoWay() {
    this.routerService.navigate('/auth/two-way');
  }

  login() {
    if (this.group && this.group.valid) {
      this.isDisabled = true;

      this.subscription.push(
        this.authService.login(this.group.getRawValue())
          .subscribe(response => {
            // track Login success
            this.gaService.sendEvent(this.gaLabels.apiSuccess, this.gaLabels.category, this.gaLabels.label, response?.status);

            if (response && response.status !== REQUEST_STATUS.FAILED) {
              if (response.data?.enableTwoWayAuth) {
                this.authService.isComingAfterLogin = true;
                this.navigateToTwoWay();
              } else {
                const params = Functions.getHomeLocation() ?? [];
                this.routerService.navigate('app', params as never[]);
              }
            } else {
              // if (CONSTANTS.REGENERATE_CAPTCHA_IF_FAILS) {
              //   this.captchaComponent.getCaptcha();
              // }
              this.isDisabled = false;
            }
          }, (response: HttpRequestResponse) => {
            // track Login failed
            this.gaService.sendEvent(this.gaLabels.apiFailed, this.gaLabels.category, this.gaLabels.label, response.status);

            this.isDisabled = false;
            // if (CONSTANTS.REGENERATE_CAPTCHA_IF_FAILS) {
            //   this.captchaComponent.getCaptcha();
            // }
          })
      );
    }
  }
}
