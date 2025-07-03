import { Component, Input, OnDestroy, OnInit } from '@angular/core';
import ApexCharts from 'apexcharts/dist/apexcharts.common.js';
import { Subscription } from 'rxjs';

import { ApexChartService } from 'src/app/core/services/apex-chart.service';

@Component({
  selector: 'app-apex-chart',
  template: `<div id="{{this.chartID}}"></div>`,
})
export class ApexChartComponent implements OnDestroy, OnInit {
  private subscription: Subscription[] = [];
  @Input() chartID: string;
  @Input() chartConfig: any;
  @Input() xAxis: any;
  @Input() newData: any;

  public chart: any;

  constructor(private apexEvent: ApexChartService) { }

  ngOnInit() {
    setTimeout(() => {
      this.chart = new ApexCharts(document.querySelector('#' + this.chartID), this.chartConfig);
      this.chart.render();
    });

    this.subscription.push(
      this.apexEvent.changeTimeRange
        .subscribe(() => {
          if (this.xAxis) {
            this.chart.updateOptions({
              xaxis: this.xAxis
            });
          }
        })
    );

    this.subscription.push(
      this.apexEvent.changeSeriesData
        .subscribe(() => {
          if (this.newData) {
            this.chart.updateSeries([{
              data: this.newData
            }]);
          }
        })
    );
  }

  ngOnDestroy() {
    this.subscription.forEach(sub => sub.unsubscribe());
  }
}
