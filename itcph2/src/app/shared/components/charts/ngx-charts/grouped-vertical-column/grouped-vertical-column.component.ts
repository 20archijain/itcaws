import { Component, Input, OnInit, HostListener } from '@angular/core';
import { LegendPosition } from '@swimlane/ngx-charts';

import { CHART_DEFAULTS } from 'src/app/app.constants';

@Component({
    selector: 'app-grouped-vertical-column-chart',
    template: `
    <h4 class="chart-title mt-4" style="text-align: center;">{{ heading }}</h4>
    <div [ngStyle]="{ height: height + 'px', width: '100%' }">
      <div *ngIf="transformedChartData && transformedChartData.length; else noData">
        <ngx-charts-bar-vertical-2d
          [view]="[calculatedWidth, height]"
          [results]="transformedChartData"
          [legend]="legend"
          [legendTitle]="legendTitle"
          [legendPosition]="legendPosition"
          [xAxis]="xAxis"
          [yAxis]="yAxis"
          [roundDomains]="roundDomains"
          [showXAxisLabel]="true"
          [showYAxisLabel]="true"
          [xAxisLabel]="xAxisLabel"
          [yAxisLabel]="yAxisLabel"
          [showDataLabel]="showDataLabel"
          [barPadding]="barPadding"
          [groupPadding]="groupPadding"
          [tooltipDisabled]="tooltipDisabled">
        </ngx-charts-bar-vertical-2d>
      </div>
      <ng-template #noData>
        <p>No data available to display.</p>
      </ng-template>
    </div>
  `,
    standalone: false
})
export class GroupedVerticalColumnChartComponent implements OnInit {
  @Input() transformedChartData: any[] = [];
  @Input() xAxisLabel: string;
  @Input() yAxisLabel: string;
  @Input() heading: string;
  @Input() legendTitle: string;
  @Input() height = 225; // Default height
  @Input() minWidth = 300; // Minimum width for responsiveness
  @Input() maxWidth = 600; // Maximum width for responsiveness
  calculatedWidth = 600; // Adjusted width based on the container or screen size
  legend = true;
  legendPosition: LegendPosition = CHART_DEFAULTS.LEGEND_POSITION;
  xAxis = true;
  yAxis = true;
  roundDomains = true;
  showDataLabel = false;
  barPadding = 0;
  groupPadding = 4;
  tooltipDisabled = false;

  ngOnInit(): void {
    this.calculateWidth();
  }

  // Listen for window resize events
  @HostListener('window:resize')
  onResize(): void {
    this.calculateWidth();
  }

  // Calculate the width dynamically based on the container or screen size
  private calculateWidth(): void {
    const screenWidth = window.innerWidth;
    this.calculatedWidth = Math.max(
      this.minWidth,
      Math.min(this.maxWidth, screenWidth - 50) // Add some padding for responsiveness
    );
  }
}
