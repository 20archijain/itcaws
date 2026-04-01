import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ReactiveFormsModule } from '@angular/forms';
import { RouterModule } from '@angular/router';
import { TranslateModule } from '@ngx-translate/core';
import {
  NgbCollapseModule, NgbDatepickerModule, NgbDropdownModule, NgbTimepickerModule,
  NgbTooltipModule
} from '@ng-bootstrap/ng-bootstrap';
import { NgScrollbarModule } from 'ngx-scrollbar';
import { NgClickOutsideDirective } from 'ng-click-outside2';
import { NgSelectModule } from '@ng-select/ng-select';
import { GoogleMapsModule } from '@angular/google-maps';
import { NgxChartsModule } from '@swimlane/ngx-charts';
import { HighchartsChartModule } from 'highcharts-angular';

import { CONTROLS_COMPONENTS } from './controls';
import { SHARED_COMPONENTS } from './components';
import { FilterPipe } from './pipes/filter.pipe';
import { SlicePipe } from './pipes/slice.pipe';

import { ScrollToFirstInvalidInputDirective } from './directives/scroll-to-first-invalid-input.directive';
import { LocationOnMapModalService } from 'src/app/core/services/location-on-map-modal.service';

@NgModule({
  declarations: [
    ...CONTROLS_COMPONENTS,
    ...SHARED_COMPONENTS,
    ScrollToFirstInvalidInputDirective,
    FilterPipe,
    SlicePipe,
  ],
  exports: [
    NgScrollbarModule,
    NgClickOutsideDirective,
    NgxChartsModule,
    ...CONTROLS_COMPONENTS,
    ...SHARED_COMPONENTS,
    ScrollToFirstInvalidInputDirective,
    FilterPipe,
    SlicePipe,
  ],
  imports: [
    CommonModule,
    RouterModule,
    ReactiveFormsModule,
    TranslateModule,
    NgbCollapseModule,
    NgbDatepickerModule,
    NgbDropdownModule,
    NgbTimepickerModule,
    NgbTooltipModule,
    NgScrollbarModule,
    NgClickOutsideDirective,
    NgSelectModule,
    NgxChartsModule,
    HighchartsChartModule,
    GoogleMapsModule,
  ],
  providers: [
    LocationOnMapModalService,
  ],
})
export class MySharedModule { }
