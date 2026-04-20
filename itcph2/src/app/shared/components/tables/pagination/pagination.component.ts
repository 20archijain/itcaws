import { Component, EventEmitter, Input, OnChanges, OnInit, Output } from '@angular/core';
import { UntypedFormBuilder, UntypedFormGroup } from '@angular/forms';

import { LISTING } from 'src/app/app.constants';

@Component({
    selector: 'app-pagination',
    templateUrl: './pagination.component.html',
    standalone: false
})
export class PaginationComponent implements OnChanges, OnInit {
  @Input() private noOfTotalLinks = 7;
  @Output() private onPageChange = new EventEmitter<number>();
  @Input() group: UntypedFormGroup = null;
  @Input() controlName = 'page';
  @Input() totalRecords = 0;
  @Input() limit = LISTING.display[0];
  disableFirst = false;
  disableLast = false;
  start = 1;
  end = 1;
  lastPage = 1;
  pages: number[] = [];

  constructor(private fb: UntypedFormBuilder) { }

  ngOnInit() {
    if (!this.group) {
      this.group = this.fb.group({
        [this.controlName]: [1]
      });
    }
  }

  ngOnChanges() {
    this.getPagination();
  }

  getPagination() {
    let start: number;
    let end: number;
    this.lastPage = Math.ceil(this.totalRecords / this.limit);
    // no of links on each side of active page
    const noOfAdjacentLinks = Math.floor(this.noOfTotalLinks / 2);

    // no data
    if (this.lastPage === 0) {
      this.disableFirst = true;
      this.disableLast = true;
    } else if (this.lastPage === 1) {
      // single page
      this.disableFirst = true;
      this.disableLast = true;
      start = end = 1;
    } else {
      // fisrt page active
      if (this.currentPage === 1) {
        this.disableFirst = true;
        this.disableLast = false;
        start = 1;
        if ((this.lastPage - this.noOfTotalLinks) <= 0) {
          end = this.lastPage;
        } else {
          end = this.noOfTotalLinks;
        }
      } else if (this.currentPage === this.lastPage) {
        // last page active
        this.disableFirst = false;
        this.disableLast = true;
        if ((this.lastPage - this.noOfTotalLinks) <= 0) {
          start = 1;
        } else {
          start = (this.lastPage - this.noOfTotalLinks) + 1;
        }
        end = this.lastPage;
      } else {
        // other than first and last page active
        this.disableFirst = false;
        this.disableLast = false;

        if ((this.currentPage - noOfAdjacentLinks) <= 0) {
          start = 1;
          if (this.lastPage > this.noOfTotalLinks) {
            end = this.noOfTotalLinks;
          } else {
            end = this.lastPage;
          }
        } else {
          if ((this.currentPage + noOfAdjacentLinks) > this.lastPage) {
            if (this.noOfTotalLinks % 2 === 1) {
              start = this.lastPage - (2 * noOfAdjacentLinks);
              start = start === 0 ? 1 : start;
            } else {
              start = this.lastPage - (2 * noOfAdjacentLinks) + 1;
            }
            end = this.lastPage;
          } else {
            end = this.currentPage + noOfAdjacentLinks;
            if (this.noOfTotalLinks % 2 === 1) {
              start = this.currentPage - noOfAdjacentLinks;
            } else {
              start = this.currentPage - noOfAdjacentLinks + 1;
            }
          }
        }
      }
    }

    // update start and end to let user know how many records are visible
    if (this.totalRecords > 0) {
      if (this.totalRecords <= this.limit && this.currentPage > 1) {
        this.start = 1;
      } else {
        this.start = ((this.currentPage - 1) * this.limit) + 1;
      }
      if (this.totalRecords <= (this.currentPage * this.limit)) {
        this.end = this.totalRecords;
      } else {
        this.end = this.currentPage * this.limit;
      }
    } else {
      this.start = this.end = 0;
    }

    this.pages = [];
    for (let page = start; page <= end; page++) {
      this.pages.push(page);
    }
  }

  changePage(page: number, isDisableLickClicked = false, isCalledFromInside = false) {
    if (!isDisableLickClicked && this.currentPage !== page) {
      this.currentPage = page;
      this.getPagination();

      if (isCalledFromInside) {
        this.onPageChange.emit(this.currentPage);
      }
    }
  }

  get currentPage() {
    return this.group.get(this.controlName).value;
  }

  set currentPage(page) {
    this.group.get(this.controlName).setValue(page);
  }
}
