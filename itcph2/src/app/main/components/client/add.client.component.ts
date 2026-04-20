import { Component, OnDestroy, OnInit, ViewChild } from '@angular/core';
import { UntypedFormBuilder, UntypedFormGroup } from '@angular/forms';
import { Subscription } from 'rxjs';
import { finalize } from 'rxjs/operators';

import { FormService } from 'src/app/core/services/form.service';
import { REQUEST_STATUS } from 'src/app/app.constants';
import { environment } from 'src/environments/environment';
import { CLIENT_VALIDATORS, COMMON_VALIDATORS } from 'src/app/core/validators/validations.list';
import { CanGoBackGuard } from 'src/app/core/guards/can-go-back-guard.service';
import { FileUploadComponent } from 'src/app/shared/controls/file-upload/file-upload.component';
import { FileUploadEvent } from 'src/app/core/interfaces/helpers.interface';
import { CUSTOM_VALIDATION_LENGTH } from 'src/app/core/validators/validators.list';

@Component({
    templateUrl: './add.client.component.html',
    standalone: false
})
export class AddClientComponent implements OnInit, OnDestroy {
  @ViewChild(FileUploadComponent, { static: false }) private fileUploadComponent: FileUploadComponent;
  private subscription: Subscription[] = [];
  private logo: File = null;
  form: UntypedFormGroup;
  errorMessages = {
    desc: CLIENT_VALIDATORS.messages.desc,
    logo: COMMON_VALIDATORS.messages.file('Logo'),
    name: COMMON_VALIDATORS.messages.name('Client Name')
  };
  isDisabled = false;

  constructor(private fb: UntypedFormBuilder, private formService: FormService,
    private canGoBackGuard: CanGoBackGuard) { }

  ngOnInit() {
    this.form = this.fb.group({
      desc: ['', CLIENT_VALIDATORS.validators.desc],
      logo: ['', COMMON_VALIDATORS.validators.file()],
      name: ['', COMMON_VALIDATORS.validators.name(CUSTOM_VALIDATION_LENGTH.CLIENT_NAME_MAXLENGTH)],
    });

    this.canGoBackGuard.markAsPristine();

    this.subscription.push(
      this.form.valueChanges
        .subscribe(() => this.canGoBackGuard.markAsDirty())
    );
  }

  ngOnDestroy() {
    this.subscription.forEach(sub => sub.unsubscribe());
  }

  onSelect($event: FileUploadEvent) {
    this.logo = $event && $event.files && $event.files[0];
  }

  addClient() {
    if (this.form.valid && !this.isDisabled) {
      this.isDisabled = true;
      this.subscription.push(
        this.formService.addData<string>(this.form, this.logo, environment.addClientUrl)
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
  }

  clearForm() {
    this.logo = null;
    this.form.reset();
    this.fileUploadComponent.clear();
  }
}
