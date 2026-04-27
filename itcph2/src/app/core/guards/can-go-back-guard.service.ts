import { Injectable, OnDestroy } from '@angular/core';
import { ActivatedRouteSnapshot, RouterStateSnapshot } from '@angular/router';
import { BehaviorSubject, Subscription } from 'rxjs';

import { ConfirmationModalService } from '../services/confirmation-modal.service';
import { RoutingService } from '../services/routing.service';

@Injectable()
export class CanGoBackGuard implements OnDestroy {
  private subscription: Subscription[] = [];
  private routedUrl = '';
  private isFormDirty = false;
  private isFormDirty$ = new BehaviorSubject<boolean>(false);

  constructor(private confirmationModalService: ConfirmationModalService,
    private routingService: RoutingService) {
    this.subscription.push(
      confirmationModalService.modal()
        .subscribe(resp => {
          // on modal hidden
          if (resp.goBackGuard && !resp.show) {
            // user confirms to lost changes
            if (resp.data) {
              this.markAsPristine();
              routingService.navigate(this.routedUrl);
            }
          }
        })
    );

    this.subscription.push(
      this.isFormDirty$.asObservable()
        .subscribe(isDirty => this.isFormDirty = isDirty)
    );
  }

  canActivateChild(route: ActivatedRouteSnapshot, state: RouterStateSnapshot) {
    // if unsaved changes, show confirm modal
    if (this.isFormDirty) {
      this.confirmationModalService.show('modal.confirmation.text', true);
      this.routedUrl = state.url;

      return false;
    } else {
      return !this.isFormDirty;
    }
  }

  markAsDirty() {
    this.isFormDirty$.next(true);
  }

  markAsPristine() {
    this.isFormDirty$.next(false);
  }

  ngOnDestroy() {
    this.subscription.forEach(sub => sub.unsubscribe());
  }
}
