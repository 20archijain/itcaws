import { Injectable } from '@angular/core';
import { Subject } from 'rxjs';

import { ConfirmationModalOutput } from '../interfaces/helpers.interface';

@Injectable()
export class ConfirmationModalService {
  private confirmationModal = new Subject<ConfirmationModalOutput>();
  data: string;

  modal() {
    return this.confirmationModal.asObservable();
  }

  show(data?: string, goBackGuard?: boolean) {
    this.data = data;
    this.confirmationModal.next({ show: true, data, goBackGuard });
  }

  hide(data?: boolean, goBackGuard?: boolean) {
    this.confirmationModal.next({ show: false, data, goBackGuard });
  }
}
