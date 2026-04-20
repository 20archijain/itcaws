import { Component, Input } from '@angular/core';

import { REQUEST_STATUS } from 'src/app/app.constants';
import { HttpRequestResponse } from 'src/app/core/interfaces/common.interface';

@Component({
    selector: 'app-alert',
    templateUrl: './alert.component.html',
    standalone: false
})
export class AlertComponent {
  @Input() response: HttpRequestResponse = null;
  @Input() allowDismiss = true;
  requestEnum = REQUEST_STATUS;

  dismissAlert(element) {
    if (element && element.parentElement) {
      element.parentElement.removeChild(element);
    }
  }
}
