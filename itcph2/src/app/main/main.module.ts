import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { TranslateModule } from '@ngx-translate/core';
import { ReactiveFormsModule } from '@angular/forms';
import { NgbAccordionModule, NgbDropdownModule, NgbNavModule } from '@ng-bootstrap/ng-bootstrap';

// MODULES
import { MainRoutingModule } from './main-routing.module';
import { MySharedModule } from 'src/app/shared/shared.module';
import { CoreModule } from 'src/app/core/core.module';

// DIRECTIVES
import { DynamicComponentDirective } from './directives/dynamic-component.directive';

// SERVICES
import { DynamicComponentService } from './services/dynamic-component.service';
import { NavigationItem } from './layout/body/navigation/navigation';

// COMPONENTS
import { LAYOUT_COMPONENTS } from './layout';
import { MAIN_COMPONENTS } from './components';

@NgModule({
  declarations: [
    DynamicComponentDirective,
    ...LAYOUT_COMPONENTS,
    ...MAIN_COMPONENTS,
  ],
  imports: [
    CommonModule,
    ReactiveFormsModule,
    MainRoutingModule,
    TranslateModule,
    NgbAccordionModule,
    NgbDropdownModule,
    CoreModule,
    MySharedModule,
    NgbNavModule,
  ],
  providers: [
    DynamicComponentService,
    NavigationItem,
  ]
})
export class MainModule { }
