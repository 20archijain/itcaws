import { Component, EventEmitter, Input, Output } from '@angular/core';
import { trigger, style, transition, animate } from '@angular/animations';

@Component({
  selector: 'app-color-accordion',
  templateUrl: './color-accordion.component.html',
  styleUrls: ['./color-accordion.component.scss'],
  animations: [
    trigger('columnAnimation', [
      transition(':enter', [
        style({ opacity: 0, transform: 'translateX(50px)' }),
        animate('300ms ease-out', style({ opacity: 1, transform: 'translateX(0)' })),
      ]),
      transition(':leave', [
        animate('300ms ease-in', style({ opacity: 0, transform: 'translateX(50px)' })),
      ]),
    ]),
  ]
})
export class ColorAccordionComponent {
  @Input() monthlySalesData: any;
  @Output() mapClicked = new EventEmitter<any>();
  isExpanded = false;

  // Managing expanded rows (Branch, Circle, etc.)
  toggleExpand(type: any): void {
    type.isExpanded = !type.isExpanded;
  }

  // Control which columns are expanded
  columnGroupsExpanded: { [key: number]: boolean } = {};

  shouldDisplayColumn(index: number): boolean {
    if (index === 0 || index === 5 || index === 10) {
      return true;
    }
    if (index >= 1 && index <= 4) {
      return this.columnGroupsExpanded[0] === true;
    }
    if (index >= 6 && index <= 9) {
      return this.columnGroupsExpanded[5] === true;
    }
    if (index >= 11 && index <= 14) {
      return this.columnGroupsExpanded[10] === true;
    }
    return true;
  }

  handleHeaderClick(index: number): void {
    if (index === 0 || index === 5 || index === 10) {
      this.columnGroupsExpanded[index] = !this.columnGroupsExpanded[index];
    }
  }

  // Optional helper for better control
  getColumnState(index: number): string {
    return this.shouldDisplayColumn(index) ? 'visible' : 'hidden';
  }

}
