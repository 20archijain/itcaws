import { NgModule } from '@angular/core';
import { RouterModule, Routes } from '@angular/router';

import { NotFoundComponent } from './core/components/not-found/not-found.component';
import { AuthGuardService } from './core/guards/auth-guard.service';

const routes: Routes = [
  {
    path: '',
    pathMatch: 'full',
    redirectTo: 'auth'
  },
  {
    loadChildren: () => import('./auth/auth.module').then(module => module.AuthModule),
    path: 'auth',
  },
  {
    canLoad: [AuthGuardService],
    loadChildren: () => import('./main/main.module').then(module => module.MainModule),
    path: 'app',
  }, {
    component: NotFoundComponent,
    path: '**'
  },
];

@NgModule({
  exports: [RouterModule],
  imports: [RouterModule.forRoot(routes, { useHash: true })],
})
export class AppRoutingModule { }
