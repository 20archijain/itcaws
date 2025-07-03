import { Component, Input, OnChanges } from '@angular/core';

import { LineChartComponent } from '../line/line-chart.component';
import { CHART_DEFAULTS } from 'src/app/app.constants';
import { ThemeDomainColorList } from 'src/app/core/interfaces/common.interface';
import { Functions } from 'src/app/core/utils/functions.list';

@Component({
  selector: 'app-normalized-vertical-column-chart',
  template: `
  <blockquote class="text-info" *ngIf="heading"><p>{{ heading }}</p></blockquote>
  <div [ngStyle]="style">
    <ngx-charts-bar-vertical-normalized
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
      [noBarWhenZero]="noBarWhenZero"
      [xAxisLabel]="xAxisLabel"
      [yAxisLabel]="yAxisLabel"
      [trimXAxisTicks]="trimXAxisTicks"
      [trimYAxisTicks]="trimYAxisTicks"
      [rotateXAxisTicks]="rotateXAxisTicks"
      [maxXAxisTickLength]="maxXAxisTickLength"
      [maxYAxisTickLength]="maxYAxisTickLength"
      [xAxisTickFormatting]="xAxisTickFormattingFn"
      [yAxisTickFormatting]="yAxisTickFormattingFn"
      [gradient]="gradient"
      [barPadding]="barPadding"
      [tooltipDisabled]="tooltipDisabled">
        <ng-template #tooltipTemplate let-model="model">
          <div class="p-2 f-14 mb-0">
            <p class="pb-1 mb-0">{{ model.tooltipText }}</p>
            <p class="pb-0 mb-0">{{ model.tooltipValue }}</p>
          </div>
        </ng-template>
    </ngx-charts-bar-vertical-normalized>
  </div>
  `
})
export class NormalizedVerticalColumnChartComponent extends LineChartComponent implements OnChanges {
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
  @Input() noBarWhenZero = false;
  @Input() trimXAxisTicks = false;
  @Input() trimYAxisTicks = false;
  @Input() rotateXAxisTicks = false;
  @Input() maxXAxisTickLength = 16;
  @Input() maxYAxisTickLength = 16;
  @Input() appendPercentageOnXAxis = false;
  @Input() appendPercentageOnYAxis = false;
  @Input() gradient = false;
  @Input() barPadding = 8;
  @Input() tooltipDisabled = false;
  style: any;
  themeScheme: ThemeDomainColorList = null;
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
      this.themeScheme.domain = this.customColors;
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
