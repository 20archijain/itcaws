import { Component, OnInit } from '@angular/core';
import { UntypedFormBuilder, UntypedFormGroup } from '@angular/forms';
import { Subscription } from 'rxjs';
import { finalize } from 'rxjs/operators';

import { DropdownList, GetAddTeamDataResponse } from 'src/app/core/interfaces/http-response.interface';
import { REQUEST_STATUS, STATIC_MODULES } from 'src/app/app.constants';
import { COMMON_VALIDATORS } from 'src/app/core/validators/validations.list';
import { FormService } from 'src/app/core/services/form.service';
import { LoaderService } from 'src/app/core/services/loader.service';
import { environment } from 'src/environments/environment';

@Component({
  templateUrl: './system-offline.component.html'
})
export class SystemOfflineComponent implements OnInit {
  private subscription: Subscription[] = [];
  private endError = '';
  form: UntypedFormGroup;
  projectOptions: DropdownList[] = [];
  teamOptions: DropdownList[] = [];
  errorMessages = {
    project: COMMON_VALIDATORS.messages.requiredOnly('Project Name'),
    team: COMMON_VALIDATORS.messages.requiredOnly('Team Name'),
  };

  constructor(private fb: UntypedFormBuilder, private formService: FormService,
    private loaderService: LoaderService,
  ) { }

  ngOnInit() {
    this.form = this.fb.group({
      project: [null, COMMON_VALIDATORS.validators.requiredOnly],
      team: [null, COMMON_VALIDATORS.validators.requiredOnly],
    });

    this.loaderService.startLoader();
    this.subscription.push(
      this.formService.getData<GetAddTeamDataResponse>(environment.getTeamDataUrl)
        .pipe(
          finalize(() => this.loaderService.stopLoader())
        )
        .subscribe(resp => {
          if (resp && resp.status === REQUEST_STATUS.SUCCESS) {
            this.projectOptions = resp.data.projectList;
          }
        })
    );
  }

  getTeams() {
    this.teamValue = null;
    this.loaderService.startLoader();
    this.subscription.push(
      this.formService.customActionCall<GetAddTeamDataResponse>(STATIC_MODULES.custom.getTeamsList, { project: this.form.get('project').value },
        null, environment.getTeamDataUrl)
        .pipe(finalize(() => this.loaderService.stopLoader()))
        .subscribe(resp => {
          if (resp && resp.status === REQUEST_STATUS.SUCCESS) {
            this.teamOptions = resp.data.teamList;
          }
        })
    );
  }

  deleteTeam() {
    if (this.form.valid) {
      this.loaderService.startLoader();
      this.subscription.push(
        this.formService.customActionCall<GetAddTeamDataResponse>(STATIC_MODULES.listing.deleteData, this.form.getRawValue(),
          null, environment.getTeamDataUrl)
          .pipe(finalize(() => this.loaderService.stopLoader()))
          .subscribe(resp => {
            if (resp && resp.status === REQUEST_STATUS.SUCCESS) {
              this.getTeams();
            }
          })
      );
    }
  }

  trigger() {
    window.open('https://radardashboard.com/uproots/mobile_services/cronjob/store_offline_dropdown_options_new_setups.php', '_blank', 'noopener,noreferrer');
  }

  set teamValue(value: string) {
    this.teamOptions = [];
    this.form.get('team').setValue(value);
  }

}
