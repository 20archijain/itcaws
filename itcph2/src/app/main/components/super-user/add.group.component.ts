import { Component, OnDestroy, OnInit } from '@angular/core';
import { UntypedFormBuilder, UntypedFormGroup } from '@angular/forms';
import { Subscription } from 'rxjs';
import { ActivatedRoute } from '@angular/router';
import { findIndex } from 'ramda';
import { TranslateService } from '@ngx-translate/core';
import { finalize } from 'rxjs/operators';

import { FormService } from 'src/app/core/services/form.service';
import { CanGoBackGuard } from 'src/app/core/guards/can-go-back-guard.service';
import { ToastrService } from 'src/app/core/services/toastr.service';
import { GROUP_VALIDATORS } from 'src/app/core/validators/validations.list';
import { AddGroup, GroupDataResponse } from 'src/app/core/interfaces/http-response.interface';
import { environment } from 'src/environments/environment';
import { REQUEST_STATUS, URL_PARAMS_KEYS } from 'src/app/app.constants';
import { ChekboxOutput } from 'src/app/core/interfaces/helpers.interface';
import { Functions } from 'src/app/core/utils/functions.list';
import { LoaderService } from 'src/app/core/services/loader.service';

@Component({
  styleUrls: [
    './add.group.component.scss',
  ],
  templateUrl: './add.group.component.html',
  standalone: false,
})
export class AddGroupComponent implements OnDestroy, OnInit {
  private subscription: Subscription[] = [];
  private emptyListError = 'err.emptyModuleList';
  private editId: number | null = null;
  group!: UntypedFormGroup;
  moduleList: AddGroup[][] = [];
  selectedRecords: any[][] = [];
  errorMessages = {
    name: GROUP_VALIDATORS.messages.name,
  };
  checkKey = 'value';
  isEdit = false;
  isDisabled = false;

  constructor(private fb: UntypedFormBuilder, private formService: FormService,
    private canGoBackGuard: CanGoBackGuard, private toastr: ToastrService,
    private translate: TranslateService, private route: ActivatedRoute, private loaderService: LoaderService) { }

  ngOnInit() {
    this.subscription.push(
      this.translate.get(this.emptyListError)
        .subscribe((translatedMsg: string) => {
          this.emptyListError = translatedMsg;
        })
    );

    this.group = this.fb.group({
      id: [''],
      items: [],
      name: ['', GROUP_VALIDATORS.validators.name],
    });

    // edit
    this.subscription.push(
      this.route.paramMap
        .subscribe(params => {
          const id = params.get(URL_PARAMS_KEYS.id);
          if (id) {
            this.isEdit = true;
            this.editId = +id;
          }
        })
    );

    this.canGoBackGuard.markAsPristine();

    this.subscription.push(
      this.group.valueChanges
        .subscribe(() => this.canGoBackGuard.markAsDirty())
    );

    this.loaderService.startLoader();
    this.subscription.push(
      this.formService.getData<GroupDataResponse>(environment.getGroupDataUrl, { isEdit: this.isEdit, id: this.editId })
        .pipe(
          finalize(() => this.loaderService.stopLoader())
        )
        .subscribe(resp => {
          if (resp && resp.status === REQUEST_STATUS.SUCCESS && resp.data) {
            this.moduleList = resp.data.modulesList;
            this.emptyModules();

            // if edit, set record details
            if (this.isEdit) {
              const editData = resp.data?.groupData;
              this.group.get('name')?.setValue(editData?.name || '');
              this.group.get('id')?.setValue(this.editId);

              const selectedRecords: string[] = editData?.items.split(',');

              if (selectedRecords && selectedRecords.length > 0) {
                selectedRecords.forEach(moduleId => {
                  const index = findIndex((mainModule: AddGroup[]) => {
                    return mainModule.findIndex(mod => +mod.value === +moduleId) > -1;
                  })(this.moduleList);
                  if (index > -1 && this.selectedRecords[index]) {
                    this.selectedRecords[index].push(moduleId);
                  }
                });
              }
              this.group.get('items')?.setValue(this.selectedModules);
              this.canGoBackGuard.markAsPristine();
            }
          } else {
            this.moduleList = [];
          }
        })
    );
  }

  ngOnDestroy() {
    this.subscription.forEach(sub => sub.unsubscribe());
  }

  emptyModules() {
    this.moduleList.forEach((module, index) => {
      this.selectedRecords[index] = [];
      if (module && module[0]) {
        module[0]['isChecked'] = false;
      }
    });
  }

  addGroup() {
    if (!this.isDisabled && this.group.valid && !Functions.isEmptyArray(this.selectedModules)) {
      this.isDisabled = true;

      if (this.isEdit) {
        this.subscription.push(
          this.formService.editData<string>(this.group, null, environment.editGroupUrl)
            .pipe(
              finalize(() => this.isDisabled = false)
            )
            .subscribe(resp => {
              if (resp && resp.status === REQUEST_STATUS.SUCCESS) {
                this.canGoBackGuard.markAsPristine();
              }
            })
        );
      } else {
        this.subscription.push(
          this.formService.addData<string>(this.group, null, environment.addGroupUrl)
            .pipe(
              finalize(() => this.isDisabled = false)
            )
            .subscribe(resp => {
              if (resp && resp.status === REQUEST_STATUS.SUCCESS) {
                this.clearForm();
                this.canGoBackGuard.markAsPristine();
              }
            })
        );
      }
    } else {
      if (Functions.isEmptyArray(this.selectedModules)) {
        this.toastr.toastr({ type: 'error', msg: this.emptyListError });
      }
    }
  }

  clearForm() {
    this.group.reset();
    this.emptyModules();
  }

  isChecked(module: AddGroup, moduleIndex: number) {
    return this.selectedRecords[moduleIndex] ?
      this.selectedRecords[moduleIndex].indexOf((module as any)[this.checkKey]) > -1 : false;
  }

  emitSelectedRecords($event: ChekboxOutput, moduleIndex: number, checkAll = false) {
    this.selectedRecords[moduleIndex] = $event.selectedRecords;
    this.group.get('items')?.setValue(this.selectedModules);

    // If user clicked on All checkbox of any module
    if (checkAll) {
      this.moduleList[moduleIndex][0]['isChecked'] = true;
      (this.moduleList[moduleIndex] as AddGroup[]).forEach(mod => {
        // uncheck Main module if checked and any of sub module is not checked
        if (this.moduleList[moduleIndex][0]['isChecked'] && this.selectedRecords[moduleIndex].indexOf((mod as any)[this.checkKey]) === -1) {
          this.moduleList[moduleIndex][0]['isChecked'] = false;
        }
      });
    }
  }

  get selectedModules() {
    // eslint-disable-next-line prefer-spread
    return [].concat.apply([], this.selectedRecords as any);
  }
}
