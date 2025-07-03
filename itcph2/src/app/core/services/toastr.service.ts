import { Injectable } from '@angular/core';
import { Subject } from 'rxjs';

import { ToastrConfig } from '../interfaces/toastr.interface';

@Injectable()
export class ToastrService {

  private toastrNotification = new Subject<ToastrConfig>();

  onToastr() {
    return this.toastrNotification.asObservable();
  }

  toastr(options: ToastrConfig) {
    this.toastrNotification.next(options);
  }
}
