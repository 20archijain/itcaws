import { Component, OnInit } from '@angular/core';
import { UntypedFormBuilder, UntypedFormGroup } from '@angular/forms';

@Component({
    selector: 'app-navbar-search',
    templateUrl: './navbar-search.component.html',
    standalone: false
})
export class NavbarSearchComponent implements OnInit {
  group: UntypedFormGroup;

  constructor(private fb: UntypedFormBuilder) { }

  ngOnInit() {
    this.group = this.fb.group({
      search: [''],
    });
  }

  search() {
    // search
  }
}
