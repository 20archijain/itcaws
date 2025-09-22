import { Component, OnDestroy, OnInit } from '@angular/core';
import { UntypedFormBuilder, UntypedFormGroup } from '@angular/forms';
import { Subscription } from 'rxjs';
import { finalize } from 'rxjs/operators';

import { environment } from 'src/environments/environment';
import { FormService } from 'src/app/core/services/form.service';
import { DropdownList, GetAddTeamDataResponse, teams } from 'src/app/core/interfaces/http-response.interface';
import { EditConfig } from 'src/app/core/interfaces/helpers.interface';
import { LoaderService } from 'src/app/core/services/loader.service';
import { REQUEST_STATUS, STATIC_MODULES } from 'src/app/app.constants';

@Component({
  templateUrl: './view-route.component.html'
})
export class ViewRouteComponent implements OnInit, OnDestroy {
  private subscription: Subscription[] = [];
  header: string[] = [];
  isSelectable = true;
  body: string[] = [];
  editConfig: EditConfig[] = [];
  sortOptions: DropdownList[] = [];
  branchOptions: DropdownList[] = [];
  teamOptions: DropdownList[] = [];
  form: UntypedFormGroup;
  url = environment.viewTeamsUrl;
  isExportBtnDisabled = false;

  constructor(private formService: FormService, private fb: UntypedFormBuilder, private loaderService: LoaderService) { }

  ngOnInit() {
    this.form = this.fb.group({
      branch: [],
      team: [],
    });
    this.getInitialData();
  }

  ngOnDestroy() {
    this.subscription.forEach(sub => sub.unsubscribe());
  }



  getInitialData() {
    this.loaderService.startLoader();
    this.subscription.push(
      this.formService.getData<GetAddTeamDataResponse>(environment.getTeamDataUrl)
        .pipe(
          finalize(() => this.loaderService.stopLoader())
        )
        .subscribe(resp => {
          if (resp && resp.status === REQUEST_STATUS.SUCCESS) {
            this.sortOptions = resp.data.sortOptions;
            this.branchOptions = resp.data.branchList;
            this.teamOptions = resp.data.teamList;
            this.header = resp.data.viewHeader;
            this.body = resp.data.viewBody;
          }
        })
    );
  }

  getTeamNames() {
    this.form.get("team").setValue("");
    this.teamOptions = [];
    this.loaderService.startLoader();
    this.subscription.push(
      this.formService
        .customActionCall<teams>(
          STATIC_MODULES.custom.getTeamsList,
          { branch: this.form.get("branch").value },
          null,
          this.url
        )
        .pipe(finalize(() => this.loaderService.stopLoader()))
        .subscribe((resp) => {
          if (resp && resp.status === REQUEST_STATUS.SUCCESS) {
            this.teamOptions = resp.data.teamList;
          }
        })
    );
  }

  onClearFilters() {
    if (this.form) {
      this.form.reset();
    }
    this.getInitialData();
  }

}
