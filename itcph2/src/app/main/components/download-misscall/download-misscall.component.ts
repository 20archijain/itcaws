
import { Component, OnDestroy, OnInit } from '@angular/core';
import { UntypedFormBuilder, UntypedFormGroup } from '@angular/forms';
import { finalize } from 'rxjs/operators';
import { Subscription } from 'rxjs';

import { REQUEST_STATUS, STATIC_MODULES } from 'src/app/app.constants';
import { DownloadReports, DropdownList, GetDownloadFileDetails } from 'src/app/core/interfaces/http-response.interface';
import { FormService } from 'src/app/core/services/form.service';
import { LoaderService } from 'src/app/core/services/loader.service';
import { Functions } from 'src/app/core/utils/functions.list';
import { COMMON_VALIDATORS } from 'src/app/core/validators/validations.list';
import { environment } from 'src/environments/environment';

@Component({
  templateUrl: './download-misscall.component.html',
})
export class DownloadMisscallComponent implements OnDestroy, OnInit {
  private subscription: Subscription[] = [];
  form: UntypedFormGroup;
  dataBaseListOptions: DropdownList[] = [];
  projectOptions: DropdownList[] = [];
  searchValue: any;
  header: string[] = [];
  body: string[] = [];
  url = environment.viewProjectsUrl;

  errorMessages = {
    dateRange: COMMON_VALIDATORS.messages.requiredOnly('Date Range'),
    database: COMMON_VALIDATORS.messages.requiredOnly('Database'),
    project: COMMON_VALIDATORS.messages.requiredOnly('Project'),
  };
isExportBtnDisabled: boolean;
hideSearchbar: any;

  constructor(
    protected formService: FormService,
    private fb: UntypedFormBuilder,
    protected loaderService: LoaderService) {
  }

  ngOnInit() {
    this.form = this.fb.group({
      dateRange: this.fb.group({
        from: [''],
        to: ['']
      }),
      database: [null, COMMON_VALIDATORS.validators.requiredOnly],
      project: [null, COMMON_VALIDATORS.validators.requiredOnly],
    });

    this.getInitialData();
  }

  getInitialData() {
    this.loaderService.startLoader();
    this.subscription.push(
      this.formService
        .getData<DownloadReports>(null)
        .pipe(finalize(() => this.loaderService.stopLoader()))
        .subscribe((resp) => {
          if (resp && resp.status === REQUEST_STATUS.SUCCESS) {
            this.dataBaseListOptions = resp.data.dataBaseList;
            this.header = resp.data.viewHeader;
            this.body = resp.data.viewBody;
          }
        })
    );
  }

  getProject() {
    this.subscription.push(
      this.formService.customActionCall<DownloadReports>(STATIC_MODULES.custom.getProjectsList, { database: this.form.get('database').value }, null, null)
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

  ngOnDestroy() {
    this.subscription.forEach((sub) => sub.unsubscribe());
  }

  clearFilters() {
    this.form.reset();
    this.projectOptions = [];
    this.getInitialData();
  }

  download() {
    if (this.form.valid) {
      this.loaderService.startLoader();
      this.subscription.push(
        this.formService.customActionCall<GetDownloadFileDetails>(STATIC_MODULES.custom.getDownloadData, this.form.getRawValue(),
          null, environment.downloadExcelUrl)
          .pipe(
            finalize(() => {
              this.loaderService.stopLoader();
            })
          )
          .subscribe(resp => {
            if (resp && resp.status === REQUEST_STATUS.SUCCESS) {
              Functions.downloadFile(resp.data.filePath, resp.data.fileName);
            }
          })
      );
    }
  }
}
