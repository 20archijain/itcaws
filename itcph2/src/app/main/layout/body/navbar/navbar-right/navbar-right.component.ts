import { Component, OnDestroy, OnInit } from '@angular/core';
import { NgbDropdownConfig } from '@ng-bootstrap/ng-bootstrap';
import { Subscription } from 'rxjs';

import { LoginDataUser } from 'src/app/core/interfaces/http-response.interface';
import { SessionUtil } from 'src/app/core/utils/session.util';
import { AuthService } from 'src/app/core/services/auth.service';
import { REQUEST_STATUS } from 'src/app/app.constants';
import { RoutingService } from 'src/app/core/services/routing.service';
import { TimeoutUtil } from 'src/app/core/utils/timeout.util';
import { GAService } from 'src/app/core/services/ga.service';
import { GA_ACTION_LIST } from 'src/app/core/utils/GAMapping';
import { HttpRequestResponse } from 'src/app/core/interfaces/common.interface';

@Component({
  providers: [NgbDropdownConfig],
  selector: 'app-navbar-right',
  templateUrl: './navbar-right.component.html',
})
export class NavbarRightComponent implements OnDestroy, OnInit {
  private subscription: Subscription[] = [];
  private gaLabels = GA_ACTION_LIST.auth.logout;
  userInfo: LoginDataUser;

  constructor(private authService: AuthService, private routerService: RoutingService, private gaService: GAService) { }

  ngOnInit() {
    this.userInfo = JSON.parse(SessionUtil.getItem('user'));
  }

  ngOnDestroy() {
    this.subscription.forEach(sub => sub.unsubscribe());
  }

  logout() {
    this.subscription.push(
      this.authService.logout()
        .subscribe(response => {
          // track Logout success
          this.gaService.sendEvent(this.gaLabels.apiSuccess, this.gaLabels.category, this.gaLabels.label, response?.status);
          if (response && response.status === REQUEST_STATUS.SUCCESS) {
            TimeoutUtil.timeoutModalBtnClick('logout');
            this.routerService.navigate('/auth/login');
          } else {
            TimeoutUtil.timeoutModalBtnClick('continue');
          }
        }, (response: HttpRequestResponse) => {
          TimeoutUtil.timeoutModalBtnClick('continue');
          // track Logout failed
          this.gaService.sendEvent(this.gaLabels.apiFailed, this.gaLabels.category, this.gaLabels.label, response.status);
        })
    );
  }
}
