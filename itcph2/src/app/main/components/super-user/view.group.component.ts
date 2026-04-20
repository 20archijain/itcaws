import { Component, OnDestroy, OnInit } from '@angular/core';
import { UntypedFormBuilder, UntypedFormGroup } from '@angular/forms';
import { Subscription } from 'rxjs';
import { finalize } from 'rxjs/operators';

import { FormService } from 'src/app/core/services/form.service';
import { REQUEST_STATUS } from 'src/app/app.constants';
import { environment } from 'src/environments/environment';
import { DropdownList, GroupDataResponse } from 'src/app/core/interfaces/http-response.interface';
import { LoaderService } from 'src/app/core/services/loader.service';

@Component({
    templateUrl: './view.group.component.html',
    standalone: false
})
export class ViewGroupComponent implements OnDestroy, OnInit {
  private subscription: Subscription[] = [];
  header: string[] = [];
  body: string[] = [];
  sortOptions: DropdownList[];
  group: UntypedFormGroup;
  url = environment.viewGroupUrl;

  constructor(private formService: FormService, private fb: UntypedFormBuilder, private loaderService: LoaderService) { }

  ngOnInit() {
    this.group = this.fb.group({
      name: [''],
    });

    this.header = [
      'app.user.group.listing.header.groupId',
      'app.user.group.form.groupName',
      'app.user.group.listing.header.modules'
    ];
    this.body = ['id', 'name', 'modules'];
    this.getInitialData();
  }

  ngOnDestroy() {
    this.subscription.forEach(sub => sub.unsubscribe());
  }

  getInitialData() {
    this.loaderService.startLoader();
    this.subscription.push(
      this.formService.getData<GroupDataResponse>(environment.getGroupDataUrl)
        .pipe(
          finalize(() => this.loaderService.stopLoader())
        )
        .subscribe(resp => {
          if (resp && resp.status === REQUEST_STATUS.SUCCESS) {
            this.sortOptions = resp.data.sortOptions;
          }
        })
    );
  }
}
