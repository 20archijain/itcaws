import { Subject } from 'rxjs';

import { TimeoutModalConfig } from '../interfaces/common.interface';

export class TimeoutUtil {
  private static timeout = new Subject<boolean>();
  private static timeoutModal = new Subject<TimeoutModalConfig>();

  static getTimeout() {
    return this.timeout.asObservable();
  }

  static getTimeoutModal() {
    return this.timeoutModal.asObservable();
  }

  static startTimeout() {
    this.timeout.next(true);
  }

  static stopTimeout() {
    this.timeout.next(false);
  }

  static timeoutModalBtnClick(action: string) {
    if (action === 'continue') {
      this.startTimeout();
    } else {
      this.stopTimeout();
    }
  }

  static toggleTimeoutModal(config: TimeoutModalConfig) {
    this.timeoutModal.next(config);
  }
}
