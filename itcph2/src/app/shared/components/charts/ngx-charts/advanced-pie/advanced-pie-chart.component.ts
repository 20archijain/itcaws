import { Component, Input, OnChanges } from '@angular/core';
import { Color } from '@swimlane/ngx-charts';

import { LineChartComponent } from '../line/line-chart.component';
import { CHART_DEFAULTS } from 'src/app/app.constants';
import { Functions } from 'src/app/core/utils/functions.list';

@Component({
  selector: 'app-advance-pie-chart',
  template: `
  <blockquote class="text-info" *ngIf="heading"><p>{{ heading }}</p></blockquote>
  <div [ngStyle]="style">
    <ngx-charts-advanced-pie-chart
      [view]="view"
      [results]="data"
      [scheme]="themeScheme"
      [gradient]="gradient"
      [label]="advancedPieChartTotalLabel"
      [tooltipDisabled]="tooltipDisabled">
    </ngx-charts-advanced-pie-chart>
  </div>
  `
})
export class AdvancePieChartComponent extends LineChartComponent implements OnChanges {
  view = undefined;
  @Input() height = CHART_DEFAULTS.HEIGHT;
  @Input() data = [];
  @Input() graphMaxHeight: number;
  @Input() scheme = null;
  @Input() customColors = [];
  @Input() gradient = false;
  @Input() advancedPieChartTotalLabel = '';
  @Input() tooltipDisabled = false;
  style: any;
  themeScheme: string | Color = null;

  ngOnChanges() {
    if (this.graphMaxHeight && !this.style) {
      this.style = {
        height: (this.graphMaxHeight - 85) + 'px',
        width: '100%',
      };
    }

    this.themeScheme = Functions.getChartColorsScheme()[this.scheme || CHART_DEFAULTS.DEFAULT_THEME];
    // Set custom colors
    if (this.scheme && this.scheme === 'CUSTOM') {
      (this.themeScheme as Color).domain = this.customColors;
    }

    if (this.width && this.height) {
      this.view = [this.width, this.height];
    } else {
      this.view = undefined;
    }
  }
}
