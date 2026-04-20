import { Component, OnDestroy, OnInit } from '@angular/core';
import { Subscription } from 'rxjs';
import Swal from 'sweetalert2';
import { TranslateService } from '@ngx-translate/core';

import { ConfirmationModalService } from '../../services/confirmation-modal.service';

@Component({
    selector: 'app-confirmation-modal',
    template: '',
    standalone: false
})
export class ConfirmationModalComponent implements OnDestroy, OnInit {
  private subscription: Subscription[] = [];
  private labelsKeys = ['modal.confirmation.title', 'modal.confirmation.text', 'modal.confirmation.cancel'];
  private labels = [];
  private isGoBackGuardCheck = false;
  private text: string;

  constructor(private confirmationModalService: ConfirmationModalService, private translate: TranslateService) { }

  ngOnInit() {
    this.subscription.push(
      this.translate.get(this.labelsKeys)
        .subscribe(translatedMsg => {
          this.labels = [
            translatedMsg[this.labelsKeys[0]],
            translatedMsg[this.labelsKeys[1]],
            translatedMsg[this.labelsKeys[2]],
          ];
          this.text = this.labels[1];
        })
    );

    this.subscription.push(
      this.confirmationModalService.modal()
        .subscribe(resp => {
          this.isGoBackGuardCheck = resp.goBackGuard;
          if (resp.show) {
            this.text = resp && resp.data !== undefined ? this.translate.instant((resp.data as string)) : this.labels[1];
            this.showConfirmBox();
          } else {
            this.hideConfirmBox();
          }
        })
    );
  }

  ngOnDestroy() {
    this.subscription.forEach(sub => sub.unsubscribe());
  }

  showConfirmBox() {
    Swal.fire({
      allowEscapeKey: false,
      allowOutsideClick: false,
      showCancelButton: true,
      showCloseButton: true,
      text: this.text,
      title: this.labels[0],
      icon: 'warning',
    }).then((willDelete) => {
      // user presses cancel button
      if (willDelete.dismiss) {
        Swal.fire('', this.labels[2], 'success')
          .then(() => this.confirmationModalService.hide(false, this.isGoBackGuardCheck));
      } else {
        this.confirmationModalService.hide(true, this.isGoBackGuardCheck);
      }
    });
  }

  hideConfirmBox() {
    if (Swal.isVisible()) {
      Swal.close();
    }
  }
}
