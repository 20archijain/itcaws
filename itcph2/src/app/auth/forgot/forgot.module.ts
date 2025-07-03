import { NgModule } from '@angular/core';
import { TranslateModule } from '@ngx-translate/core';
import { ReactiveFormsModule } from '@angular/forms';

import { ForgotRoutingModule } from './forgot-routing.module';
import { ForgotComponent } from './forgot.component';
import { MySharedModule } from 'src/app/shared/shared.module';

@NgModule({
  declarations: [
    ForgotComponent,
  ],
  imports: [
    ReactiveFormsModule,
    ForgotRoutingModule,
    TranslateModule,
    MySharedModule,
  ],
})
export class ForgotModule { }
