import { Component, EventEmitter, Input, OnChanges, OnDestroy, OnInit, Output, SimpleChanges, TemplateRef } from '@angular/core';
import { UntypedFormControl, UntypedFormGroup, ValidatorFn } from '@angular/forms';
import { DomSanitizer } from '@angular/platform-browser';
import { Subscription } from 'rxjs';

import { FormService } from 'src/app/core/services/form.service';
import { CUSTOM_VALIDATOR_KEYS, UPLOAD_FILES } from 'src/app/app.constants';
import { ListingService } from 'src/app/core/services/listing.service';
import { FileUploadEvent } from 'src/app/core/interfaces/helpers.interface';
import { FILE_SIZE_VALIDATOR, FILE_TYPE_VALIDATOR } from 'src/app/core/validators/common.validator';
import { FormControlErrorMessage } from 'src/app/core/interfaces/common.interface';

@Component({
    selector: 'app-file-upload',
    templateUrl: './file-upload.component.html',
    standalone: false
})
export class FileUploadComponent implements OnChanges, OnDestroy, OnInit {
  private subscription: Subscription[] = [];
  @Input() private validators = null;
  @Output() private onSelect = new EventEmitter<FileUploadEvent>();
  @Input() private maxFileSize = UPLOAD_FILES.maxFileSizeInBytes;
  @Input() private addMaxFileSizeValidation = true;
  @Input() accept = UPLOAD_FILES.fileTypes.imageOnly.mimeTypes.join(',');
  @Input() private addFileTypeValidation = true;
  @Input() private errorMessages: FormControlErrorMessage[] = [];
  @Input() label = '';
  @Input() labelTemplate: TemplateRef<any> = null;
  @Input() showPreview = true;
  @Input() showDummyImageIfNoImageSelected = true;
  @Input() chooseLabel = 'button.choose';
  @Input() changeLabel = 'button.change';
  @Input() cancelLabel = 'button.remove';
  @Input() multiple = false;
  @Input() controlName = 'file';
  @Input() hide = false;
  @Input() group: UntypedFormGroup;
  @Input() smallSizeThumbnail = false;
  @Input() defaultSelected: string = null;
  @Input() groupClassName = '';
  @Input() labelClassName = 'form-label';
  @Input() sizeClass = 'col-sm-8 col-xl-9';
  @Input() thumbnailClass = '';
  @Input() fileControlClass = 'form-control';
  @Input() dummyImage = UPLOAD_FILES.dummyImage;
  errorMessage = '';
  isInvalid = false;
  files: any[] = [];
  selectedFile = null;
  oldFileSizeValidator: ValidatorFn;
  oldFileTypeValidator: ValidatorFn;

  constructor(private formService: FormService, private sanitizer: DomSanitizer,
    private listingService: ListingService) { }

  ngOnChanges(changes: SimpleChanges) {
    // remove control if hide is true
    if (changes && changes.hide && this.group) {
      if (this.group.get(this.controlName) && changes.hide.currentValue) {
        this.group.removeControl(this.controlName);
      }
    } else {
      if (!this.group.get(this.controlName)) {
        this.group.addControl(this.controlName,
          new UntypedFormControl(null, this.validators));
      }
    }

    if (changes && changes.defaultSelected && changes.defaultSelected.currentValue
      && this.files.length === 0) {
      this.setSelctedImage(changes.defaultSelected.currentValue);
    }
  }

  ngOnInit() {
    if (this.inputField) {
      this.subscription.push(
        this.inputField.statusChanges
          .subscribe(() => {
            this.checkError();
          })
      );
    }
  }

  ngOnDestroy() {
    this.subscription.forEach(sub => sub.unsubscribe());
  }

  setSelctedImage(image: string) {
    this.files.push(image);
    this.defaultSelected = image;
  }

  onChange($event: DragEvent | Event) {
    this.defaultSelected = null;
    if (!this.multiple) {
      this.files = [];
    }

    const files: FileList = ($event as DragEvent)?.dataTransfer ?
      ($event as DragEvent).dataTransfer?.files : ($event.target as HTMLInputElement)?.files;
    if (files && files.length) {
      for (let i = 0; i < files.length; i++) {
        const file = files[i];

        if (!this.listingService.isFileSelected(this.files, file)) {
          if (this.validate(file)) {
            if (this.listingService.isImage(file)) {
              this.selectedFile = this.sanitizer.bypassSecurityTrustUrl((window.URL.createObjectURL(file)));
            } else {
              this.selectedFile = null;
            }

            this.files.push(file);
          }
        }
      }
    }

    this.checkError();
    this.onSelect.emit({ originalEvent: $event, files, invalid: this.isInvalid });
  }

  validate(file: File): boolean {
    // Add max file size validation
    if (this.addMaxFileSizeValidation && this.maxFileSize) {
      this.removeFileSizeValidator();
      this.oldFileSizeValidator = FILE_SIZE_VALIDATOR(this.maxFileSize, file.size);
      this.inputField.addValidators(this.oldFileSizeValidator);
      this.inputField.updateValueAndValidity();
    }

    // Add file type validation
    if (this.addFileTypeValidation && this.accept) {
      this.removeFileTypeValidator();
      this.oldFileTypeValidator = FILE_TYPE_VALIDATOR(this.accept, file);
      this.inputField.addValidators(this.oldFileTypeValidator);
      this.inputField.updateValueAndValidity();
    }

    return !this.hasFileSizeOrFileTypeError;
  }

  hasFiles() {
    return this.files && this.files.length > 0;
  }

  isImageFile() {
    return this.files && this.listingService.isImage(this.files[0]);
  }

  removeFileSizeValidator() {
    if (this.oldFileSizeValidator) {
      this.inputField.removeValidators(this.oldFileSizeValidator);
      this.oldFileSizeValidator = null;
    }
  }

  removeFileTypeValidator() {
    if (this.oldFileTypeValidator) {
      this.inputField.removeValidators(this.oldFileTypeValidator);
      this.oldFileTypeValidator = null;
    }
  }

  get hasFileSizeOrFileTypeError() {
    return this.inputField.hasError(CUSTOM_VALIDATOR_KEYS.FILE_SIZE) || this.inputField.hasError(CUSTOM_VALIDATOR_KEYS.FILE_TYPE)
  }

  get inputField() {
    return this.group.get(this.controlName);
  }

  get errors() {
    return this.group.get(this.controlName) && this.group.get(this.controlName).errors;
  }

  get isTouched() {
    return this.inputField && (this.inputField.touched || this.inputField.dirty);
  }

  clear() {
    this.files = [];
    this.isInvalid = false;
    this.selectedFile = null;
    this.defaultSelected = null;
    this.inputField.setValue(null);
    // Remove File size and File type error on clear
    this.removeFileSizeValidator();
    this.removeFileTypeValidator();
    this.inputField.updateValueAndValidity();
    this.checkError();
    this.onSelect.emit({ originalEvent: null, files: null, invalid: this.isInvalid });
  }

  checkError() {
    // This is used when clicking "Choose" Btn to mark file control as touched
    this.inputField.markAsTouched();
    const resp = this.formService.getValidationError(this.inputField, this.errorMessages);
    this.isInvalid = resp.isInvalid;
    this.errorMessage = resp.errorMessage;
  }
}
