import { Component, Input } from '@angular/core';

@Component({
    selector: 'app-accordion',
    templateUrl: './accordion.component.html',
    styleUrls: ['./accordion.component.scss'],
    standalone: false
})
export class AccordionComponent {
  @Input() monthlySalesData;
  isExpanded = false;

  toggleExpand(type: any): void {
    type.isExpanded = !type.isExpanded;
  }
}
