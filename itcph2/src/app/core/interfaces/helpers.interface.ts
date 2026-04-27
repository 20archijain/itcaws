import { TemplateRef } from '@angular/core';
import { DropdownList } from './http-response.interface';

export interface ValidationError {
  errorMessage: string;
  isInvalid: boolean;
}

export interface PasswordConfig {
  newPass: string;
  confPass: string;
}

export interface CsvDataFormat {
  fileName: string;
  header: string[][];
  body: string[][];
}

export interface ConfirmationModalOutput {
  data: string | boolean;
  goBackGuard?: boolean;
  show: boolean;
}

export interface ControlMaxDate {
  day: number;
  month: number;
  year: number;
}

export interface FormattedDate<T = string> {
  date: Date;
  day: number;
  month: T;
  year: number;
}

export interface ChekboxOutput {
  isAllSelected: boolean;
  selectedRecords: any[];
}

export interface ListingActions {
  allowSingle: boolean;
  allowMulti?: boolean;
  icon: string;
  id: number;
  name: string;
  title?: string;
}

export interface ListingExtraButtons {
  heading?: string;
  showIconOnly?: boolean;
  icon?: string;
  title?: string;
  text?: string;
  colorClass?: string;
  btnClass?: string;
  showTemplate: number;
  columnSize: number;
  template: TemplateRef<any>;
}

export interface ListingBulkActionOutput {
  type: number;
}

export interface FileUploadEvent {
  originalEvent: DragEvent | Event | null;
  files: FileList | null | undefined;
  invalid: boolean;
}

export interface GAPageTracking {
  page_path: string;
  page_title: string;
}

export interface ListingBulkActionOutput {
  type: number;
  action?: ListingActions;
}

export interface EditModalOutput {
  status: boolean;
}

export interface InputConfig {
  controlName: string;
  errorMessages?: any[];
  required?: boolean;
  disableOption?: boolean;
  label?: string;
  options?: DropdownList[] | DropdownList<number>[];
  type: number;
  subtype?: string;
  validators?: any[];
}

export interface EditConfig extends InputConfig {
  labelKey?: string;
  valueKey?: string;
  placeholder?: string;
  multiple?: boolean;
  validators?: any[];
  onDropdownChange?: number;
  setDropdownOptionsIndex?: number;
  isPreDefinedCall?: boolean;
  hide?: boolean;
  groupBy?: string;
  onChange?: any;
}

export interface OnRadioChangeEvent {
  event: Event;
  value: number;
}

export interface CustomFile {
  fileKey: string;
  file: File;
}
