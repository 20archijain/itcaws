import { Component, OnDestroy, OnInit } from '@angular/core';
import { UntypedFormBuilder, UntypedFormGroup, Validators } from '@angular/forms';
import { Subscription } from 'rxjs';
import { finalize } from 'rxjs/operators';

import { FORGOT_VALIDATORS } from 'src/app/core/validators/validations.list';
import { RoutingService } from 'src/app/core/services/routing.service';
import { AuthService } from 'src/app/core/services/auth.service';
import { REQUEST_STATUS } from 'src/app/app.constants';
import { GAService } from 'src/app/core/services/ga.service';
import { GA_ACTION_LIST } from 'src/app/core/utils/GAMapping';
import { HttpRequestResponse } from 'src/app/core/interfaces/common.interface';

@Component({
    styleUrls: ['./forgot.component.scss'],
    templateUrl: './forgot.component.html',
    standalone: false
})
export class ForgotComponent implements OnDestroy, OnInit {
  private subscription: Subscription[] = [];
  private gaLabels = GA_ACTION_LIST.auth.forgot;
  group: UntypedFormGroup;
  isDisabled = false;
  errorMessages = {
    email: FORGOT_VALIDATORS.messages.email,
  };

  constructor(private fb: UntypedFormBuilder, private routerService: RoutingService, private authService: AuthService,
    private gaService: GAService) {
  }

  ngOnInit() {
    this.group = this.fb.group({
      email: ['', [Validators.required, ...FORGOT_VALIDATORS.validators.email]],
    });
  }

  ngOnDestroy() {
    this.subscription.forEach(sub => sub.unsubscribe());
  }

  navigateToLogin() {
    this.routerService.navigate('/auth/login');
  }

  forgot() {
    if (this.group && this.group.valid) {
      this.isDisabled = true;

      this.subscription.push(
        this.authService.forgot(this.group.getRawValue())
          .pipe(
            finalize(() => this.isDisabled = false),
          )
          .subscribe((response: HttpRequestResponse<string>) => {
            // track Forgot success
            this.gaService.sendEvent(this.gaLabels.apiSuccess, this.gaLabels.category, this.gaLabels.label, response?.status);
            if (response && response.status === REQUEST_STATUS.SUCCESS) {
              this.reset();
            }
          }, (response: HttpRequestResponse) => {
            // track Forgot failed
            this.gaService.sendEvent(this.gaLabels.apiFailed, this.gaLabels.category, this.gaLabels.label, response.status);
          })
      );
    }
  }

  reset() {
    this.group.get('email').reset();
  }
}
