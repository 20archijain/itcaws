import { Component, EventEmitter, Input, OnInit, Output, TemplateRef } from '@angular/core';
import { UntypedFormBuilder, UntypedFormGroup } from '@angular/forms';

import { LISTING } from 'src/app/app.constants';
import { ListingActions, ListingBulkActionOutput } from 'src/app/core/interfaces/helpers.interface';
import { DropdownList } from 'src/app/core/interfaces/http-response.interface';

@Component({
  selector: 'app-listing-searchbar',
  templateUrl: './listing-searchbar.component.html',
  standalone: false,
})
export class ListingSearchbarComponent implements OnInit {
  @Output() private onAction = new EventEmitter<ListingBulkActionOutput>();
  limitOptions = LISTING.display;
  @Input() group!: UntypedFormGroup;
  @Input() searchTemplate?: TemplateRef<any>;
  @Input() searchGroup!: UntypedFormGroup;
  @Input() showPagination = true;
  @Input() sortOptions: DropdownList[] = [];
  @Input() actions: ListingActions[] = [];
  @Input() selectedRecords: any[] = [];

  constructor(private fb: UntypedFormBuilder) {
  }

  ngOnInit() {
    if (!this.group) {
      this.group = this.fb.group({
        action: [''],
        limit: [''],
        page: [1],
        sort: ['']
      });
    }
  }

  onSebarAction(type: number) {
    this.onAction.emit({ type });
  }
}
