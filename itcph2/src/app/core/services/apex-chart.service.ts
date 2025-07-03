import { Injectable } from '@angular/core';
import { Subject } from 'rxjs';

@Injectable()
export class ApexChartService {
  private _changeTimeRange = new Subject<void>();
  private _changeSeriesData = new Subject<void>();

  get changeTimeRange() {
    return this._changeTimeRange.asObservable();
  }

  get changeSeriesData() {
    return this._changeSeriesData.asObservable();
  }

  eventChangeTimeRange() {
    this._changeTimeRange.next();
  }

  eventChangeSeriesData() {
    this._changeSeriesData.next();
  }
}
