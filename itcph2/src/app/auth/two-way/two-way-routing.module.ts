import { NgModule } from '@angular/core';
import { RouterModule, Routes } from '@angular/router';

import { TwoWayComponent } from './two-way.component';

const routes: Routes = [
  {
    component: TwoWayComponent,
    path: '',
  },
];

@NgModule({
  exports: [RouterModule],
  imports: [RouterModule.forChild(routes)],
})
export class TwoWayRoutingModule { }
