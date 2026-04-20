import { Component, EventEmitter, Input, Output, TemplateRef } from '@angular/core';
import { UntypedFormGroup } from '@angular/forms';

import { CHART_CONFIG, CHART_DEFAULTS } from 'src/app/app.constants';
import { StatisticsConfig } from 'src/app/core/interfaces/common.interface';
import { Functions } from 'src/app/core/utils/functions.list';

@Component({
    selector: 'app-statistics',
    templateUrl: './statistics.component.html',
    standalone: false
})
export class StatisticsComponent {
  @Output() private onFilter = new EventEmitter();
  typeConfig = CHART_CONFIG;
  @Input() headerTitle = '';
  @Input() statsConfig: StatisticsConfig[] = [];
  @Input() searchTemplate: TemplateRef<any> = null;
  @Input() extraTemplate: TemplateRef<any> = null;
  @Input() searchForm: UntypedFormGroup;
  @Input() sizeGraph = false;
  legendPosition = CHART_DEFAULTS.LEGEND_POSITION;
  colorsScheme = Functions.getChartColorsScheme();

  onSearch() {
    this.onFilter.emit();
  }

  get scheme() {
    return this.searchForm && this.searchForm.get('theme') && this.searchForm.get('theme').value;
  }
}
