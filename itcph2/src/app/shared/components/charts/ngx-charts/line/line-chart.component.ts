import { Component, Input, HostBinding } from '@angular/core';
import { curveMonotoneX } from 'd3-shape'; // Import the curve function from d3-shape

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
        [legendPosition]="'below'"
        [autoScale]="true"
        [showGridLines]="true"
        [showDataLabel]="false"
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

  // Import curveMonotoneX and apply it in the chart via the [curve] binding
  curveMonotoneX = curveMonotoneX;
  @HostBinding('style.width') @Input() width = '100%';
}
