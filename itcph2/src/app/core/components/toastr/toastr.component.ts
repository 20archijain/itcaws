import { Component, OnDestroy, OnInit } from '@angular/core';
import { Subscription } from 'rxjs';
import { ToastrService as ToastyService } from 'ngx-toastr';

import { TOASTR_DEFAULT_CONFIG } from 'src/app/app.constants';
import { ToastrService } from '../../services/toastr.service';
import { ToastrConfig } from '../../interfaces/toastr.interface';

@Component({
    selector: 'app-toastr',
    template: '',
    standalone: false
})
export class ToastrComponent implements OnDestroy, OnInit {
  private subscription: Subscription[] = [];
  private type = TOASTR_DEFAULT_CONFIG.TYPE;
  position = TOASTR_DEFAULT_CONFIG.POSITION;

  constructor(private toastrService: ToastrService, private toastyService: ToastyService) { }

  ngOnInit() {
    this.subscription.push(
      this.toastrService.onToastr()
        .subscribe(options => this.showToast(options))
    );
  }

  ngOnDestroy() {
    this.subscription.forEach(sub => sub.unsubscribe());
  }

  showToast(options: ToastrConfig) {
    if (options.closeOther) {
      this.toastyService.clear();
    }

    const msg = options.msg || '';
    const title = options.title || '';
    const toastOptions = {
      closeButton: options.showClose || false,
      easeTime: '100',
      easing: 'linear',
      positionClass: options.position || this.position,
      progressBar: true,
      timeOut: options.timeout || TOASTR_DEFAULT_CONFIG.TIMEOUT,
    };

    switch (options.type || this.type) {
      case 'info': this.toastyService.info(msg, title, toastOptions); break;
      case 'success': this.toastyService.success(msg, title, toastOptions); break;
      case 'error': this.toastyService.error(msg, title, toastOptions); break;
      case 'warning': this.toastyService.warning(msg, title, toastOptions); break;
    }
  }
}
