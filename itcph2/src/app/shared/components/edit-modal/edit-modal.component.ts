import {
  AfterViewInit, Component, EventEmitter, Input, OnDestroy,
  OnInit, Output, ViewChild
} from '@angular/core';
import { UntypedFormBuilder, UntypedFormControl, UntypedFormGroup } from '@angular/forms';
import { Subscription } from 'rxjs';
import { finalize } from 'rxjs/operators';

import { CONTROL_CONFIG, EDIT_MODAL_ONCHANGE, REQUEST_STATUS } from 'src/app/app.constants';
import { FormService } from 'src/app/core/services/form.service';
import { EditModalService } from 'src/app/core/services/edit-modal.service';
import { EditConfig, EditModalOutput, FileUploadEvent } from 'src/app/core/interfaces/helpers.interface';
import { ModalComponent } from '../modal/modal.component';
import { HttpRequestResponse } from 'src/app/core/interfaces/common.interface';
import { LoaderService } from 'src/app/core/services/loader.service';
import { environment } from 'src/environments/environment';

@Component({
  selector: 'app-edit-modal',
  templateUrl: './edit-modal.component.html'
})
export class EditModalComponent implements AfterViewInit, OnInit, OnDestroy {
  @ViewChild('editModal', { static: false }) private editModal: ModalComponent;
  @Output() private onEdit = new EventEmitter<EditModalOutput>();
  private subscription: Subscription[] = [];
  private logo: File = null;
  @Input() modalSize = 'modal-xl';
  @Input() editLabel = '';
  @Input() url = '';
  @Input() editConfig: EditConfig[] = [];
  show = false;
  typeConfig = CONTROL_CONFIG;
  group: UntypedFormGroup;
  defaultImage: string = null;
  editResponse: HttpRequestResponse = null;
  list: any[] = [];
  isFormLoaded = false;
  isDisabled = false;

  constructor(private fb: UntypedFormBuilder, private editModalService: EditModalService,
    private formService: FormService, private loaderService: LoaderService) {
  }

  ngOnInit() {
    this.group = this.fb.group({
    });
  }

  ngAfterViewInit() {
    this.subscription.push(
      this.editModalService.modal()
        .subscribe(editData => {
          this.show = editData.show;

          // Show Edit modal
          if (this.show) {
            this.isFormLoaded = false;
            this.editResponse = null;

            this.editConfig.forEach((config, i) => {
              // if image to be display
              if (config.type === CONTROL_CONFIG.IMG_BOX) {
                this.defaultImage = editData.data[config.controlName] && editData.data[config.controlName][0]
                  ? editData.data[config.controlName][0].small : '';
                this.group.addControl(config.controlName,
                  new UntypedFormControl('', config.validators));
              } else {
                this.group.addControl(config.controlName,
                  new UntypedFormControl(editData.data[config.controlName], config.validators));

                if (config.type === CONTROL_CONFIG.SELECT_BOX) {
                  // this will be used for dropdown options
                  this.list[i] = {
                    options: config.options,
                  };

                  if (config.onDropdownChange) {
                    this.onChange(i, config.controlName, config.onDropdownChange, config.isPreDefinedCall);
                  }
                }
              }
            });

            this.isFormLoaded = true;
            // show edit modal
            this.editModal.show();
          } else {
            // hide edit modal
            this.closeModal();
          }
        })
    );
  }

  ngOnDestroy() {
    this.subscription.forEach(sub => sub.unsubscribe());
  }

  reset() {
    this.isFormLoaded = false;
    this.group = null;
    this.group = this.fb.group({
    });
  }

  onClick(isConfirm: boolean) {
    if (isConfirm) {
      if (!this.isDisabled && this.group.valid) {
        this.isDisabled = true;
        this.editResponse = null;
        this.saveChanges();
      }
    } else {
      this.onEdit.emit({ status: isConfirm });
      this.closeModal();
    }
  }

  saveChanges() {
    this.subscription.push(
      this.formService.editData(this.group, this.logo, this.url)
        .pipe(
          finalize(() => this.isDisabled = false)
        )
        .subscribe(resp => {
          if (resp && resp.status === REQUEST_STATUS.SUCCESS) {
            this.onEdit.emit({ status: true });
            this.closeModal();
          } else {
            this.editResponse = resp;
          }
        })
    );
  }

  closeModal() {
    if (this.editModal && this.editModal.visible) {
      this.editModal.hide();
      this.reset();
    }
  }

  onImgSelect($event: FileUploadEvent) {
    this.logo = $event && $event.files && $event.files[0];
  }

