import { Injectable } from '@angular/core';
import { Subject } from 'rxjs';

@Injectable()
export class SpinnerService {
  private _spinner = new Subject<boolean>();
  private currentState = false;

  get spinner() {
    return this._spinner.asObservable();
  }

  startSpinner() {
    if (!this.currentState) {
      this.currentState = true;
      this._spinner.next(true);
    }
  }

  stopSpinner() {
    if (this.currentState) {
      this.currentState = false;
      this._spinner.next(false);
    }
  }
}
