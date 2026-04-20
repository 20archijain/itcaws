import { Component, Input, OnChanges, SimpleChanges } from '@angular/core';

import { MapConfig } from 'src/app/core/interfaces/common.interface';
import { MAP_DEFAULTS, mapPath } from 'src/app/app.constants';

@Component({
    selector: 'app-area-map',
    templateUrl: './area-map.component.html',
    standalone: false
})
export class AreaMapComponent implements OnChanges {
  @Input() markers: MapConfig[] = [];
  @Input() defaultZoom = MAP_DEFAULTS.defaultZoom;
  @Input() strokeWeight = MAP_DEFAULTS.strokeWeight;
  @Input() strokeColor = MAP_DEFAULTS.strokeColor;
  @Input() fillColor = MAP_DEFAULTS.fillColor;
  @Input() mapTypeId = MAP_DEFAULTS.type.ROADMAP;
  @Input() showMarkers = false;
  @Input() firstMarkerUrl = `${mapPath}${MAP_DEFAULTS.icons.START}`;
  @Input() betweenMarkerUrl = `${mapPath}${MAP_DEFAULTS.icons.BETWEEN}`;
  @Input() lastMarkerUrl = `${mapPath}${MAP_DEFAULTS.icons.END}`;
  paths: google.maps.LatLngLiteral[] = [];
  center: google.maps.LatLngLiteral;
  mapOptions: google.maps.MapOptions = {
    zoomControl: true,
    scrollwheel: true,
    streetViewControl: true,
    disableDoubleClickZoom: true,
    maxZoom: MAP_DEFAULTS.maxZoom,
    minZoom: MAP_DEFAULTS.minZoom,
  };
  polygonOptions: google.maps.PolygonOptions = {
    draggable: false,
    fillColor: this.fillColor,
    strokeColor: this.strokeColor,
    strokeWeight: this.strokeWeight,
  };

  ngOnChanges(changes: SimpleChanges) {
    if (changes?.markers?.currentValue?.length > 0) {
      // Reset zoom level
      this.mapOptions = { ...this.mapOptions, zoom: this.defaultZoom };

      // Reset center
      this.center = {
        lat: this.markers?.length ? this.markers[0].latitude : 0,
        lng: this.markers?.length ? this.markers[0].longitude : 0,
      };

      this.paths = [];
      this.paths = this.markers.map((marker: MapConfig) => ({ lat: marker.latitude, lng: marker.longitude }));
    }
  }
}
