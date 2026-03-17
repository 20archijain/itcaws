import { Component, Input, OnInit } from '@angular/core';
import {
  ApexAxisChartSeries,
  ApexChart,
  ApexTitleSubtitle,
  ApexStroke
} from 'ng-apexcharts';

@Component({
  selector: 'app-apexlinechart',
  templateUrl: './apexlinechart.component.html'
})
export class ApexlinechartComponent implements OnInit {
  @Input() chartData;
  title: ApexTitleSubtitle = {};
  series: ApexAxisChartSeries = [];
  chart: ApexChart = {
    type: 'line',
    background: "white"
  };
  stroke: ApexStroke = {
  curve: 'smooth',
  width: 3
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
