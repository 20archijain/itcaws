import { Observable } from 'rxjs';
import { Injectable } from '@angular/core';
import { HttpErrorResponse, HttpEvent, HttpHandler, HttpInterceptor, HttpRequest, HttpResponse } from '@angular/common/http';
import { catchError, map } from 'rxjs/operators';

import { REQUEST_STATUS } from 'src/app/app.constants';
import { SessionUtil } from '../utils/session.util';
import { ToastrService } from '../services/toastr.service';
import { HttpRequestResponse } from '../interfaces/common.interface';
import { SpinnerService } from '../services/spinner.service';

@Injectable()
export class HttpReqInterceptor implements HttpInterceptor {
  constructor(private toastr: ToastrService, private spinnerService: SpinnerService) { }

  intercept(req: HttpRequest<any>, next: HttpHandler): Observable<HttpEvent<any>> {
    this.spinnerService.startSpinner();
    const setHeaders: any = {};

    const authToken = SessionUtil.getItem('authToken') || null;
    if (authToken) {
      setHeaders.Authorization = `Basic ${authToken}`;
    }

    req = req.clone({
      setHeaders
    });

    return next
      .handle(req)
      .pipe(
        map(event => {
          if (event instanceof HttpResponse) {// OR if (event.type === HttpEventType.Response) {
            const body = event.body as HttpRequestResponse;
            if (body && body.status) {
              if (body.status === REQUEST_STATUS.FAILED) {
                this.toastr.toastr({ type: 'error', msg: body.message[0] });
              } else if (body.status === REQUEST_STATUS.WARNING) {
                this.toastr.toastr({ type: 'warning', msg: body.message[0] });
              } else if (!body.hidePopup) {
                this.toastr.toastr({ type: 'success', msg: body.message[0] });
              }
            }

            this.spinnerService.stopSpinner();
          }

          return event;
        }),
        catchError((err: HttpErrorResponse) => {
          let statusText = '';
          let errorMsg = '';
          if (err.error instanceof Error) {
            errorMsg = err.error.message;
            // A client-side or network error occurred
            this.toastr.toastr({ type: 'error', title: 'Some network error', msg: errorMsg });
          } else {
            statusText = err.statusText.toLowerCase() === 'unknown error' ? 'Network Error' : err.statusText;
            errorMsg = err.statusText.toLowerCase() === 'unknown error' ? 'No Internet Connection ' : err.message;
            // The backend returned an unsuccessful response code like 404, some broken file, server is down
            this.toastr.toastr({ type: 'error', title: statusText, msg: errorMsg });
          }

          this.spinnerService.stopSpinner();

          throw { status: REQUEST_STATUS.FAILED, message: [errorMsg] };
        })
      );
  }
}
