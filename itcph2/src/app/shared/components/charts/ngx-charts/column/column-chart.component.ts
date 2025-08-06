import { Component, Input, OnChanges } from '@angular/core';
import { Color } from '@swimlane/ngx-charts';

import { LineChartComponent } from '../line/line-chart.component';
import { CHART_DEFAULTS } from 'src/app/app.constants';
import { Functions } from 'src/app/core/utils/functions.list';

@Component({
  selector: 'app-column-chart',
  template: `
  <blockquote class="text-info" *ngIf="heading"><p>{{ heading }}</p></blockquote>
  <div [ngStyle]="style">
    <ngx-charts-bar-vertical
      [view]="view"
      [results]="data"
      [scheme]="themeScheme"
      [legend]="legend"
      [legendTitle]="legendTitle"
      [legendPosition]="legendPosition"
      [xAxis]="xAxis"
      [yAxis]="yAxis"
      [showGridLines]="showGridLines"
      [roundDomains]="roundDomains"
      [showXAxisLabel]="!!xAxisLabel"
      [showYAxisLabel]="!!yAxisLabel"
      [xAxisLabel]="xAxisLabel"
      [yAxisLabel]="yAxisLabel"
      [trimXAxisTicks]="trimXAxisTicks"
      [trimYAxisTicks]="trimYAxisTicks"
      [rotateXAxisTicks]="rotateXAxisTicks"
      [maxXAxisTickLength]="maxXAxisTickLength"
      [maxYAxisTickLength]="maxYAxisTickLength"
      [xAxisTickFormatting]="xAxisTickFormattingFn"
      [yAxisTickFormatting]="yAxisTickFormattingFn"
      [showDataLabel]="showDataLabel"
      [noBarWhenZero]="noBarWhenZero"
      [gradient]="gradient"
      [barPadding]="barPadding"
      [tooltipDisabled]="tooltipDisabled"
      [yScaleMax]="yScaleMax"
      [yScaleMin]="yScaleMin"
      [roundEdges]="roundEdges">
    </ngx-charts-bar-vertical>
  </div>
  `
})
export class ColumnChartComponent extends LineChartComponent implements OnChanges {
  view = undefined;
  @Input() height = CHART_DEFAULTS.HEIGHT;
  @Input() graphMaxHeight: number;
  @Input() data = [];
  @Input() scheme = null;
  @Input() customColors = [];
  @Input() legend = false;
  @Input() legendTitle = '';
  @Input() legendPosition = CHART_DEFAULTS.LEGEND_POSITION;
  @Input() xAxis = false;
  @Input() yAxis = false;
  @Input() showGridLines = true;
  @Input() roundDomains = false;
  @Input() trimXAxisTicks = false;
  @Input() trimYAxisTicks = false;
  @Input() rotateXAxisTicks = false;
  @Input() maxXAxisTickLength = 16;
  @Input() maxYAxisTickLength = 16;
  @Input() appendPercentageOnXAxis = false;
  @Input() appendPercentageOnYAxis = false;
  @Input() showDataLabel = false;
  @Input() noBarWhenZero = false;
  @Input() gradient = false;
  @Input() barPadding = 8;
  @Input() tooltipDisabled = false;
  @Input() yScaleMin = 0;
  @Input() yScaleMax = 0;
  @Input() roundEdges = false;
  style: any;
  themeScheme: string | Color = null;
  xAxisTickFormattingFn = this.xAxisTickFormatting.bind(this);
  yAxisTickFormattingFn = this.yAxisTickFormatting.bind(this);

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

  xAxisTickFormatting(label: string) {
    if (this.appendPercentageOnXAxis) {
      return `${label}%`;
    } else {
      return label;
    }
  }

  yAxisTickFormatting(label: string) {
    if (this.appendPercentageOnYAxis) {
      return `${label}%`;
    } else {
      return label;
    }
  }
}
