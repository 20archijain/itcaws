import { Component, Input, OnInit, SimpleChanges, OnChanges } from '@angular/core';
import * as Highcharts from 'highcharts';

import { CHART_DEFAULTS } from 'src/app/app.constants';
import { StackedGroupedColumnChartData } from 'src/app/core/interfaces/http-response.interface';

@Component({
  selector: 'app-stacked-grouped-vertical-column-chart',
  template: `
    <blockquote class="text-info" *ngIf="heading"><p>{{ heading }}</p></blockquote>
    <highcharts-chart
      [Highcharts]="Highcharts"
      [options]="stackGroupedColumnChartOptions"
      style="{{ style }}">
    </highcharts-chart>
  `,
  standalone: false,
})
export class StackedGroupedVerticalColumnChartComponent implements OnInit, OnChanges {
  @Input() width = CHART_DEFAULTS.WIDTH;
  @Input() height = CHART_DEFAULTS.HEIGHT;
  @Input() heading = '';
  @Input() data: StackedGroupedColumnChartData | any[] = [];
  Highcharts = Highcharts;
  stackGroupedColumnChartOptions!: Highcharts.Options;
  style: any = {
    display: 'block',
  };

  ngOnInit() {
    // Initialize chart options if data is available during component initialization
    if (this.data) {
      this.initializeChartOptions();
    }
  }

  ngOnChanges(changes: SimpleChanges) {
    // Respond to changes in input data
    if (changes?.data?.currentValue) {
      this.initializeChartOptions();
    }
  }

  private initializeChartOptions() {
    const data = this.data as StackedGroupedColumnChartData;
    if (data && data.seriesData) {
      this.stackGroupedColumnChartOptions = {
        chart: {
          type: 'column',
          width: 1200  // Set the desired width (you can adjust this value)
        },
        plotOptions: {
          column: {
            dataLabels: {
              enabled: true,
            },
            stacking: 'normal',  // Keep stacking enabled to stack values
          }
        },
        series: data?.seriesData || [],  // Now contains "Last Month" and "This Month" series
        title: {
          text: data?.title || ''
        },
        tooltip: {
          formatter: function () {
            return '<b>' + this.x + '</b><br/>' +  // Use branch name on x-axis
              this.series.name + ': ' + this.y + '<br/>';
          }
        },
        xAxis: {
          categories: data?.xAxisLabels || [],  // Branch names as categories
          title: {
            text: 'Branches'
          }
        },
        yAxis: {
          allowDecimals: false,
          min: 0,
          title: {
            text: data?.yAxisLabel || ''
          }
        },
      };
    } else {
      this.stackGroupedColumnChartOptions = {
        chart: {
          type: 'column',
          width: 1200  // Default width if no data
        },
        title: {
          text: 'No data available'
        }
      };
    }
  }
}
