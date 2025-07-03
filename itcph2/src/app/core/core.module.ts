import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { HTTP_INTERCEPTORS } from '@angular/common/http';
import { ToastrModule } from 'ngx-toastr';
import { TranslateModule } from '@ngx-translate/core';

import { MySharedModule } from 'src/app/shared/shared.module';
import { AuthGuardService } from './guards/auth-guard.service';
import { CanGoBackGuard } from './guards/can-go-back-guard.service';
import { SERVICES } from './services';
import { HttpReqInterceptor } from './interceptors/http-req.interceptor';
import { CORE_COMPONENTS } from './components';

@NgModule({
  declarations: [
    ...CORE_COMPONENTS,
  ],
  exports: [
    ...CORE_COMPONENTS,
  ],
  imports: [
    CommonModule,
    MySharedModule,
    TranslateModule,
    ToastrModule.forRoot(),
  ],
  providers: [
    AuthGuardService,
    CanGoBackGuard,
    {
      multi: true,
      provide: HTTP_INTERCEPTORS,
      useClass: HttpReqInterceptor
    },
    ...SERVICES,
  ],
})
export class CoreModule { }
