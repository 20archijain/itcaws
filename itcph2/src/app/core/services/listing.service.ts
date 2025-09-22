import { Injectable } from '@angular/core';
import { ActivatedRoute } from '@angular/router';
import { equals, findIndex, pick } from 'ramda';
import { Subject } from 'rxjs';

import { ChekboxOutput, ListingActions } from '../interfaces/helpers.interface';
import { SessionUtil } from '../utils/session.util';
import { SessionModule, SessionModuleObject } from '../interfaces/common.interface';
import { URL_PARAMS_KEYS, USER_ACTION } from 'src/app/app.constants';

@Injectable()
export class ListingService {
  private refreshListingData = new Subject<void>();

  constructor(private route: ActivatedRoute) {
  }

  getModuleActions() {
    let actions: ListingActions[] = [];
    const allModulesList: SessionModuleObject = JSON.parse(SessionUtil.getItem('modules'));
    let modc = '';
    let pmodc = '';

    const routeSnapshot = this.route.snapshot;
    if (routeSnapshot.children[0] && routeSnapshot.children[0].children[0] &&
      routeSnapshot.children[0].children[0].children[0]) {
      modc = routeSnapshot.children[0].children[0].children[0].paramMap.get(URL_PARAMS_KEYS.modc);
      pmodc = routeSnapshot.children[0].children[0].children[0].paramMap.get(URL_PARAMS_KEYS.pmodc);
    }

    const currentModule: SessionModule = pmodc === '0' ? allModulesList[modc] : allModulesList[pmodc].submodules[modc];

    if (currentModule) {
      actions = currentModule.actions.map(action => {
        switch (action.toUpperCase()) {
          case USER_ACTION[USER_ACTION.EDIT]:
            return {
              allowMulti: false, allowSingle: true, icon: 'icon-edit link', id: USER_ACTION.EDIT,
              name: 'Edit', title: 'icon.edit',
            } as ListingActions;
          case USER_ACTION[USER_ACTION.DEL]:
            return {
              allowMulti: true, allowSingle: true, icon: 'icon-trash link', id: USER_ACTION.DEL,
              name: 'Delete', title: 'icon.delete',
            } as ListingActions;
          case USER_ACTION[USER_ACTION.UNLK]:
            return {
              allowMulti: true, allowSingle: true, icon: 'icon-unlock link', id: USER_ACTION.UNLK,
              name: 'Unlock', title: 'icon.unlock'
            } as ListingActions;
          case USER_ACTION[USER_ACTION.MAP]:
            return {
              allowMulti: false, allowSingle: true, icon: 'icon-map-pin me-1 link', id: USER_ACTION.MAP,
              name: 'Map', title: 'icon.map',
            } as ListingActions;
          case USER_ACTION[USER_ACTION.DWN_IMG]:
            return {
              allowMulti: true, allowSingle: false, icon: 'download', id: USER_ACTION.DWN_IMG,
              name: 'Download Image', title: 'icon.downloadImage'
            } as ListingActions;
          case USER_ACTION[USER_ACTION.DEL_IMG]:
            return {
              allowMulti: false, allowSingle: false, icon: 'trash', id: USER_ACTION.DEL_IMG,
              name: 'Delete Image', title: 'icon.deleteImage'
            } as ListingActions;
          case USER_ACTION[USER_ACTION.RESTORE]:
            return {
              allowMulti: true, allowSingle: true, icon: 'icon-repeat link', id: USER_ACTION.RESTORE,
              name: 'Restore', title: 'icon.restore', multiActionBtnColorClass: 'btn-success',
            } as ListingActions;
        }
      });
    }

    return actions;
  }

  isMapAllowed() {
    let isMapAllowed = false;
    const actions: ListingActions[] = this.getModuleActions();
    if (actions && actions.length) {
      const map = actions.find(action => action.id === USER_ACTION.MAP);
      isMapAllowed = map ? true : false;
    }

    return isMapAllowed;
  }

