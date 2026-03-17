import { Component, Input, OnInit } from '@angular/core';
import {
  ApexAxisChartSeries,
  ApexChart,
  ApexTitleSubtitle,
} from 'ng-apexcharts';

@Component({
  selector: 'app-apexmixedchart',
  templateUrl: './apexmixedchart.component.html',
})
export class ApexmixedchartComponent implements OnInit {
  @Input() chartData;
  title: ApexTitleSubtitle = {};
  series: ApexAxisChartSeries = [];
  chart: ApexChart = {
    type: 'line',
    background: "white"
  };
  chartOptions = {
    stroke: {
      width: [0, 2, 5],
      curve: "smooth"
    }
  };
  chartlabel: any[] = [];
  chartCategories: any;

  ngOnInit(): void {
    this.initializeChartOption();
  }


  private initializeChartOption(): void {
    this.title = {
      text: this.chartData.title,
    };
    this.series = this.chartData.chartData;
    this.chartlabel = this.chartData.xAxisLabel1;

    this.chartCategories = this.chartData.xAxisLabel2;

    if (this.chartCategories.height) {
      this.chart.height = this.chartCategories.height;
    }

    if (this.chartCategories.width) {
      this.chart.width = this.chartCategories.width;
    }

    if (this.chartCategories.background) {
      this.chart.background = this.chartCategories.background;
    }
    if (this.chartCategories.type) {
      this.chart.type = this.chartCategories.type;
    }
  }
}
