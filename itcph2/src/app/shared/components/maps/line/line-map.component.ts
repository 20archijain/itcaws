import { AfterViewInit, Component, Input, OnChanges, SimpleChanges, ViewChild } from '@angular/core';
import { MapPolyline } from '@angular/google-maps';

import { MapConfig } from 'src/app/core/interfaces/common.interface';
import { MAP_DEFAULTS, mapPath } from 'src/app/app.constants';

@Component({
  selector: 'app-line-map',
  templateUrl: './line-map.component.html'
})
export class LineMapComponent implements AfterViewInit, OnChanges {
  @ViewChild(MapPolyline) mapPolyline: MapPolyline;
  @Input() markers: MapConfig[] = [];
  @Input() defaultZoom = MAP_DEFAULTS.defaultZoom;
  @Input() strokeWeight = MAP_DEFAULTS.strokeWeight;
  @Input() strokeColor = MAP_DEFAULTS.strokeColor;
  @Input() mapTypeId = MAP_DEFAULTS.type.ROADMAP;
  @Input() showAnimation = true;
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
  polylineOptions: google.maps.PolylineOptions = {
    draggable: false,
    strokeColor: this.strokeColor,
    strokeWeight: this.strokeWeight,
    geodesic: true,
    icons: this.showAnimation ? [
      {
        icon: {
          path: google.maps.SymbolPath.CIRCLE,
          scale: 8,
          strokeColor: '#393',
        },
        offset: '100%',
      }
    ] : [],
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

  ngAfterViewInit(): void {
    if (this.showAnimation && this.mapPolyline?.polyline) {
      this.animateCircle(this.mapPolyline.polyline);
    }
  }

  animateCircle(line: google.maps.Polyline) {
    let count = 0;

    window.setInterval(() => {
      count = (count + 1) % 200;

      const icons = line?.get("icons");

      if (icons?.length) {
        icons[0].offset = count / 2 + "%";
        line.set("icons", icons);
      }
    }, MAP_DEFAULTS.animationInterval);
  }
}
