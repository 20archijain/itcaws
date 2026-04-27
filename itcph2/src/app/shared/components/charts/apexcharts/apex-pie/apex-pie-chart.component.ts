import { Component, Input, OnInit } from '@angular/core';

@Component({
  selector: 'app-apex-pie-chart',
  template: `
    <blockquote class="text-info" *ngIf="heading"><p>{{ heading }}</p></blockquote>
    <app-apex-chart
      [chartID]="chartID || defaultChartID"
      [chartConfig]="piechartConfig">
    </app-apex-chart>
  `,
  standalone: false,
})
export class ApexPieChartComponent implements OnInit {
  @Input() heading = '';
  @Input() height = 320;
  @Input() data: any[] = [];
  @Input() colors = [];
  @Input() chartID!: string;
  defaultChartID = 'pc-1';
  piechartConfig: any = null;

  ngOnInit() {
    if (this.data) {
      const labels: any[] = [];
      const series: any[] = [];
      let colors = ['#4680ff', '#0e9e4a'];
      let height = 320;
      this.data.forEach(d => {
        labels.push(d.name);
        series.push(+d.value);
      });

      if (this.colors && this.colors.length) {
        colors = this.colors;
      }
      if (this.height) {
        height = this.height;
      }

      this.piechartConfig = {
        colors,
        labels,
        series,
        chart: {
          height,
          type: 'pie',
        },
        dataLabels: {
          dropShadow: {
            enabled: false,
          },
          enabled: true,
        },
        legend: {
          position: 'bottom',
          show: true,
        },
        responsive: [{
          breakpoint: 480,
          options: {
            chart: {
              width: 200
            },
            legend: {
              position: 'bottom'
            }
          }
        }],
      };
    }
  }
}
