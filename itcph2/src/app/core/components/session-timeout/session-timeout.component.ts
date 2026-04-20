import { Component, ElementRef, OnDestroy, OnInit, Renderer2, ViewChild } from '@angular/core';
import { Observable, Subscription, timer } from 'rxjs';

import { ModalComponent } from 'src/app/shared/components/modal/modal.component';
import { REQUEST_STATUS } from 'src/app/app.constants';
import { RoutingService } from '../../services/routing.service';
import { TimeoutUtil } from '../../utils/timeout.util';
import { AuthService } from '../../services/auth.service';
import { GA_ACTION_LIST } from '../../utils/GAMapping';
import { GAService } from '../../services/ga.service';
import { HttpRequestResponse, TimeoutModalConfig } from '../../interfaces/common.interface';

@Component({
    selector: 'app-session-timeout',
    templateUrl: './session-timeout.component.html',
    standalone: false
})
export class SessionTimeoutComponent implements OnDestroy, OnInit {
  private gaLabels = GA_ACTION_LIST.auth.logout;
  private subscription: Subscription[] = [];
  private countdownStart = false;
  private pollScreenObservable: Observable<number>;
  private pollScreenSubscription: Subscription;
  private action = '';
  @ViewChild('timeoutModal', { static: true }) timeoutModal: ModalComponent;
  countdown = 0;

  constructor(private authService: AuthService, private routerService: RoutingService, private renderer2: Renderer2,
    private el: ElementRef, private gaService: GAService) { }

  ngOnInit() {
    // used to display remaining time in modal
    this.pollScreenObservable = timer(0, 1000);

    // get remaining time
    this.subscription.push(
      TimeoutUtil.getTimeoutModal()
        .subscribe((config: TimeoutModalConfig) => {
          this.timeoutModal.show();
          this.countdown = config.countdown;
          this.pollTime();
        })
    );
  }

  ngOnDestroy() {
    this.subscription.forEach(sub => sub.unsubscribe());
  }

  pollTime() {
    if (this.pollScreenSubscription) {
      this.pollScreenSubscription.unsubscribe();
    }
    this.pollScreenSubscription = this.pollScreenObservable
      .subscribe(() => {
        this.screenPollTimerInterval();
      });
  }

  screenPollTimerInterval() {
    if (this.countdown > 0) {
      this.countdown--;
      this.setTimer(this.countdown);
    } else if (this.countdownStart) {
      this.logout();
    }
  }

  setTimer(countdown: number) {
    const animationSpinValue = 'rota ' + countdown + 's linear forwards';
    const animationFillerValue = 'opa ' + countdown + 's steps(1, end) forwards reverse';
    const animationMaskValue = 'opa ' + countdown + 's steps(1, end) forwards';

    if (this.countdown > 0 && !this.countdownStart) {
      this.countdownStart = true;
      this.renderer2.setStyle(this.el.nativeElement.getElementsByClassName('spin')[0], 'animation', animationSpinValue);
      this.renderer2.setStyle(this.el.nativeElement.getElementsByClassName('filler')[0], 'animation', animationFillerValue);
      this.renderer2.setStyle(this.el.nativeElement.getElementsByClassName('mask')[0], 'animation', animationMaskValue);
    }
  }

  logout() {
    this.subscription.push(
      this.authService.logout()
        .subscribe(response => {
          // track Logout success
          this.gaService.sendEvent(this.gaLabels.apiSuccess, this.gaLabels.category, this.gaLabels.timeoutLabel, response.status);
          if (response && response.status === REQUEST_STATUS.SUCCESS) {
            this.action = 'logout';
            this.closeModal();
            this.routerService.navigate('/auth/login');
          } else {
            this.continue();
          }
        }, (response: HttpRequestResponse) => {
          this.continue();
          // track Logout failed
          this.gaService.sendEvent(this.gaLabels.apiFailed, this.gaLabels.category, this.gaLabels.timeoutLabel, response.status);
        })
    );
  }

  closeModal() {
    TimeoutUtil.timeoutModalBtnClick(this.action);
    this.countdownStart = false;
    if (this.timeoutModal && this.timeoutModal.visible) {
      this.timeoutModal.hide();
    }
    if (this.pollScreenSubscription) {
      this.pollScreenSubscription.unsubscribe();
    }
  }

  continue() {
    this.action = 'continue';
    this.closeModal();
  }
}
