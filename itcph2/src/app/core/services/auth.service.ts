import { Observable } from 'rxjs';
import { Injectable } from '@angular/core';
import { tap } from 'rxjs/operators';

import { REQUEST_STATUS, STATIC_MODULES } from 'src/app/app.constants';
import { environment } from 'src/environments/environment';
import { FormService } from './form.service';
import { SessionUtil } from '../utils/session.util';
import { TimeoutUtil } from '../utils/timeout.util';
import { HttpRequestResponse, LoginRequestPayload, TwoWayAuthRequestPayload } from '../interfaces/common.interface';
import { CanGoBackGuard } from '../guards/can-go-back-guard.service';
import { LoginDataResponse, LoginDataUser } from '../interfaces/http-response.interface';

@Injectable()
export class AuthService {
  isComingAfterLogin = false;

  constructor(private formService: FormService, private canGoBackGuard: CanGoBackGuard) { }

  login(params: LoginRequestPayload): Observable<HttpRequestResponse<LoginDataResponse>> {
    return this.formService
      .customActionCall<LoginDataResponse>(STATIC_MODULES.login.action, params, null, environment.loginUrl,
        {
          moduleName: STATIC_MODULES.login.module,
          staticModule: true
        })
      .pipe(
        tap((response: HttpRequestResponse<LoginDataResponse>) => {
          if (response && response.status !== REQUEST_STATUS.FAILED) {

            // store each response object
            for (const key in response.data) {
              if (typeof response.data[key] === 'object') {
                SessionUtil.setItem(key, JSON.stringify(response.data[key]));
              } else {
                SessionUtil.setItem(key, response.data[key]);
              }
            }

            // start timeout if 2 way auth is not enabled
            if (!response.data.enableTwoWayAuth) {
              TimeoutUtil.startTimeout();
            }
          }
        })
      );
  }

  forgot(params: string): Observable<HttpRequestResponse<string>> {
    return this.formService
      .customActionCall<string>(STATIC_MODULES.forgot.action, params, null, environment.forgotUrl,
        {
          moduleName: STATIC_MODULES.forgot.module,
          staticModule: true
        });
  }

  verifyOtp(params: TwoWayAuthRequestPayload): Observable<HttpRequestResponse<LoginDataResponse>> {
    return this.formService
      .customActionCall<LoginDataResponse>(STATIC_MODULES.twoWay.verifyOtp.action, params, null, environment.loginUrl,
        {
          moduleName: STATIC_MODULES.twoWay.module,
          staticModule: true
        })
      .pipe(
        tap((response: HttpRequestResponse<LoginDataResponse>) => {
          if (response && response.status !== REQUEST_STATUS.FAILED) {

            // store each response object
            for (const key in response.data) {
              if (typeof response.data[key] === 'object') {
                SessionUtil.setItem(key, JSON.stringify(response.data[key]));
              } else {
                SessionUtil.setItem(key, response.data[key]);
              }
            }

            // start timeout
            TimeoutUtil.startTimeout();
          }
        })
      );
  }

  sendOtp() {
    const userDetails: LoginDataUser = JSON.parse(SessionUtil.getItem('user'));

    return this.formService
      .customActionCall<LoginDataResponse>(STATIC_MODULES.twoWay.resendOtp.action, { id: userDetails.id },
        null, environment.loginUrl,
        {
          moduleName: STATIC_MODULES.twoWay.module,
          staticModule: true
        });
  }

  logout(): Observable<HttpRequestResponse<string>> {
    return this.formService
      .customActionCall<string>(STATIC_MODULES.logout.action, { token: SessionUtil.getItem('token') }, null,
        environment.logoutUrl,
        {
          moduleName: STATIC_MODULES.logout.module,
          staticModule: true
        })
      .pipe(
        tap((response: HttpRequestResponse<string>) => {
          if (response && response.status === REQUEST_STATUS.SUCCESS) {
            SessionUtil.clear();

            // mark form as not dirty
            this.canGoBackGuard.markAsPristine();

            // stop timeout
            TimeoutUtil.stopTimeout();
          }
        })
      );
  }
}
