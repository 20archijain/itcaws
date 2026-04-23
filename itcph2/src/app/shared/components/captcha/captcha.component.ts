import { Component, Input, OnDestroy, OnInit } from '@angular/core';
import { UntypedFormGroup } from '@angular/forms';
import { Subscription } from 'rxjs';

import { environment } from 'src/environments/environment';
import { FormService } from 'src/app/core/services/form.service';
import { REQUEST_STATUS, STATIC_MODULES } from 'src/app/app.constants';
import { FormControlErrorMessage } from 'src/app/core/interfaces/common.interface';

@Component({
  selector: 'app-captcha',
  styleUrls: ['./captcha.component.scss'],
  templateUrl: './captcha.component.html',
  standalone: false,
})
export class CaptchaComponent implements OnDestroy, OnInit {
  private subscription: Subscription[] = [];
  @Input() group!: UntypedFormGroup;
  @Input() controlName!: string;
  @Input() errorMessages: FormControlErrorMessage[] = [];
  captchaImage = '';

  constructor(private formService: FormService) {
  }

  ngOnInit() {
    this.getCaptcha();
  }

  ngOnDestroy() {
    this.subscription.forEach(sub => sub.unsubscribe());
  }

  getCaptcha() {
    this.captchaImage = '';

    this.subscription.push(
      this.formService.customActionCall<string>(STATIC_MODULES.custom.getCaptcha, ['getCaptcha'], null,
        environment.getCaptchaUrl, { moduleName: STATIC_MODULES.custom.getCaptcha, staticModule: true })
        .subscribe(response => {
          if (response && response.status === REQUEST_STATUS.SUCCESS) {
            this.captchaImage = response.data + '?te=' + new Date().getTime();
          }
        })
    );
  }
}
