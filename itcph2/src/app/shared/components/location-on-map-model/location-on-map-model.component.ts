import { Component, Input, OnDestroy, OnInit, ViewChild } from '@angular/core';
import { Subscription } from 'rxjs';

import { LocationOnMapModalService } from 'src/app/core/services/location-on-map-modal.service';
import { MapConfig } from 'src/app/core/interfaces/common.interface';
import { LISTING, MAP_DEFAULTS } from 'src/app/app.constants';
import { ModalComponent } from '../modal/modal.component';

@Component({
    selector: 'app-location-on-map-modal',
    templateUrl: './location-on-map-modal.component.html',
    standalone: false
})
export class LocationOnMapModalComponent implements OnInit, OnDestroy {
  @ViewChild('locationOnMapModal', { static: true }) private locationOnMapModal: ModalComponent;
  private subscription: Subscription[] = [];
  @Input() defaultZoom = MAP_DEFAULTS.defaultZoom;
  markers: MapConfig[] = [];

  constructor(private locationOnMapModalService: LocationOnMapModalService) { }

  ngOnInit() {
    this.subscription.push(
      this.locationOnMapModalService.modal()
        .subscribe(mapData => {
          if (mapData.show) {
            this.markers = [
              {
                latitude: mapData.data[LISTING.mapKeys.lt] ? +mapData.data[LISTING.mapKeys.lt] : 0,
                longitude: mapData.data[LISTING.mapKeys.lg] ? +mapData.data[LISTING.mapKeys.lg] : 0,
                markerUrl: mapData.data.markerUrl,
              }
            ];

            // show modal
            this.locationOnMapModal.show();
          } else {
            // close modal
            this.closeModal();
          }
        })
    );
  }

  ngOnDestroy() {
    this.subscription.forEach(sub => sub.unsubscribe());
  }

  onClick() {
    this.locationOnMapModalService.hide();
    this.closeModal();
  }

  closeModal() {
    if (this.locationOnMapModal.visible) {
      this.locationOnMapModal.hide();
    }
  }
}
