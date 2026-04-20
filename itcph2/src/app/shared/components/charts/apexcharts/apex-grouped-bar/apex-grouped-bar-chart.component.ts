import {
  Component,
  Input,
  OnChanges,
  SimpleChanges,
  OnDestroy,
  AfterViewInit,
} from '@angular/core';
import ApexCharts from 'apexcharts/dist/apexcharts.common.js';

/** Input item: { name: string, series: { name: string, value: number }[] } */
export interface GroupedBarCategory {
  name: string;
  series: { name: string; value: number }[];
}

@Component({
    selector: 'app-apex-grouped-bar-chart',
    template: `
    <h4 class="chart-title mt-4" style="text-align: center;">{{ heading }}</h4>
    <div class="apex-grouped-bar-scroll" title="Scroll horizontally to see all levels">
      <div class="apex-grouped-bar-inner" [style.min-width.px]="chartWidth">
        <div [id]="chartId"></div>
      </div>
    </div>
  `,
    styles: [
        `
      .apex-grouped-bar-scroll {
        width: 100%;
        overflow-x: auto;
        overflow-y: hidden;
        -webkit-overflow-scrolling: touch;
      }
      .apex-grouped-bar-inner {
        display: inline-block;
        min-height: 1px;
      }
      .apex-grouped-bar-inner ::ng-deep .apexcharts-canvas {
        margin: 0;
      }
    `,
    ],
    standalone: false
})
export class ApexGroupedBarChartComponent implements OnChanges, AfterViewInit, OnDestroy {
  @Input() transformedChartData: GroupedBarCategory[] = [];
  @Input() heading = '';
  @Input() height = 400;
  @Input() xAxisLabel = 'Level';
  @Input() yAxisLabel = 'Value';
  /** Min px width per category when there are many (enables horizontal scroll). */
  @Input() pxPerCategory = 40;

  chartId = 'apex-grouped-bar-' + Math.random().toString(36).slice(2, 10);
  chart: ApexCharts | null = null;
  chartWidth = 800;

  ngAfterViewInit(): void {
    this.buildAndRender();
  }

  ngOnChanges(changes: SimpleChanges): void {
    if (changes['transformedChartData']) {
      this.buildAndRender();
    }
  }

  ngOnDestroy(): void {
    this.destroyChart();
  }

  private destroyChart(): void {
    if (this.chart) {
      this.chart.destroy();
      this.chart = null;
    }
  }

  private buildAndRender(): void {
    this.destroyChart();
    const data = this.transformedChartData || [];
    if (data.length === 0) {
      return;
    }

    const categories = data.map((d) => d.name || '');
    const seriesNames = data[0]?.series?.map((s) => s.name) || [
      'Planned Outlets',
      'Outlets ReVisit',
      'Total Sales',
    ];
    const series = seriesNames.map((seriesName) => ({
      name: seriesName,
      data: data.map((d) => {
        const s = d.series?.find((x) => x.name === seriesName);
        const v = s?.value;
        return typeof v === 'number' && Number.isFinite(v) ? v : 0;
      }),
    }));

    this.chartWidth = Math.max(800, data.length * this.pxPerCategory);

    const options: ApexCharts.ApexOptions = {
      chart: {
        type: 'bar',
        height: this.height,
        width: this.chartWidth,
        toolbar: { show: true },
        zoom: { enabled: false },
      },
      series,
      xaxis: {
        categories,
        title: { text: this.xAxisLabel },
        labels: {
          rotate: -45,
          rotateAlways: categories.length > 20,
        },
      },
      yaxis: {
        title: { text: this.yAxisLabel },
      },
      plotOptions: {
        bar: {
          horizontal: false,
          columnWidth: '55%',
          dataLabels: { position: 'top' as const },
        },
      },
      dataLabels: {
        enabled: false,
      },
      legend: {
        position: 'bottom',
        show: true,
      },
      colors: ['#8B0000', '#5B9BD5', '#ED7D31'],
      grid: {
        xaxis: { lines: { show: false } },
        yaxis: { lines: { show: true } },
      },
      tooltip: {
        shared: true,
        intersect: false,
      },
    };

    const el = document.getElementById(this.chartId);
    if (el) {
      this.chart = new ApexCharts(el, options);
      this.chart.render();
    }
  }
}
