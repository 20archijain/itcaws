import { Injectable } from '@angular/core';
import { Subject } from 'rxjs';

import { DropdownChangeData, EditData } from '../interfaces/common.interface';

@Injectable()
export class EditModalService {
  private showModal = new Subject<EditData>();
  private dropdownChange = new Subject<DropdownChangeData>();

  modal() {
    return this.showModal.asObservable();
  }

  show(data: any) {
    this.showModal.next({ show: true, data });
  }

  hide() {
    this.showModal.next({ show: false });
  }

  onDropdownChange() {
    return this.dropdownChange.asObservable();
  }

  dropdownValueChange(type: number, data?: any) {
    this.dropdownChange.next({ type, data });
  }
}
