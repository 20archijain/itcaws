import { Component, OnDestroy, OnInit } from '@angular/core';
import { UntypedFormBuilder, UntypedFormGroup, Validators } from '@angular/forms';
import { Subscription } from 'rxjs';
import { finalize } from 'rxjs/operators';

import { environment } from 'src/environments/environment';
import { FormService } from 'src/app/core/services/form.service';
import { DropdownList, GetAddTeamDataResponse, teams } from 'src/app/core/interfaces/http-response.interface';
import { EditConfig } from 'src/app/core/interfaces/helpers.interface';
import { LoaderService } from 'src/app/core/services/loader.service';
import { REQUEST_STATUS, STATIC_MODULES, CONTROL_CONFIG } from 'src/app/app.constants';
import { COMMON_VALIDATORS } from 'src/app/core/validators/validations.list';

@Component({
    templateUrl: './view-route.component.html',
    standalone: false
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
  routeOptions: DropdownList[] = [];
  statusOptions: DropdownList[] = [];
  editRouteOptions: DropdownList[] = [];
  form: UntypedFormGroup;
  editRouteForm: UntypedFormGroup;
  url = environment.viewTeamsUrl;
  isExportBtnDisabled = false;
  deleteCondition: [string, number] = ['dstatus', 0];
  showEditModal = false;

  constructor(
    private formService: FormService,
    private fb: UntypedFormBuilder,
    private loaderService: LoaderService
  ) { }

  ngOnInit() {
    this.form = this.fb.group({
      branch: [],
      team: [],
      routeName: [],
      recIds: [],
      phoneNumber: [],
    });

    this.editRouteForm = this.fb.group({
      teamName: [null, Validators.required],
      routeName: [null, Validators.required],
      newRouteName: ['', [Validators.required, Validators.minLength(3)]],
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
            this.routeOptions = resp.data.routeList;
            this.statusOptions = resp.data.statusList;
            this.header = resp.data.viewHeader;
            this.body = resp.data.viewBody;

            this.editConfig = [
              {
                controlName: 'id', label: '', type: CONTROL_CONFIG.REC_ID,
              },
              {
                controlName: 'team',
                label: 'Team Name',
                errorMessages: COMMON_VALIDATORS.messages.dropdown('Team Name'),
                validators: COMMON_VALIDATORS.validators.dropdown,
                required: true,
                options: resp.data.teamList,
                type: CONTROL_CONFIG.SELECT_BOX,
              },
              {
                controlName: 'routeName',
                label: 'Route Name',
                errorMessages: COMMON_VALIDATORS.messages.dropdown('Route Name'),
                validators: COMMON_VALIDATORS.validators.dropdown,
                required: true,
                options: resp.data.routeList,
                type: CONTROL_CONFIG.SELECT_BOX,
              },
              {
                controlName: 'team', errorMessages: COMMON_VALIDATORS.messages.requiredOnly('Team'), hide: true,
                label: 'app.team.view.team', multiple: true, options: resp.data.teamList,
                required: true, type: CONTROL_CONFIG.SELECT_BOX, validators: COMMON_VALIDATORS.validators.requiredOnly,
              },
              {
                controlName: 'team', errorMessages: COMMON_VALIDATORS.messages.requiredOnly('Route'), hide: true,
                label: 'app.team.view.team', multiple: true, options: resp.data.routeList,
                required: true, type: CONTROL_CONFIG.SELECT_BOX, validators: COMMON_VALIDATORS.validators.requiredOnly,
              },
            ];
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

  getRouteNames() {
    this.form.get("routeName").setValue("");
    this.routeOptions = [];
    this.loaderService.startLoader();
    this.subscription.push(
      this.formService
        .customActionCall<teams>(
          STATIC_MODULES.custom.getRouteList,
          { team: this.form.get("team").value },
          null,
          this.url
        )
        .pipe(finalize(() => this.loaderService.stopLoader()))
        .subscribe((resp) => {
          if (resp && resp.status === REQUEST_STATUS.SUCCESS) {
            this.routeOptions = resp.data.routeList;
          }
        })
    );
  }

  getRouteNamesForModal() {
    this.editRouteForm.get("routeName").setValue(null);
    this.editRouteOptions = [];
    const teamValue = this.editRouteForm.get("teamName").value;

    if (!teamValue) {
      return;
    }

    this.loaderService.startLoader();
    this.subscription.push(
      this.formService
        .customActionCall<teams>(
          STATIC_MODULES.custom.getRouteList,
          { team: teamValue },
          null,
          this.url
        )
        .pipe(finalize(() => this.loaderService.stopLoader()))
        .subscribe((resp) => {
          if (resp && resp.status === REQUEST_STATUS.SUCCESS) {
            this.editRouteOptions = resp.data.routeList;
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

  openEditRouteModal() {
    this.showEditModal = true;
    this.editRouteForm.reset();
    this.editRouteOptions = [];
  }

  closeEditRouteModal() {
    this.showEditModal = false;
    this.editRouteForm.reset();
    this.editRouteOptions = [];
  }

  submitEditRoute() {
    if (this.editRouteForm.valid) {
      const formData = this.editRouteForm.value;

      // Add your API call here to update the route
      // console.log('Submitted data:', formData);

      // Example API call:
      this.loaderService.startLoader();
      this.subscription.push(
        this.formService.customActionCall(
          STATIC_MODULES.listing.editData,
          formData,
          null,
          this.url
        )
        .pipe(finalize(() => this.loaderService.stopLoader()))
        .subscribe((resp) => {
          if (resp && resp.status === REQUEST_STATUS.SUCCESS) {
            this.closeEditRouteModal();
            this.getInitialData();
          }
        })
      );

      this.closeEditRouteModal();
    } else {
      Object.keys(this.editRouteForm.controls).forEach(key => {
        this.editRouteForm.get(key).markAsTouched();
      });
    }
  }
}
