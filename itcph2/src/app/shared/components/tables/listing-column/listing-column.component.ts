import { Component, Input } from '@angular/core';

@Component({
  selector: 'app-listing-column',
  templateUrl: './listing-column.component.html',
  standalone: false,
})
export class ListingColumnComponent {
  @Input() header!: string;
  @Input() content!: string;
  @Input() isSkeleton = false;
}
