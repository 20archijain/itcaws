import { Component, Input, OnChanges, SimpleChanges, ViewChild } from '@angular/core';
import { MapInfoWindow, MapMarker } from '@angular/google-maps';

import { MapConfig } from 'src/app/core/interfaces/common.interface';
import { mapPath, MAP_DEFAULTS } from 'src/app/app.constants';

@Component({
  selector: 'app-simple-map',
  templateUrl: './simple-map.component.html'
})
export class SimpleMapComponent implements OnChanges {
  @ViewChild(MapInfoWindow, { static: false }) infoWindow: MapInfoWindow;
  @Input() markers: MapConfig[] = [];
  @Input() defaultZoom = MAP_DEFAULTS.defaultZoom;
  @Input() defaultUrl = `${mapPath}${MAP_DEFAULTS.icons.GREEN}`;
  @Input() mapTypeId = MAP_DEFAULTS.type.ROADMAP;
  @Input() useGoogleMapWrapper = true;
  @Input() useDifferentIconForFirstLastBetweenMarker = false;
  @Input() firstMarkerUrl = `${mapPath}${MAP_DEFAULTS.icons.START}`;
  @Input() betweenMarkerUrl = `${mapPath}${MAP_DEFAULTS.icons.BETWEEN}`;
  @Input() lastMarkerUrl = `${mapPath}${MAP_DEFAULTS.icons.END}`;
  center: google.maps.LatLngLiteral;
  mapOptions: google.maps.MapOptions = {
    zoomControl: true,
    scrollwheel: true,
    streetViewControl: true,
    disableDoubleClickZoom: true,
    maxZoom: MAP_DEFAULTS.maxZoom,
    minZoom: MAP_DEFAULTS.minZoom,
  };
  infoWindowContent: string;

  ngOnChanges(changes: SimpleChanges) {
    if (changes?.markers?.currentValue?.length) {
      // Reset zoom level
      this.mapOptions = { ...this.mapOptions, zoom: this.defaultZoom };

      // Reset center
      this.center = {
        lat: this.markers?.length ? this.markers[0].latitude : 0,
        lng: this.markers?.length ? this.markers[0].longitude : 0,
      };
    }
    if (changes?.mapTypeId?.currentValue) {
      this.mapOptions.mapTypeId = this.mapTypeId;
    }
  }

  getMarkerPosition(marker: MapConfig): google.maps.LatLngLiteral {
    return {
      lat: marker.latitude,
      lng: marker.longitude,
    };
  }

  getMarkerOptions(marker: MapConfig, first: boolean, last: boolean): google.maps.MarkerOptions {
    let icon: string;
    // use "markerUrl" if present
    if (marker?.markerUrl) {
      icon = marker?.markerUrl;
    } else if (this.useDifferentIconForFirstLastBetweenMarker) {
      if (first) {
        icon = this.firstMarkerUrl;
      } else if (last) {
        icon = this.lastMarkerUrl;
      } else {
        icon = this.betweenMarkerUrl;
      }
    }

    if (!icon) {
      icon = this.defaultUrl;
    }

    return {
      icon,
      draggable: false,
      title: marker?.markerTitle,
    };
  }

  openInfoWindow(mapMarker: MapMarker, marker: MapConfig) {
    this.infoWindowContent = marker?.windowTitle;
    if (this.infoWindow?.open) {
      this.infoWindow.open(mapMarker);
    }
  }
}
