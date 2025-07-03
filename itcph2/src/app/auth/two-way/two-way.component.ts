import { AfterViewInit, Component, OnDestroy, OnInit } from '@angular/core';
import { UntypedFormBuilder, UntypedFormGroup } from '@angular/forms';
import { Subscription } from 'rxjs';
import { TranslateService } from '@ngx-translate/core';

import { COMMON_VALIDATORS } from 'src/app/core/validators/validations.list';
import { RoutingService } from 'src/app/core/services/routing.service';
import { AuthService } from 'src/app/core/services/auth.service';
import { REQUEST_STATUS, TWO_WAY } from 'src/app/app.constants';
import { GAService } from 'src/app/core/services/ga.service';
import { GA_ACTION_LIST } from 'src/app/core/utils/GAMapping';
import { HttpRequestResponse } from 'src/app/core/interfaces/common.interface';
import { Functions } from 'src/app/core/utils/functions.list';
import { SessionUtil } from 'src/app/core/utils/session.util';
import { LoginDataUser } from 'src/app/core/interfaces/http-response.interface';

@Component({
  templateUrl: './two-way.component.html'
})
export class TwoWayComponent implements AfterViewInit, OnDestroy, OnInit {
  private subscription: Subscription[] = [];
  private gaLabels = GA_ACTION_LIST.auth;
  group: UntypedFormGroup;
  isVerifyOtpDisabled = false;
  isResendBtnDisable = true;
  timerText: string;
  userDetails: LoginDataUser = JSON.parse(SessionUtil.getItem('user'));
  heading = 'auth.twoWay.form.heading';
  mobile = +this.userDetails?.mobile;
  mobileString = this.mobile?.toString();
  obscuredNumber = this.mobileString ? this.mobileString.replace(/(\d{2})\d+(\d{3})/, '$1*****$2') : '';
  resendOtpText = 'button.resendOTP';
  errorMessages = {
    otp: COMMON_VALIDATORS.messages.requiredOnly('OTP'),
  };

  constructor(private fb: UntypedFormBuilder, private routerService: RoutingService, private authService: AuthService,
    private gaService: GAService, private translate: TranslateService) {
  }

  ngOnInit() {
    if (!this.authService.isComingAfterLogin) {
      this.navigateToLogin();
    }

    this.subscription.push(
      this.translate.get(this.resendOtpText)
        .subscribe(translatedMsg => {
          this.resendOtpText = translatedMsg;
        })
    );

    this.group = this.fb.group({
      otp: ['', COMMON_VALIDATORS.validators.requiredOnly],
    });
  }

  ngOnDestroy() {
    this.subscription.forEach(sub => sub.unsubscribe());
  }

  ngAfterViewInit() {
    this.startCountdown();
  }

  navigateToLogin() {
    this.routerService.navigate('/auth/login');
  }

  startCountdown() {
    let totalTimeInSeconds = TWO_WAY.ENABLE_SEND_OTP_BTN_IN_SEC;
    const interval = setInterval(() => {
      this.timerText = `${Math.floor(totalTimeInSeconds / 60)}m:${totalTimeInSeconds % 60}s`;
      totalTimeInSeconds--;

      if (totalTimeInSeconds < 0) {
        if (interval) {
          clearInterval(interval);
        }
        this.timerText = this.resendOtpText;
        this.isResendBtnDisable = false;
      }
    }, 1000);
  }

  verify() {
    if (this.group && this.group.valid) {
      this.isVerifyOtpDisabled = true;
      const userDetails: LoginDataUser = JSON.parse(SessionUtil.getItem('user'));

      this.subscription.push(
        this.authService.verifyOtp({ ...this.group.getRawValue(), id: userDetails.id })
          .subscribe(response => {
            this.gaService.sendEvent(this.gaLabels.twoWayVerifyOtpBtn.apiSuccess,
              this.gaLabels.twoWayVerifyOtpBtn.category,
              this.gaLabels.twoWayVerifyOtpBtn.label, response?.status);
            this.isVerifyOtpDisabled = false;
            if (response && response.status === REQUEST_STATUS.SUCCESS) {
              const params = Functions.getHomeLocation();
              this.routerService.navigate('app', params);
            }
          }, (response: HttpRequestResponse) => {
            this.gaService.sendEvent(this.gaLabels.twoWayVerifyOtpBtn.apiFailed, this.gaLabels.twoWayVerifyOtpBtn.category,
              this.gaLabels.twoWayVerifyOtpBtn.label, response.status);
            this.isVerifyOtpDisabled = false;
          })
      );
    }
  }

  resend() {
    this.isResendBtnDisable = true;

    this.subscription.push(
      this.authService.sendOtp()
        .subscribe(response => {
          if (response && response.status !== REQUEST_STATUS.FAILED) {
            this.startCountdown();
          } else {
            this.isResendBtnDisable = false;
          }
        })
    );
  }
}
