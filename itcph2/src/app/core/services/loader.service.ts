import { Injectable } from '@angular/core';
import { Subject } from 'rxjs';

@Injectable()
export class LoaderService {
  private _loader = new Subject<boolean>();
  private currentState = false;

  get loader() {
    return this._loader.asObservable();
  }

  startLoader() {
    if (!this.currentState) {
      this.currentState = true;
      this._loader.next(true);
    }
  }

  stopLoader() {
    if (this.currentState) {
      this.currentState = false;
      this._loader.next(false);
    }
  }
}
