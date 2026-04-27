import { Component, Input, OnChanges } from '@angular/core';
import { Color } from '@swimlane/ngx-charts';

import { LineChartComponent } from '../line/line-chart.component';
import { CHART_DEFAULTS } from 'src/app/app.constants';
import { Functions } from 'src/app/core/utils/functions.list';
import { ChartColorSchemeNames } from 'src/app/core/interfaces/common.interface';

@Component({
  selector: 'app-stack-vertical-column-chart',
  template: `
  <blockquote class="text-info" *ngIf="heading"><p>{{ heading }}</p></blockquote>
  <div [ngStyle]="style">
    <ngx-charts-bar-vertical-stacked
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
      [noBarWhenZero]="noBarWhenZero"
      [showDataLabel]="showDataLabel"
      [gradient]="gradient"
      [barPadding]="barPadding"
      [tooltipDisabled]="tooltipDisabled"
      [yScaleMax]="yScaleMax">
    </ngx-charts-bar-vertical-stacked>
  </div>
  `,
  standalone: false,
})
export class StackVerticalColumnChartComponent extends LineChartComponent implements OnChanges {
  view: [number, number] | undefined = undefined;
  @Input() height = CHART_DEFAULTS.HEIGHT;
  @Input() graphMaxHeight?: number;
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
  @Input() noBarWhenZero = false;
  @Input() showDataLabel = false;
  @Input() gradient = false;
  @Input() barPadding = 8;
  @Input() tooltipDisabled = false;
  @Input() yScaleMax = 0;
  @Input() appendPercentageOnXAxis = false;
  @Input() appendPercentageOnYAxis = false;
  style: any;
  themeScheme: string | Color | null = null;
  xAxisTickFormattingFn = this.xAxisTickFormatting.bind(this);
  yAxisTickFormattingFn = this.yAxisTickFormatting.bind(this);

  ngOnChanges() {
    if (this.graphMaxHeight && !this.style) {
      this.style = {
        height: (this.graphMaxHeight - 85) + 'px',
        width: '100%',
      };
    }

    const colorScheme = this.scheme || CHART_DEFAULTS.DEFAULT_THEME as ChartColorSchemeNames;
    this.themeScheme = Functions.getChartColorsScheme()[colorScheme];
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
