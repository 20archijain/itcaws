import { Component, OnDestroy, OnInit } from '@angular/core';
import { UntypedFormBuilder, UntypedFormGroup } from '@angular/forms';
import { Subscription } from 'rxjs';
import { finalize } from 'rxjs/operators';

import { FormService } from 'src/app/core/services/form.service';
import { DropdownList, GetAddTeamDataResponse, GetDownloadFileDetails } from 'src/app/core/interfaces/http-response.interface';
import { LoaderService } from 'src/app/core/services/loader.service';
import { CONTROL_CONFIG, REQUEST_STATUS, STATIC_MODULES } from 'src/app/app.constants';
import { Functions } from 'src/app/core/utils/functions.list';
import { environment } from 'src/environments/environment';
import { EditConfig } from 'src/app/core/interfaces/helpers.interface';
import { COMMON_VALIDATORS } from 'src/app/core/validators/validations.list';

@Component({
  templateUrl: './MasterData.component.html',
  standalone: false,
})
export class MasterDataListingComponent implements OnInit, OnDestroy {
  private subscription: Subscription[] = [];
  header: string[] = [];
  body: string[] = [];
  isSelectable = false;
  sortOptions: DropdownList[] = [];
  branchOptions: DropdownList[] = [];
  wdOptions: DropdownList[] = [];
  dsTypeOptions: DropdownList[] = [];
  teamOptions: DropdownList[] = [];
  focusTypeOptions: DropdownList[] = [];
  editConfig: EditConfig[] = [];
  form!: UntypedFormGroup;
  isDisabled = false;
  showDownloadDataBtn = false;
  downloadDataBtnTitle = false;
  url = environment.getActiveVariantsDataUrl;

  constructor(private formService: FormService, private fb: UntypedFormBuilder, private loaderService: LoaderService) { }

  ngOnInit() {
    this.form = this.fb.group({
      branch: [],
      dsType: [],
    });

    this.getInitialData();
  }

  ngOnDestroy() {
    this.subscription.forEach(sub => sub.unsubscribe());
  }


  getInitialData() {
    this.loaderService.startLoader();
    this.subscription.push(
      this.formService.getData<GetAddTeamDataResponse>(this.url, this.form.getRawValue())
        .pipe(
          finalize(() => this.loaderService.stopLoader())
        )
        .subscribe(resp => {
          if (resp && resp.status === REQUEST_STATUS.SUCCESS && resp.data) {
            this.sortOptions = resp.data.sortOptions ?? [];
            this.branchOptions = resp.data.branchList ?? [];
            this.dsTypeOptions = resp.data.dsTypeList ?? [];
            this.header = resp.data.viewHeader ?? [];
            this.body = resp.data.viewBody ?? [];
            this.isSelectable = resp.data.isSelectable ?? false;

            this.editConfig = [
              {
                controlName: 'recId', label: '', type: CONTROL_CONFIG.REC_ID,
              },
              {
                controlName: 'focusBrand', errorMessages: COMMON_VALIDATORS.messages.requiredOnly('Chnage Focus Brand'),
                label: 'app.reporting.activeUSers.changeFocus', type: CONTROL_CONFIG.SELECT_BOX, required: true,
                validators: COMMON_VALIDATORS.validators.requiredOnly, options: resp.data.focusTypeList,
              },
            ];
          }
        })
    );
  }

  download() {
    if (!this.isDisabled && this.form.valid) {
      this.isDisabled = true;
      this.loaderService.startLoader();
      this.subscription.push(
        this.formService.customActionCall<GetDownloadFileDetails>(STATIC_MODULES.custom.getDownloadData, this.form.getRawValue(), null, environment.downloadExcelUrl)
          .pipe(
            finalize(() => {
              this.isDisabled = false;
              this.loaderService.stopLoader();
            })
          )
          .subscribe(resp => {
            if (resp && resp.status === REQUEST_STATUS.SUCCESS && resp.data) {
              Functions.downloadFile(resp.data.filePath, resp.data.fileName);
            }
          })
      );
    }
  }

  clearForm() {
    // this.getInitialData();
    this.form.reset();
  }

}
