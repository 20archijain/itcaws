import { Component, Input, HostBinding } from '@angular/core';
import { LegendPosition } from '@swimlane/ngx-charts';
import { curveMonotoneX } from 'd3-shape'; // Import the curve function from d3-shape

import { CHART_DEFAULTS } from 'src/app/app.constants';

@Component({
  selector: 'app-line-chart',
  template: `
    <h4 class="chart-title mt-4" style="text-align: center;">{{ heading }}</h4>
    <div [ngStyle]="{height: '225px'}">
      <ngx-charts-line-chart
        *ngIf="lineChartData?.length > 0"
        [results]="lineChartData"
        [xAxis]="true"
        [yAxis]="true"
        [showXAxisLabel]="true"
        [showYAxisLabel]="true"
        [xAxisLabel]="xAxisLabel"
        [yAxisLabel]="yAxisLabel"
        [legend]="true"
        [legendTitle]="''"
        [legendPosition]="legendPosition"
        [autoScale]="true"
        [showGridLines]="true"
        [curve]="curveMonotoneX">
      </ngx-charts-line-chart>
    </div>
  `,
  styles: [`
    ngx-charts-line-chart {
      display: block;
      width: 100%;
    }
  `]
})
export class LineChartComponent {
  @Input() lineChartData: any[] = []; // Holds the transformed chart data
  @Input() xAxisLabel: string; // Set to 'Date'
  @Input() yAxisLabel: string;
  @Input() heading: string;
  @Input() width: number;
  legendPosition: LegendPosition = CHART_DEFAULTS.LEGEND_POSITION;

  // Import curveMonotoneX and apply it in the chart via the [curve] binding
  curveMonotoneX = curveMonotoneX;
  @HostBinding('style.width') @Input() hostWidth = '100%';
}
