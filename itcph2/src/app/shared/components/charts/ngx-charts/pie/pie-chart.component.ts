import { Component, Input, OnChanges } from '@angular/core';
import { Color } from '@swimlane/ngx-charts';

import { LineChartComponent } from '../line/line-chart.component';
import { CHART_DEFAULTS } from 'src/app/app.constants';
import { Functions } from 'src/app/core/utils/functions.list';

@Component({
  selector: 'app-pie-chart',
  template: `
  <blockquote class="text-info" *ngIf="heading"><p>{{ heading }}</p></blockquote>
  <div [ngStyle]="style">
    <ngx-charts-pie-chart
      [view]="view"
      [results]="data"
      [scheme]="themeScheme"
      [labels]="!hidePieChartLabels"
      [trimLabels]="trimPieChartLabels"
      [maxLabelLength]="pieChartMaxLabelLength"
      [legend]="legend"
      [legendTitle]="legendTitle"
      [legendPosition]="legendPosition"
      [explodeSlices]="explodeSlices"
      [doughnut]="doughnut"
      [gradient]="gradient"
      [tooltipDisabled]="tooltipDisabled">
    </ngx-charts-pie-chart>
  </div>
  `
})
export class PieChartComponent extends LineChartComponent implements OnChanges {
  @Input() hidePieChartLabels = false;
  @Input() trimPieChartLabels = false;
  @Input() pieChartMaxLabelLength = 10;
  @Input() height = CHART_DEFAULTS.HEIGHT;
  @Input() graphMaxHeight: number;
  @Input() data = [];
  @Input() scheme = null;
  @Input() customColors = [];
  @Input() legend = false;
  @Input() legendTitle = '';
  @Input() legendPosition = CHART_DEFAULTS.LEGEND_POSITION;
  @Input() explodeSlices = false;
  @Input() doughnut = false;
  @Input() gradient = false;
  @Input() tooltipDisabled = false;
  view = undefined;
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
