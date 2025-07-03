import { NgModule } from '@angular/core';
import { RouterModule, Routes } from '@angular/router';

import { AuthGuardService } from 'src/app/core/guards/auth-guard.service';
import { CanGoBackGuard } from '../core/guards/can-go-back-guard.service';
import { URL_PARAMS_KEYS } from '../app.constants';
import { LayoutComponent } from './layout/layout.component';

const routes: Routes = [
  {
    canActivate: [AuthGuardService],
    canActivateChild: [AuthGuardService, CanGoBackGuard],
    children: [
      {
        component: LayoutComponent,
        path: `:${URL_PARAMS_KEYS.modc}/:${URL_PARAMS_KEYS.pmodc}`
      },
      {
        component: LayoutComponent,
        path: `:${URL_PARAMS_KEYS.modc}/:${URL_PARAMS_KEYS.pmodc}/:${URL_PARAMS_KEYS.id}`
      },
      {
        component: LayoutComponent,
        path: `:${URL_PARAMS_KEYS.modc}/:${URL_PARAMS_KEYS.pmodc}/:${URL_PARAMS_KEYS.id}/:${URL_PARAMS_KEYS.type}`
      }
    ],
    path: '',
  },
];

@NgModule({
  exports: [
    RouterModule,
  ],
  imports: [
    RouterModule.forChild(routes),
  ],
})
export class MainRoutingModule { }
