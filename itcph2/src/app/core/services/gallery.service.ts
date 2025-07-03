import { Injectable } from '@angular/core';
import { Subject } from 'rxjs';

@Injectable()
export class GalleryService {
  private isPreviewOpen = new Subject<boolean>();

  getPreviewOpenStatus() {
    return this.isPreviewOpen.asObservable();
  }

  toggleGalleryPreview(status: boolean) {
    this.isPreviewOpen.next(status);
  }
}
