import { Injectable, OnDestroy } from '@angular/core';
import { Observable, Subscription, timer } from 'rxjs';
import { AbstractControl, UntypedFormGroup } from '@angular/forms';
import { TranslateService } from '@ngx-translate/core';
import { switchMap } from 'rxjs/operators';

import { environment } from 'src/environments/environment';
import { AUTOREFRESH, STATIC_MODULES } from 'src/app/app.constants';
import { CustomFile, ValidationError } from '../interfaces/helpers.interface';
import { FormControlErrorMessage, HttpRequestParams, HttpRequestParamsModuleInfo, HttpRequestResponse } from '../interfaces/common.interface';
import { HttpService } from './http.service';

@Injectable()
export class FormService implements OnDestroy {
  private subscription: Subscription[] = [];

  constructor(private translate: TranslateService, private httpService: HttpService) {
  }

  private getFormData(form: UntypedFormGroup): any {
    const keys = Object.keys(form.controls);
    const data: Record<string, any> = {};
    if (keys && keys.length) {
      keys.forEach(key => {
        data[key] = form.controls[key].value;
      });
    }

    return data;
  }

  private uploadData<T>({ action, moduleName, staticModule }: HttpRequestParams,
    formData: UntypedFormGroup | any, file: File | File[] | CustomFile[] | null = null, url = environment.apiUrl) {
    let data: any;
    if (formData instanceof UntypedFormGroup) {
      data = this.getFormData(formData);
    } else {
      data = formData;
    }

    return this.httpService
      .upload<T>(url, file, { action, data, moduleName, staticModule });
  }

  private getDropdownListOnChange<T>(action: string, id: number | number[] | string | string[] | undefined, url = environment.apiUrl) {
    return this.uploadData<T>({ action }, { id }, null, url);
  }

  ngOnDestroy() {
    this.subscription.forEach(sub => sub.unsubscribe());
  }

  getValidationError(field: AbstractControl | null | undefined, errorMessages: FormControlErrorMessage[] = []): ValidationError {
    const errResp: ValidationError = {
      errorMessage: '',
      isInvalid: false
    };

    if (field && field.invalid) {
      errResp.isInvalid = true;
      let hasError = false;
      if (errorMessages && errorMessages.length) {
        errorMessages.forEach(validator => {
          if (!hasError && field.hasError(validator.errorName)) {
            hasError = true;
            this.subscription.push(
              this.translate.get(
                validator.message,
                {
                  fileSize: validator.fileSize,
                  fileType: validator.fileType,
                  maxLength: validator.maxLength,
                  maxValue: validator.maxValue,
                  minLength: validator.minLength,
                  minValue: validator.minValue,
                  name: validator.name
                })
                .subscribe(translatedMsg => {
                  errResp.errorMessage = translatedMsg;
                })
            );
          }
        });
      }
    }

    return errResp;
  }

  addData<T = any>(form: UntypedFormGroup | any, file: File | File[] | CustomFile[] | null = null, url = environment.apiUrl,
    { moduleName, staticModule }: HttpRequestParamsModuleInfo = {}): Observable<HttpRequestResponse<T>> {
    return this.uploadData<T>({ action: STATIC_MODULES.listing.addData, moduleName, staticModule }, form, file, url);
  }

  getData<T = any>(url = environment.apiUrl, data: any = null,
    { moduleName, staticModule }: HttpRequestParamsModuleInfo = {}): Observable<HttpRequestResponse<T>> {
    return this.httpService
      .request<T>(url, { action: STATIC_MODULES.listing.getData, data, moduleName, staticModule });
  }

  getList<T = any>(url = environment.apiUrl, data: any = null,
    { moduleName, staticModule }: HttpRequestParamsModuleInfo = {}, autoRefresh = false): Observable<HttpRequestResponse<T>> {

    if (autoRefresh && AUTOREFRESH.enable === 1) {
      const autoListingTimer = timer(0, AUTOREFRESH.duration);

      return autoListingTimer.pipe(
        switchMap(() => {
          return this.httpService
            .request<T>(url, { action: STATIC_MODULES.listing.getList, data, moduleName, staticModule });
        })
      );
    } else {
      return this.httpService
        .request<T>(url, { action: STATIC_MODULES.listing.getList, data, moduleName, staticModule });
    }
  }

  getRoute<T = any>(url = environment.apiUrl, data: any = null,
    { moduleName, staticModule }: HttpRequestParamsModuleInfo = {}, autoRefresh = false): Observable<HttpRequestResponse<T>> {

    if (autoRefresh && AUTOREFRESH.enable === 1) {
      const autoListingTimer = timer(0, AUTOREFRESH.duration);

      return autoListingTimer.pipe(
        switchMap(() => {
          return this.httpService
            .request<T>(url, { action: STATIC_MODULES.listing.getRoute, data, moduleName, staticModule });
        })
      );
    } else {
      return this.httpService
        .request<T>(url, { action: STATIC_MODULES.listing.getRoute, data, moduleName, staticModule });
    }
  }

  deleteData<T = any>(url: string | undefined, id: any,
    { moduleName, staticModule }: HttpRequestParamsModuleInfo = {}): Observable<HttpRequestResponse<T>> {
    return this.httpService
      .request<T>(url, { action: STATIC_MODULES.listing.deleteData, data: { id }, moduleName, staticModule });
  }

  // deleting data with form details
  deleteWithFormData<T = any>(url: string, formData: any,
    { moduleName, staticModule }: HttpRequestParamsModuleInfo = {}): Observable<HttpRequestResponse<T>> {
    return this.httpService
      .request<T>(url, { action: STATIC_MODULES.listing.deleteWithFormData, data: formData, moduleName, staticModule });
  }

  deleteImage<T = any>(url: string, data: any,
    { moduleName, staticModule }: HttpRequestParamsModuleInfo = {}): Observable<HttpRequestResponse<T>> {
    return this.httpService
      .request<T>(url, { action: STATIC_MODULES.listing.deleteImage, data, moduleName, staticModule });
  }

  editData<T = any>(form: UntypedFormGroup, file: File | null | undefined, url = environment.apiUrl,
    { moduleName, staticModule }: HttpRequestParamsModuleInfo = {}): Observable<HttpRequestResponse<T>> {
    return this.uploadData<T>({ action: STATIC_MODULES.listing.editData, moduleName, staticModule }, form, file, url);
  }

  customActionCall<T = any>(action: string, formData: UntypedFormGroup | any, file: File | null = null, url = environment.apiUrl,
    { moduleName, staticModule }: HttpRequestParamsModuleInfo = {}): Observable<HttpRequestResponse<T>> {
    return this.uploadData<T>({ action, moduleName, staticModule }, formData, file, url);
  }

  getProjects<T = any>(url: string, clientId?: number | number[] | string | string[]) {
    return this.getDropdownListOnChange<T>(STATIC_MODULES.custom.getProjectsList, clientId, url);
  }

  getCity<T = any>(url: string, id?: any) {
    return this.getDropdownListOnChange<T>(STATIC_MODULES.custom.getCityList, id, url);
  }

  getTeams<T = any>(url: string, id?: any) {
    return this.getDropdownListOnChange<T>(STATIC_MODULES.custom.getTeamsList, id, url);
  }

  restoreData<T = any>(url: string | undefined, id: any,
    { moduleName, staticModule }: HttpRequestParamsModuleInfo = {}): Observable<HttpRequestResponse<T>> {
    return this.httpService
      .request<T>(url, { action: STATIC_MODULES.listing.restoreData, data: { id }, moduleName, staticModule });
  }
}
