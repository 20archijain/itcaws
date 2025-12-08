import { Injectable } from '@angular/core';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import { ActivatedRoute } from '@angular/router';
import { Observable } from 'rxjs';

import { environment } from 'src/environments/environment';
import { URL_PARAMS_KEYS } from 'src/app/app.constants';
import { SessionUtil } from '../utils/session.util';
import { HttpRequestParams, HttpRequestPayload, HttpRequestResponse } from '../interfaces/common.interface';
import { CustomFile } from '../interfaces/helpers.interface';

@Injectable()
export class HttpService {

  constructor(private http: HttpClient, private route: ActivatedRoute) { }

  private createRequestPayload(params: HttpRequestParams): HttpRequestPayload {
    if (!params) { params = {} as HttpRequestParams; }
    const routeSnapshot = this.route?.snapshot;

    return {
      auth_token: SessionUtil.getItem('token') || null,
      request_info: {
        action: params.action,
        data: params.data || {},
        module: params.moduleName || {
          [URL_PARAMS_KEYS.modc]: routeSnapshot.children[0]
            && routeSnapshot.children[0].children[0].children[0].paramMap.get(URL_PARAMS_KEYS.modc),
          [URL_PARAMS_KEYS.pmodc]: routeSnapshot.children[0]
            && routeSnapshot.children[0].children[0].children[0].paramMap.get(URL_PARAMS_KEYS.pmodc)
        }
      },
      staticModule: params.staticModule || false
    } as HttpRequestPayload;
  }

  private makeRequest<T>(url: string, formData: FormData | HttpRequestPayload, headers: HttpHeaders): Observable<HttpRequestResponse<T>> {
    if (environment.production) {
      return this.http.post<HttpRequestResponse<T>>(environment.apiUrl, formData, { headers });
    }

    return this.http.get<HttpRequestResponse<T>>(url || environment.noUrlProvidedUrl);
  }

  private getHeaders(isFile = false): HttpHeaders {
    const headers = new HttpHeaders();
    headers.set('Accept', 'application/json');
    headers.set('Content-Type', 'application/json');

    if (isFile) {
      headers.delete('Content-Type');
    }

    return headers;
  }

  request<T>(url: string, params: HttpRequestParams): Observable<HttpRequestResponse<T>> {
    const requestPayload = this.createRequestPayload(params);
    const headers = this.getHeaders();

    return this.makeRequest<T>(url, requestPayload, headers);
  }

  upload<T>(url: string, files: File | File[] | CustomFile[], params: HttpRequestParams): Observable<HttpRequestResponse<T>> {
    if (files) {
      const requestPayload = this.createRequestPayload(params);
      const formData = new FormData();
      if (Array.isArray(files)) {
        files.forEach((file, index) => {
          if ((file as CustomFile)?.fileKey) {
            formData.append((file as CustomFile).fileKey, file.file, file.file.name);
          } else {
            formData.append(`file${index}`, file, file.name);
          }
        });
      } else {
        formData.append('file', files, files.name);
      }
      formData.append('data', JSON.stringify(requestPayload));
      const headers = this.getHeaders(true);

      return this.makeRequest<T>(url, formData, headers);
    }

    return this.request<T>(url, params);
  }
}