  onChange(index: number, controlName: string, onDropdownChange: number, isPreDefinedCall: boolean) {
    if (onDropdownChange) {
      // Call one of the methods defined here
      if (isPreDefinedCall) {
        // get projects
        if (this.editConfig[index].onDropdownChange === EDIT_MODAL_ONCHANGE.PROJECTS) {
          // clear project, city and team options
          if (this.list[index + 1]) {
            this.list[index + 1]['options'] = [];
            if (this.editConfig[index + 1] && this.group.get(this.editConfig[index + 1].controlName)) {
              this.group.get(this.editConfig[index + 1].controlName).setValue([]);
            }
          }
          if (this.list[index + 2]) {
            this.list[index + 2]['options'] = [];
            if (this.editConfig[index + 2] && this.group.get(this.editConfig[index + 2].controlName)) {
              this.group.get(this.editConfig[index + 2].controlName).setValue([]);
            }
          }
          if (this.list[index + 3]) {
            this.list[index + 3]['options'] = [];
            if (this.editConfig[index + 3] && this.group.get(this.editConfig[index + 3].controlName)) {
              this.group.get(this.editConfig[index + 3].controlName).setValue([]);
            }
          }

          if (this.group.get(controlName) && this.group.get(controlName).value &&
            ((this.editConfig[index].multiple &&
              this.group.get(controlName).value.length) || !this.editConfig[index].multiple)) {
            // setTimeout is used to prevent ExpressionChangedAfterItHasBeenCheckedError warning
            this.loaderService.startLoader();
            setTimeout(() => {
              this.subscription.push(
                this.formService.getProjects(environment.getUserDataUrl, this.group.get(controlName).value)
                  .pipe(
                    finalize(() => this.loaderService.stopLoader())
                  )
                  .subscribe(resp => {
                    if (resp && resp.status === REQUEST_STATUS.SUCCESS) {
                      this.list[index + 1]['options'] = resp.data.projectList;
                    }
                  })
              );
            }, 0);
          }
        } else if (this.editConfig[index].onDropdownChange === EDIT_MODAL_ONCHANGE.CITY) {
          // clear city and team options
          if (this.list[index + 1]) {
            this.list[index + 1]['options'] = [];
            if (this.editConfig[index + 1] && this.group.get(this.editConfig[index + 1].controlName)) {
              this.group.get(this.editConfig[index + 1].controlName).setValue([]);
            }
          }
          if (this.list[index + 2]) {
            this.list[index + 2]['options'] = [];
            if (this.editConfig[index + 2] && this.group.get(this.editConfig[index + 2].controlName)) {
              this.group.get(this.editConfig[index + 2].controlName).setValue([]);
            }
          }

          if (this.group.get(controlName) && this.group.get(controlName).value &&
            ((this.editConfig[index].multiple &&
              this.group.get(controlName).value.length) || !this.editConfig[index].multiple)) {
            // setTimeout is used to prevent ExpressionChangedAfterItHasBeenCheckedError warning
            this.loaderService.startLoader();
            setTimeout(() => {
              this.subscription.push(
                this.formService.getCity(environment.getUserDataUrl,
                  {
                    client: this.group.get(this.editConfig[index - 1].controlName).value,
                    project: this.group.get(controlName).value
                  })
                  .pipe(
                    finalize(() => this.loaderService.stopLoader())
                  )
                  .subscribe(resp => {
                    if (resp && resp.status === REQUEST_STATUS.SUCCESS) {
                      this.list[index + 1]['options'] = resp.data.cityList;
                    }
                  })
              );
            }, 0);
          }
        } else if (this.editConfig[index].onDropdownChange === EDIT_MODAL_ONCHANGE.TEAMS) {
          // clear team options
          if (this.list[index + 1]) {
            this.list[index + 1]['options'] = [];
            if (this.editConfig[index + 1] && this.group.get(this.editConfig[index + 1].controlName)) {
              this.group.get(this.editConfig[index + 1].controlName).setValue([]);
            }
          }

          if (this.group.get(controlName) && this.group.get(controlName).value &&
            ((this.editConfig[index].multiple &&
              this.group.get(controlName).value.length) || !this.editConfig[index].multiple)) {
            // setTimeout is used to prevent ExpressionChangedAfterItHasBeenCheckedError warning
            this.loaderService.startLoader();
            setTimeout(() => {
              this.subscription.push(
                this.formService.getTeams(environment.getUserDataUrl,
                  {
                    city: this.group.get(controlName).value,
                    client: this.group.get(this.editConfig[index - 2].controlName).value,
                    project: this.group.get(this.editConfig[index - 1].controlName).value,
                  })
                  .pipe(
                    finalize(() => this.loaderService.stopLoader())
                  )
                  .subscribe(resp => {
                    if (resp && resp.status === REQUEST_STATUS.SUCCESS) {
                      this.list[index + 1]['options'] = resp.data.teamList;
                    }
                  })
              );
            }, 0);
          }
        }
      } else {
        this.editModalService.dropdownValueChange(onDropdownChange);
      }
    }
  }
}
