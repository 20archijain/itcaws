import { Component, EventEmitter, Input, OnChanges, OnDestroy, OnInit, Output, SimpleChanges } from '@angular/core';
import { UntypedFormBuilder, UntypedFormControl, UntypedFormGroup } from '@angular/forms';
import { Subscription } from 'rxjs';

import { ChekboxOutput } from 'src/app/core/interfaces/helpers.interface';
import { ListingService } from 'src/app/core/services/listing.service';
import { FormService } from 'src/app/core/services/form.service';

@Component({
    selector: 'app-checkbox',
    templateUrl: './checkbox.component.html',
    standalone: false
})
export class CheckboxComponent implements OnChanges, OnDestroy, OnInit {
  private subscription: Subscription[] = [];
  @Input() private validators = null;
  @Input() private defaultValue = null;
  @Output() private emitSelectedRecords = new EventEmitter<ChekboxOutput>();
  @Input() private selectAll = false;
  @Input() private checkKey = '';
  @Input() private value = null;
  @Input() private selectedRecords = [];
  @Input() private tableData: any[] = [];
  @Input() protected errorMessages: string[] = [];
  @Input() showValidationState = true;
  @Input() isChecked = false;
  @Input() hide = false;
  @Input() group: UntypedFormGroup = null;
  @Input() controlName = 'checkbox';
  @Input() label = '';
  @Input() noMargin = false;
  @Input() id = 0;
  errorMessage = '';
  isInvalid = false;

  constructor(private formService: FormService, private listingService: ListingService, private fb: UntypedFormBuilder) { }

  ngOnChanges(changes: SimpleChanges) {
    // remove control if hide is true
    if (changes && changes.hide && this.group) {
      if (this.group.get(this.controlName) && changes.hide.currentValue) {
        this.group.removeControl(this.controlName);
        // clear selected options
        this.emitSelectedRecords.emit({ isAllSelected: false, selectedRecords: [] });
      } else {
        if (!this.group.get(this.controlName) && !changes.hide.currentValue) {
          this.group.addControl(this.controlName,
            new UntypedFormControl(this.defaultValue, this.validators));
        }
        this.resetError(null);
      }
    }

    // disable the control
    if (changes && changes.disable && this.group.get(this.controlName)) {
      if (changes.disable.currentValue) {
        this.group.get(this.controlName).disable();
      } else {
        this.group.get(this.controlName).enable();
      }
    }
  }

  ngOnInit() {
    if (!this.group) {
      this.group = this.fb.group({
        [this.controlName]: [],
      });
    }

    this.subscription.push(
      this.inputField.statusChanges
        .subscribe(() => {
          this.resetError(null);
        })
    );
  }

  ngOnDestroy() {
    this.subscription.forEach(sub => sub.unsubscribe());
  }

  get inputField() {
    return this.group && this.group.get(this.controlName);
  }

  get isTouched() {
    return this.inputField && (this.inputField.touched || this.inputField.dirty);
  }

  onChange($event: MouseEvent) {
    if ($event) {
      $event.stopPropagation();
    }

    const list = this.listingService.selectRecords(this.tableData, this.selectedRecords,
      this.selectAll, this.isChecked, this.checkKey, this.value);

    this.emitSelectedRecords.emit(list);
  }

  resetError($event: Event) {
    if ($event && $event.stopPropagation) {
      $event.stopPropagation();
    }
    this.checkError();
  }

  checkError() {
    const resp = this.formService.getValidationError(this.inputField, this.errorMessages);
    this.isInvalid = resp.isInvalid;
    this.errorMessage = resp.errorMessage;
  }
}
