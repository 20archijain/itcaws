import { Color, colorSets, ScaleType } from '@swimlane/ngx-charts';
import { findIndex } from 'ramda';
import * as moment from 'moment';
import { saveAs } from 'file-saver';

import { GA_ROUTE_MAPPING } from './GAMapping';
import { SessionUtil } from './session.util';
import { ControlMaxDate, CsvDataFormat } from '../interfaces/helpers.interface';
import { DATE_FORMAT, URL_PARAMS_KEYS } from 'src/app/app.constants';
import { SessionModule, SessionModuleObject, ThemeMap } from '../interfaces/common.interface';

export class Functions {
  private modulesList: any[] = [];
  private csvData = '';

  static isEmptyArray(value: any[]) {
    return value && Array.isArray(value) && value.length === 0;
  }

  static getValuesFromObjectAsArray(object: { [key: string]: any }): any[] {
    const arr = [];
    Object.keys(object).forEach(key => {
      arr.push(object[key]);
    });

    return arr;
  }

  static getValidDate(date: ControlMaxDate): string {
    if (date) {
      return `${date.year}-${date.month < 10 ? '0' + date.month : date.month}-${date.day < 10 ? '0' + date.day : date.day}`;
    }

    return '';
  }

  static getNoOfDaysBetweenDates(fromDate: ControlMaxDate | string, toDate: ControlMaxDate | string, getValidDate?: boolean): number {
    const df = getValidDate ? this.getValidDate(fromDate as ControlMaxDate) : (fromDate as string);
    const dt = getValidDate ? this.getValidDate(toDate as ControlMaxDate) : (toDate as string);

    return Math.floor((Date.parse(dt) - Date.parse(df)) / 86400000) + 1;
  }

  static getDatesArray(fromDate: ControlMaxDate, toDate: ControlMaxDate, downloadPeriod: number): Array<string[]> {
    const startDateOrg = Functions.getValidDate(fromDate);
    const endDateOrg = Functions.getValidDate(toDate);
    const period = [];

    if (downloadPeriod > 0) {
      // get no of days between dates
      const totalDays = Functions.getNoOfDaysBetweenDates(startDateOrg, endDateOrg);
      let remainingDays = totalDays;
      let startdate = startDateOrg;

      while (remainingDays > 0) {
        if (remainingDays <= downloadPeriod) {
          startdate = startdate === startDateOrg ? startdate : moment(startdate, 'YYYY-MM-DD').add(1, 'days').format('YYYY-MM-DD');
          period.push([startdate, moment(startdate, 'YYYY-MM-DD').add(remainingDays - 1, 'days').format('YYYY-MM-DD')]);
          remainingDays = 0;
        } else {
          startdate = startdate === startDateOrg ? startdate : moment(startdate, 'YYYY-MM-DD').add(1, 'days').format('YYYY-MM-DD');
          const enddate = moment(startdate, 'YYYY-MM-DD').add(downloadPeriod - 1, 'days').format('YYYY-MM-DD');
          period.push([startdate, enddate]);
          startdate = enddate;
          remainingDays -= downloadPeriod;
        }
      }
    } else {
      period.push([startDateOrg, endDateOrg]);
    }

    return period;
  }

  static currentYear(customDate?: string): number {
    const date = customDate ? new Date(customDate) : new Date();
    return date.getFullYear();
  }

  static previousMonth(customDate?: string): string {
    const date = customDate ? new Date(customDate) : new Date();
    const monthIndex = date.getMonth(); // 0 = Jan, 1 = Feb, ..., 11 = Dec
    const previousMonthIndex = (monthIndex - 1 + 12) % 12; // handle January -> December
    const monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    return monthNames[previousMonthIndex];
  }

  static currentDate(customDate?: string, firstDay?: boolean) {
    let date = new Date();
    if (customDate) {
      date = new Date(customDate);
    }

    const day = firstDay ? 1 : date.getDate();
    let month = date.getMonth();
    month = date.getMonth() + 1;
    const year = date.getFullYear();

    return { date, month, year, day };
  }

  static currentFormatedDate() {
    const date = new Date();

    const day = date.getDate();
    let month = String(date.getMonth());
    month = `0${date.getMonth() + 1}`;
    const year = date.getFullYear();

    return { date, month, year, day };
  }

