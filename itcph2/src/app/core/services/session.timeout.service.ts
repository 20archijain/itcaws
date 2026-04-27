import { Injectable, OnDestroy } from '@angular/core';
import { Observable, Subscription, timer } from 'rxjs';
import * as ifvisible from 'ifvisible.js';

import { Timeout } from 'src/app/app.constants';
import { SessionUtil } from '../utils/session.util';
import { TimeoutUtil } from '../utils/timeout.util';
import { ConfirmationModalService } from './confirmation-modal.service';

@Injectable()
export class SessionTimeoutService implements OnDestroy {
  private subscription: Subscription[] = [];
  private isIdleStarted = false;
  private isModalOpen = false;
  private lastWakeUpDate!: Date;
  private pollScreenObservable!: Observable<number>;
  private pollScreenSubscription!: Subscription;
  private domEvents = 'click mousemove focus keydown touchstart mousedown mousewheel DOMMouseScroll touchmove scroll';

  constructor(private confirmationModalService: ConfirmationModalService) {
    this.init();

    // toggle timer on login or logout or continue
    this.subscription.push(
      TimeoutUtil.getTimeout()
        .subscribe((showTimeout: boolean) => {
          // user logged in or continue, start timer
          this.isModalOpen = false;
          this.resetTimer();
          if (showTimeout) {
            this.startTimer();
          } else {
            this.stopTimer();
          }
        })
    );

    // start timeout if login on page reload
    if (SessionUtil.getItem('token') && !this.isModalOpen) {
      TimeoutUtil.startTimeout();
    }
  }

  init() {
    // Add events on DOM to listen for IDLE
    this.onUserActivity();

    // start timer
    this.pollScreenObservable = timer(0, 1000);
    this.lastWakeUpDate = new Date();

    // set idle duration
    ifvisible.setIdleDuration(Timeout.IDLE_TIME);

    // idle start
    ifvisible.on('idle', () => {
      this.isIdleStarted = true;
    });

    // idle stop
    ifvisible.on('wakeup', () => {
      this.lastWakeUpDate = new Date();
      this.isIdleStarted = false;
    });
  }

  onUserActivity() {
    this.domEvents.split(' ').forEach((eventName) => {
      document.removeEventListener(eventName, this.userActivity);
      document.addEventListener(eventName, this.userActivity.bind(this));
    });
  }

  userActivity() {
    this.resetTimer();
  }

  resetTimer() {
    ifvisible.wakeup();
    this.isIdleStarted = false;
    this.lastWakeUpDate = new Date();
  }

  startTimer() {
    if (this.pollScreenSubscription) {
      this.pollScreenSubscription.unsubscribe();
    }
    this.pollScreenSubscription = this.pollScreenObservable
      .subscribe(() => {
        this.checkCurrentSession();
      });
  }

  checkCurrentSession() {
    // if user is idle
    if (this.isIdleStarted && !this.isModalOpen) {
      const passedSeconds = Math.floor(((new Date()).valueOf() - this.lastWakeUpDate.valueOf()) / 1000);

      // user has not done any activity till the timer timeout
      if ((passedSeconds && passedSeconds >= (Timeout.IDLE_TIME + Timeout.TIMEOUT))) {
        this.resetTimer();
        this.stopTimer();
      } else if (passedSeconds && passedSeconds >= Timeout.IDLE_TIME) {
        // idle time over, show timeout modal
        this.stopTimer();
        this.confirmationModalService.hide();
        this.setTimer(Timeout.TIMEOUT + Timeout.IDLE_TIME - passedSeconds);
      }
    }
  }

  stopTimer() {
    if (this.pollScreenSubscription) {
      this.pollScreenSubscription.unsubscribe();
    }
    this.domEvents.split(' ').forEach((eventName) => {
      document.removeEventListener(eventName, this.userActivity);
    });
  }

  setTimer(countdown: number) {
    this.isModalOpen = true;
    TimeoutUtil.toggleTimeoutModal({ countdown });
  }

  ngOnDestroy() {
    this.subscription.forEach(sub => sub.unsubscribe());
  }
}
