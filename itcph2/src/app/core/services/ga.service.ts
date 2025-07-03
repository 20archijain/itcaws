import { Injectable, OnDestroy } from '@angular/core';
import { ActivatedRoute, NavigationEnd, Router } from '@angular/router';
import { Subscription } from 'rxjs';

import { GA_TRACKING_ID } from 'src/app/app.constants';
import { GAPageTracking } from '../interfaces/helpers.interface';
import { Functions } from '../utils/functions.list';

@Injectable()
export class GAService implements OnDestroy {
  private gtag: any;
  private subscription: Subscription[] = [];

  constructor(private router: Router, private route: ActivatedRoute) { }

  private trackPage(pageInfo: GAPageTracking) {
    this.gtag('config', GA_TRACKING_ID, pageInfo);
  }

  ngOnDestroy() {
    this.subscription.forEach(sub => sub.unsubscribe());
  }

  initiateGMapTracking() {
    this.gtag = window['gtag'];

    if (this.gtag) {
      // disable automatically page hit tracking
      this.gtag('config', GA_TRACKING_ID, { send_page_view: false });

      this.subscription.push(
        this.router.events
          .subscribe(event => {
            if (event instanceof NavigationEnd) {

              const routeParams = event.url.split('/');
              const moduleInfo = Functions.getModuleInfo(routeParams[1], routeParams[2], routeParams[3]);

              // enable manually page hit tracking on route change
              this.trackPage({
                page_path: event.urlAfterRedirects,
                page_title: moduleInfo ? moduleInfo.name : '',
              });
            }
          })
      );
    }
  }

  sendEvent(action: string, category: string, label?: string, value?: number) {
    if (this.gtag) {
      this.gtag('event', action, {
        event_category: category || '',
        event_label: label || '',
        value: value || '',
      });
    }
  }
}
