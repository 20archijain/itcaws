import { Injectable } from '@angular/core';
import { Subject } from 'rxjs';

import { ModalData } from '../interfaces/common.interface';

@Injectable()
export class LocationOnMapModalService {
  private showModal = new Subject<ModalData>();

  modal() {
    return this.showModal.asObservable();
  }

  show(data: any) {
    this.showModal.next({ show: true, data });
  }

  hide() {
    this.showModal.next({ show: false });
  }
}
