import { NgModule } from '@angular/core';
import { TranslateModule } from '@ngx-translate/core';
import { ReactiveFormsModule } from '@angular/forms';

import { TwoWayRoutingModule } from './two-way-routing.module';
import { TwoWayComponent } from './two-way.component';
import { MySharedModule } from 'src/app/shared/shared.module';

@NgModule({
  declarations: [
    TwoWayComponent,
  ],
  imports: [
    ReactiveFormsModule,
    TwoWayRoutingModule,
    TranslateModule,
    MySharedModule,
  ],
})
export class TwoWayModule { }