  static getValidDatetimeForSorting(datetime: string) {
    if (datetime) {
      const dateAndTime = datetime.split(' ');
      const time = dateAndTime && dateAndTime[1] ? dateAndTime[1] : '';
      const dateParts = dateAndTime && dateAndTime[0] ? dateAndTime[0].split(/-|\//) : [];

      // Check if year value is first or date value is first
      if (dateParts && dateParts[0] && dateParts[0].length === 4) {
        return `${dateParts[0]}/${dateParts[1]}/${dateParts[2]} ${time}`;
      } else if (dateParts && dateParts[0] && dateParts[0].length === 2) {
        return `${dateParts[2]}/${dateParts[1]}/${dateParts[0]} ${time}`;
      }
    }

    return '';
  }

  static isValidDate(date: any): boolean {
    if (date) {
      if (date.year) {
        return moment(`${date.year}-${date.month}-${date.day}`, DATE_FORMAT.YYYYMMDD).isValid();
      } else {
        return moment(date, DATE_FORMAT.YYYYMMDD).isValid();
      }
    }

    return false;
  }

  static createCSV({ fileName, header, body }: CsvDataFormat) {
    Functions.prototype.csvData = '';
    const blob = new Blob([Functions.prototype.getCsvData(header, body)], { type: 'text/csv;charset=utf8;' });

    // creates a DOMString containing a URL representing the object given in the parameter
    const blobURL = window.URL.createObjectURL(blob);

    this.downloadFile(blobURL, `${fileName.replace(/ /g, '_')}.csv`);
  }

  static downloadFile(fileUrl: string, fileName: string) {
    if (fileUrl) {
      saveAs(fileUrl, fileName);
    }
  }

  static getHomeLocation() {
    const landing = JSON.parse(SessionUtil.getItem('landing'));
    const modules = JSON.parse(SessionUtil.getItem('modules'));

    // If User has landing page permission, send that page otherwise send first module as landing page
    if (modules) {
      if (landing && (modules[landing[URL_PARAMS_KEYS.modc]] || modules[landing[URL_PARAMS_KEYS.pmodc]])) {
        return [
          landing[URL_PARAMS_KEYS.modc],
          landing[URL_PARAMS_KEYS.pmodc]
        ];
      } else {
        const modulesKeys = Object.keys(modules);

        return [
          modules[modulesKeys[0]][URL_PARAMS_KEYS.modc],
          modules[modulesKeys[0]][URL_PARAMS_KEYS.pmodc]
        ];
      }
    }

    return null;
  }

  static printBlob(blob: Blob) {
    // creates a DOMString containing a URL representing the object given in the parameter
    const blobURL = URL.createObjectURL(blob);

    const iframe = document.createElement('iframe');
    document.body.appendChild(iframe);
    iframe.style.display = 'none';
    iframe.src = blobURL;

    iframe.onload = function () {
      setTimeout(function () {
        iframe.focus();
        iframe.contentWindow.print();
      }, 1);
    };
  }

  static getChartColorsScheme() {
    const sets = {} as ThemeMap;
    colorSets.push({
      domain: [],
      group: ScaleType.Ordinal,
      name: 'custom',
      selectable: true,
    });

    colorSets.forEach((set: Color) => {
      sets[set.name.toUpperCase()] = {
        domain: set.domain
      };
    });

    return sets;
  }

  static getAsideItems() {
    let modules: SessionModuleObject;
    if (SessionUtil.getItem('modules')) {
      modules = JSON.parse(SessionUtil.getItem('modules'));
      Functions.prototype.modulesList = Functions.prototype.getModules(modules);
    }

    return Functions.prototype.modulesList;
  }

  static getModuleInfo(route: string, modc: string, pmodc: string) {
    let index: number;
    const modules = Functions.prototype.modulesList || this.getAsideItems();
    let moduleInfo;

    // application module
    if (route === 'app') {
      // main/parent module
      if (pmodc === '0') {
        index = findIndex(mod => mod[URL_PARAMS_KEYS.modc] === modc)(modules);
        moduleInfo = index >= 0 ? modules[index] : null;
      } else {
        // sub/child modules
        const pindex = findIndex(mod => mod[URL_PARAMS_KEYS.modc] === pmodc)(modules);
        index = pindex >= 0 ? findIndex(mod => mod[URL_PARAMS_KEYS.modc] === modc)(modules[pindex].submodules) : -1;
        moduleInfo = index >= 0 ? modules[index] : null;
      }
    } else {
      // static module (auth)
      return GA_ROUTE_MAPPING[modc];
    }

    return moduleInfo;
  }

  static calculateColumnWidth(noOfColumns: number) {
    const num = +(100 / noOfColumns);

    return +num.toString().match(/^-?\d+(?:\.\d{0,2})?/)[0];
  }

  static scrollToFocusElement(element: HTMLElement, leaveSpaceOnTop = 0, subtractInputHeight = false) {
    // scrollbar start position from top of window
    const scrollBarPosition = window.pageYOffset;

    if (element) {
      const elementPosition = element.offsetTop || element.parentElement.offsetTop;
      window.scrollBy(0, elementPosition - leaveSpaceOnTop - (scrollBarPosition - (subtractInputHeight ? element.scrollHeight : 0)));
    }
  }

  private getModules(modules: SessionModuleObject) {
    const newModules = Object.keys(modules);
    const modulesList = [];

    // list of main modules
    if (!Functions.isEmptyArray(newModules)) {
      newModules.forEach((mod) => {
        modulesList.push(this.getAsideItem(modules[mod]));
      });
    }

    return modulesList;
  }

  private getAsideItem(mod: SessionModule) {
    return {
      hide: true,
      icon: mod.icon,
      isHidden: !!mod.hidden,
      modc: mod.modc,
      name: mod.name,
      pmodc: mod.pmodc,
      submodules: Functions.isEmptyArray(Object.keys(mod.submodules)) ? [] : this.getModules(mod.submodules)
    };
  }

  private getCsvData(header: string[][], body: string[][]) {
    if (header && header.length > 0) {
      this.getCsvHeader(header);
    }

    if (body && body.length > 0) {
      this.getCsvBody(body);
    }

    if (this.csvData) {
      return this.csvData;
    }

    return '';
  }

  private getCsvHeader(header: string[][]) {
    if (header && header.length > 0) {
      const headerData = header.map((data: string[]) => {
        return data.join(',');
      });

      this.csvData = `${this.csvData}${headerData.join('\r\n')}\r\n`;
    }
  }

  private getCsvBody(body: string[][]) {
    if (body && body.length > 0) {
      const bodyData = body.map((data: string[]) => {
        return data.join(',');
      });

      this.csvData = `${this.csvData}${bodyData.join('\r\n')}`;
    }
  }
}
