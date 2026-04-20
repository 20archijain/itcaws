import { Component, ElementRef, EventEmitter, HostListener, Input, OnDestroy, OnInit, Output, Renderer2, ViewChild } from '@angular/core';
import { Subscription } from 'rxjs';
import { UntypedFormGroup } from '@angular/forms';
import { finalize } from 'rxjs/operators';

import { environment } from 'src/environments/environment';
import { GalleryService } from 'src/app/core/services/gallery.service';
import {
  CustomGalleryConfig, GalleryImagesList, GalleryPreviewStyle, GalleryPreviewStyleConfig
} from 'src/app/core/interfaces/common.interface';
import { ListingService } from 'src/app/core/services/listing.service';
import { ListingActions } from 'src/app/core/interfaces/helpers.interface';
import { ConfirmationModalService } from 'src/app/core/services/confirmation-modal.service';
import { FormService } from 'src/app/core/services/form.service';
import { REQUEST_STATUS, USER_ACTION } from 'src/app/app.constants';
import { LoaderService } from 'src/app/core/services/loader.service';
import { Functions } from 'src/app/core/utils/functions.list';

@Component({
    selector: 'app-custom-gallery',
    templateUrl: './custom-gallery.component.html',
    standalone: false
})
export class CustomGalleryComponent implements OnDestroy, OnInit {
  @ViewChild('previewContainer', { static: false }) private previewContainer: ElementRef;
  @Output() private onDelete = new EventEmitter();
  private subscription: Subscription[] = [];
  private actionData: number = null;
  private previewStyleParams: GalleryPreviewStyleConfig = {};
  private defaultConfig: CustomGalleryConfig = {
    closePreviewOnEsc: true,
    previewActions: {
      maxZoom: 4,
      minZoom: 1,
      rotate: true,
      zoom: true,
    },
    previewImageDwnKey: 'downloadFileName',
    previewImageKey: 'big',
    previewTextKey: 'description',
    showPreviewOnClick: true,
    showPreviewText: true,
    showThumbnailActions: true,
    showThumbnailText: true,
    thumbnailAltTextKey: 'thumbnailAltText',
    thumbnailContentKey: 'thumbnailContent',
    thumbnailImageKey: 'small',
    thumbnailMaxHeight: '80px',
    thumbnailMaxWidth: '80px',
    thumbnailSizeClass: 'wid-90 hei-90',
    thumbnailTitleKey: 'thumbnailTitle',
  };
  private previewCurrentStyle = {
    rotate: 0,
    zoom: 0,
  };
  @Input() private url = environment.apiUrl;
  @Input() private cgConfig: CustomGalleryConfig = null;
  @Input() images: GalleryImagesList[] = [];
  @Input() actions: ListingActions[] = [];
  @Input() thumbnailNoWrap = false;
  @Input() showAsCard = false;
  @Input() showAsUserCard = false;
  @Input() thumbnailClass = '';
  @Input() group: UntypedFormGroup = null;
  @Input() limitWidth = false;
  config: CustomGalleryConfig = null;
  isPreviewOpen = false;
  currentImageIndex = 0;
  currentImage: GalleryImagesList = null;
  previewStyle: GalleryPreviewStyle = null;
  userAction = USER_ACTION;
  hasDeletePermission = null;

  constructor(private galleryService: GalleryService, private listingService: ListingService,
    private confirmationModalService: ConfirmationModalService, private formService: FormService,
    private loaderService: LoaderService, private renderer2: Renderer2) {
  }

  ngOnInit() {
    if (this.images && !Array.isArray(this.images)) {
      this.images = [this.images];
    }

    const customConfig = this.cgConfig ? this.cgConfig : {};
    this.config = { ...this.defaultConfig, ...customConfig };

    this.actions = this.listingService.getModuleActions();
    this.hasDeletePermission = this.actions.find(action => action.id === this.userAction.DEL_IMG);

    // on delete confirm
    this.subscription.push(
      this.confirmationModalService.modal()
        .subscribe(resp => {
          if (!resp.goBackGuard && !resp.show) {
            if (resp.data && this.actionData !== null) {
              this.onDeleteConfirm();
            }
          }
        })
    );
  }

  ngOnDestroy() {
    this.subscription.forEach(sub => sub.unsubscribe());
  }

  @HostListener('window:keyup', ['$event'])
  onKeyUp($event: KeyboardEvent) {
    // if preview window is open
    if (this.config && this.config.closePreviewOnEsc && this.isPreviewOpen) {
      // if escape key was pressed, close the preview window
      if ($event.keyCode === 27 || $event.which === 27) {
        this.togglePreview(false);
      }

      // show previous image on click of left arrow
      if ($event.keyCode === 37 || $event.which === 37) {
        this.showPrevious();
      }

      // show next image on click of right arrow
      if ($event.keyCode === 39 || $event.which === 39) {
        this.showNext();
      }
    }
  }

