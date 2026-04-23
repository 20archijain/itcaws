import { Component, Input } from '@angular/core';

@Component({
  selector: 'app-outlet-visit-accordion',
  templateUrl: './outlet-visit-accordion.component.html',
  styleUrls: ['./outlet-visit-accordion.component.scss'],
  standalone: false,
})
export class AccordionOutletVisitComponent {
  @Input() outletVisitedTableData: any;
  isExpanded = false;

  toggleExpand(type: any): void {
    type.isExpanded = !type.isExpanded;
  }
}
