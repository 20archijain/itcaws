import { Injectable, OnDestroy } from '@angular/core';
import { ActivatedRouteSnapshot, Route, RouterStateSnapshot } from '@angular/router';
import { TranslateService } from '@ngx-translate/core';
import { Subscription } from 'rxjs';

import { URL_PARAMS_KEYS } from 'src/app/app.constants';
import { ToastrService } from '../services/toastr.service';
import { SessionUtil } from '../utils/session.util';
import { RoutingService } from '../services/routing.service';
import { SessionModuleObject } from '../interfaces/common.interface';

@Injectable()
export class AuthGuardService  implements OnDestroy {
  private subscription: Subscription[] = [];
  private invalidModuleErrorMessage = 'not.invalidModule';

  constructor(private routingService: RoutingService, private translate: TranslateService,
    private toastr: ToastrService) {
    this.subscription.push(
      translate.get(this.invalidModuleErrorMessage)
        .subscribe(translatedMsg => {
          this.invalidModuleErrorMessage = translatedMsg;
        })
    );
  }

  private checkValidUser(url: string) {
    // Login or forgot or two-way
    if (url && (url.indexOf('login') > -1 || url.indexOf('forgot') > -1 || url.indexOf('two-way') > -1)) {
      // Already logged in, send back to previous screen
      if (SessionUtil.getItem('token')) {
        this.routingService.back();

        return false;
      }
    } else {
      if (!SessionUtil.getItem('token')) {
        // Not logged in, send back to login screen
        this.routingService.navigate('/auth/login');

        return false;
      }
    }

    return true;
  }

  canActivate(next: ActivatedRouteSnapshot, state: RouterStateSnapshot) {
    return this.checkValidUser(state.url);
  }

  canLoad(route: Route) {
    return this.checkValidUser(route.path);
  }

  canActivateChild(next: ActivatedRouteSnapshot) {
    const urlParams = next.params;
    const allModulesList: SessionModuleObject = JSON.parse(SessionUtil.getItem('modules'));

    if (allModulesList) {
      const moduleCodes = Object.keys(allModulesList);

      if (!urlParams || !urlParams[URL_PARAMS_KEYS.modc] || !urlParams[URL_PARAMS_KEYS.pmodc]) {
        return false;
      }

      // parent module request
      if (urlParams[URL_PARAMS_KEYS.pmodc] === '0') {
        // if module code not exist in all module list
        if (moduleCodes && moduleCodes.indexOf(urlParams[URL_PARAMS_KEYS.modc]) === -1) {
          this.routingService.back();
          this.toastr.toastr({ type: 'error', msg: this.invalidModuleErrorMessage });

          return false;
        }
      } else {
        // child module request
        // if parent module code not exist in all module list
        if (moduleCodes && moduleCodes.indexOf(urlParams[URL_PARAMS_KEYS.pmodc]) === -1) {
          this.routingService.back();
          this.toastr.toastr({ type: 'error', msg: this.invalidModuleErrorMessage });

          return false;
        } else {
          const subModuleCodes = Object.keys(allModulesList[urlParams[URL_PARAMS_KEYS.pmodc]].submodules);
          // check if module code exist in submodules of parent module
          if (subModuleCodes && subModuleCodes.indexOf(urlParams[URL_PARAMS_KEYS.modc]) === -1) {
            this.routingService.back();
            this.toastr.toastr({ type: 'error', msg: this.invalidModuleErrorMessage });

            return false;
          }
        }
      }
    }

    return true;
  }

  ngOnDestroy() {
    this.subscription.forEach(sub => sub.unsubscribe());
  }
}
