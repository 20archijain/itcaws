import { Component, Input, OnChanges } from '@angular/core';
import { Color } from '@swimlane/ngx-charts';

import { LineChartComponent } from '../line/line-chart.component';
import { CHART_DEFAULTS } from 'src/app/app.constants';
import { Functions } from 'src/app/core/utils/functions.list';
import { ChartColorSchemeNames } from 'src/app/core/interfaces/common.interface';

@Component({
  selector: 'app-pie-grid-chart',
  template: `
  <blockquote class="text-info" *ngIf="heading"><p>{{ heading }}</p></blockquote>
  <div [ngStyle]="style">
    <ngx-charts-pie-grid
      [view]="view"
      [results]="data"
      [scheme]="themeScheme"
      [label]="pieGridLabel"
      [tooltipDisabled]="tooltipDisabled"
      [designatedTotal]="pieGridDesignatedTotal"
      [minWidth]="pieGridMinEachGraphWidth">
    </ngx-charts-pie-grid>
  </div>
  `,
  standalone: false,
})
export class PieGridChartComponent extends LineChartComponent implements OnChanges {
  @Input() pieGridLabel = '';
  @Input() pieGridDesignatedTotal?: number;
  @Input() pieGridMinEachGraphWidth?: number;
  @Input() height = CHART_DEFAULTS.HEIGHT;
  @Input() graphMaxHeight?: number;
  @Input() data = [];
  @Input() scheme = null;
  @Input() customColors = [];
  @Input() tooltipDisabled = false;
  view: [number, number] | undefined = undefined;
  style: any;
  themeScheme: string | Color | null = null;

  ngOnChanges() {
    if (this.graphMaxHeight && !this.style) {
      this.style = {
        height: (this.graphMaxHeight - 85) + 'px',
        width: '100%',
      };
    }

    const colorScheme = this.scheme || CHART_DEFAULTS.DEFAULT_THEME as ChartColorSchemeNames;
    this.themeScheme = Functions.getChartColorsScheme()[colorScheme];
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
