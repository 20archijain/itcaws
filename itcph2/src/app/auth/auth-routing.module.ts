import { NgModule } from '@angular/core';
import { RouterModule, Routes } from '@angular/router';

import { AuthGuardService } from '../core/guards/auth-guard.service';

const routes: Routes = [
  {
    children: [
      {
        path: '',
        pathMatch: 'full',
        redirectTo: 'login',
      },
      {
        canLoad: [AuthGuardService],
        loadChildren: () => import('./login/login.module').then(module => module.LoginModule),
        path: 'login',
      },
      {
        canLoad: [AuthGuardService],
        loadChildren: () => import('./forgot/forgot.module').then(module => module.ForgotModule),
        path: 'forgot',
      },
      {
        canLoad: [AuthGuardService],
        loadChildren: () => import('./two-way/two-way.module').then(module => module.TwoWayModule),
        path: 'two-way',
      },
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
export class AuthRoutingModule { }