  isDeleteAllowed() {
    let isDeleteAllowed = false;
    const actions: ListingActions[] = this.getModuleActions();
    if (actions && actions.length) {
      const isDelete = actions.find(action => action.id === USER_ACTION.DEL);
      isDeleteAllowed = isDelete ? true : false;
    }

    return isDeleteAllowed;
  }

  isDwnImageAllowed() {
    let isDwnImageAllowed = false;
    const actions: ListingActions[] = this.getModuleActions();
    if (actions && actions.length) {
      const isDwnImage = actions.find(action => action.id === USER_ACTION.DWN_IMG);
      isDwnImageAllowed = isDwnImage ? true : false;
    }

    return isDwnImageAllowed;
  }

  selectRecords(tableData: any[], selectedRecords: any[], selectAll: boolean,
    isAllSelected: boolean, checkKey: string | string[], currentValue: any): ChekboxOutput {
    let key = '';
    if (!selectedRecords) {
      selectedRecords = [];
    }

    // user clicked on select all checkbox from the table header
    if (selectAll) {
      isAllSelected = !isAllSelected;
      if (isAllSelected) {
        tableData.forEach(list => {
          if (Array.isArray(checkKey)) {
            key = checkKey.length ? pick(checkKey, list) : list;
          } else {
            key = checkKey ? list[checkKey] : list;
          }
          const itemIndex = findIndex(val => equals(val, key))(selectedRecords);
          if (itemIndex === -1) {
            selectedRecords.push(key);
          }
        });

        return { isAllSelected, selectedRecords };
      } else {
        return { isAllSelected, selectedRecords: [] };
      }
    } else {
      if (Array.isArray(checkKey)) {
        key = checkKey.length ? pick(checkKey, currentValue) : currentValue;
      } else {
        key = checkKey ? currentValue[checkKey] : currentValue;
      }
      const itemIndex = findIndex(val => equals(val, key))(selectedRecords);
      if (itemIndex === -1) {
        selectedRecords.push(key);

        if (selectedRecords.length === tableData.length) {
          isAllSelected = true;
        }
      } else {
        selectedRecords.splice(itemIndex, 1);

        if (isAllSelected) {
          isAllSelected = false;
        }
      }

      return { isAllSelected, selectedRecords };
    }
  }

  // check/uncheck clicked record
  isChecked(data: any, selectedRecords: any[], checkKey: string | string[]) {
    let isChecked = false;
    if (selectedRecords && selectedRecords.length) {
      const value = this.getCheckedKeyValue(data, checkKey);
      const isItemFound = findIndex(val => equals(val, value))(selectedRecords);
      isChecked = isItemFound > -1;
    }

    return isChecked;
  }

  // get the record id based on which check/uncheck is done
  getCheckedKeyValue(data: any, checkKey: string | string[]) {
    let value = '';
    if (Array.isArray(checkKey)) {
      value = checkKey.length ? pick(checkKey, data) : data;
    } else {
      value = checkKey ? data[checkKey] : data;
    }

    return value;
  }

  isFileSelected(files: File[], file: File) {
    if (files && files.length) {
      for (const sFile of files) {
        if ((sFile.name + sFile.type + sFile.size) === (file.name + file.type + file.size)) {
          return true;
        }
      }
    }

    return false;
  }

  isFileTypeValid(file: File, accept: string) {
    const acceptableTypes = accept ? accept.split(',') : [];
    if (acceptableTypes && acceptableTypes.length) {
      for (const type of acceptableTypes) {
        const acceptable = this.isWildcard(type) ? this.getTypeClass(file.type) === this.getTypeClass(type)
          : file.type === type || this.getFileExtension(file) === type;

        if (acceptable) {
          return true;
        }
      }
    }

    return false;
  }

  isWildcard(fileType: string) {
    return fileType && fileType.indexOf('*') !== -1;
  }

  isImage(file: File) {
    return /^image\//.test(file.type);
  }

  getFileExtension(file: File) {
    return '.' + file.name.split('.').pop();
  }

  getTypeClass(fileType: string) {
    return fileType && fileType.substring(0, fileType.indexOf('/'));
  }

  onRefreshListing() {
    return this.refreshListingData.asObservable();
  }

  refreshListing() {
    this.refreshListingData.next();
  }
}