  togglePreview(status: boolean, imageIndex?: number) {
    this.isPreviewOpen = status;
    this.galleryService.toggleGalleryPreview(this.isPreviewOpen);

    if (this.isPreviewOpen) {
      this.setCurrentImage(imageIndex);
      setTimeout(() => {
        const previewList = document.getElementsByClassName('cg-preview-thumbnails')[0];
        const height = previewList.clientHeight + 54 + 10;
        this.renderer2.setStyle(this.previewContainer.nativeElement, 'height', `calc(100% - ${height}px)`);
      }, 0);
    } else {
      this.currentImage = null;
    }
  }

  onPreviewActionClick(actionId: number, actionName?: string, currentImageIndex?: number) {
    switch (actionId) {
      case 0: {
        const currentStyle = { ...this.previewStyleParams };
        switch (actionName) {
          case 'rotateLeft':
            if (this.previewCurrentStyle.rotate === -270) {
              this.previewCurrentStyle.rotate = 0;
            } else {
              this.previewCurrentStyle.rotate -= 90;
            }
            currentStyle['rotate'] = `rotate(${this.previewCurrentStyle.rotate}deg)`;
            break;
          case 'rotateRight':
            if (this.previewCurrentStyle.rotate === 270) {
              this.previewCurrentStyle.rotate = 0;
            } else {
              this.previewCurrentStyle.rotate += 90;
            }
            currentStyle['rotate'] = `rotate(${this.previewCurrentStyle.rotate}deg)`;
            break;
          case 'zoomOut':
            if (this.previewCurrentStyle.zoom >= this.config.previewActions.minZoom) {
              this.previewCurrentStyle.zoom--;
            }
            currentStyle['scale'] = `scale(1.${this.previewCurrentStyle.zoom})`;
            break;
          case 'zoomIn':
            if (this.previewCurrentStyle.zoom < this.config.previewActions.maxZoom) {
              this.previewCurrentStyle.zoom++;
            }
            currentStyle['scale'] = `scale(1.${this.previewCurrentStyle.zoom})`;
            break;
        }

        this.previewStyleParams = { ...currentStyle };

        this.previewStyle = {
          transform:
            `${currentStyle['scale'] ? currentStyle['scale'] : ''} ${currentStyle['rotate'] ? currentStyle['rotate'] : ''}`
        };
        break;
      }
      case this.userAction.DEL_IMG: {
        this.confirmationModalService.show('listing.action.deleteImage');
        this.actionData = (currentImageIndex !== null && currentImageIndex !== undefined) ? currentImageIndex : this.currentImageIndex;
        break;
      }
    }
  }

  isThumbnailActive(imageIndex: number) {
    return imageIndex === this.currentImageIndex;
  }

  showNext() {
    const totalImages = this.totalImages;
    let nextImageIndex = this.currentImageIndex + 1;

    if (nextImageIndex >= totalImages) {
      nextImageIndex = 0;
    }

    this.setCurrentImage(nextImageIndex);
  }

  showPrevious() {
    const totalImages = this.totalImages;
    let previousImageIndex = this.currentImageIndex - 1;

    if (previousImageIndex < 0) {
      previousImageIndex = totalImages - 1;
    }

    this.setCurrentImage(previousImageIndex);
  }

  setCurrentImage(imageIndex: number) {
    this.currentImageIndex = imageIndex;
    this.currentImage = this.images[this.currentImageIndex];
    this.previewCurrentStyle = {
      rotate: 0,
      zoom: 0
    };
    this.previewStyle = null;
  }

  onDeleteConfirm() {
    this.loaderService.startLoader();

    const payload = {
      data: this.group ? this.group.getRawValue() : null,
      id: this.actionData !== null ? this.images[this.actionData].id : null,
    };

    this.subscription.push(
      this.formService.deleteImage<string>(this.url, payload)
        .pipe(
          finalize(() => this.loaderService.stopLoader())
        )
        .subscribe(resp => {
          if (resp && resp.status === REQUEST_STATUS.SUCCESS) {
            this.images.splice(this.actionData, 1);
            this.togglePreview(false);
            this.onDelete.emit();
            this.actionData = null;
          }
        })
    );
  }

  downloadImage() {
    if (this.currentImage && this.currentImage[this.config.previewImageKey]) {
      Functions.downloadFile(this.currentImage[this.config.previewImageKey],
        this.currentImage[this.config.previewImageDwnKey]);
    }
  }

  get totalImages() {
    return this.images.length;
  }

  openGoogleMaps(latitude: number, longitude: number): void {
    window.open(`https://www.google.com/maps?q=${latitude},${longitude}`);
  }
}
