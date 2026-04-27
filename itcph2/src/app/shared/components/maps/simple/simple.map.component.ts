import { Component, Input, OnChanges, SimpleChanges, ViewChild } from '@angular/core';
import { MapInfoWindow, MapMarker, GoogleMap } from '@angular/google-maps';

import { MapConfig } from 'src/app/core/interfaces/common.interface';
import { mapPath, MAP_DEFAULTS } from 'src/app/app.constants';

@Component({
  selector: 'app-simple-map',
  templateUrl: './simple-map.component.html',
  standalone: false,
})
export class SimpleMapComponent implements OnChanges {
  @ViewChild(MapInfoWindow, { static: false }) infoWindow!: MapInfoWindow;
  @ViewChild(GoogleMap, { static: false }) map!: GoogleMap;
  @Input() markers: MapConfig[] = [];
  @Input() defaultZoom = MAP_DEFAULTS.defaultZoom;
  @Input() defaultUrl = `${mapPath}${MAP_DEFAULTS.icons.GREEN}`;
  @Input() mapTypeId = MAP_DEFAULTS.type.ROADMAP;
  @Input() useGoogleMapWrapper = true;
  @Input() useDifferentIconForFirstLastBetweenMarker = false;
  @Input() firstMarkerUrl = `${mapPath}${MAP_DEFAULTS.icons.START}`;
  @Input() betweenMarkerUrl = `${mapPath}${MAP_DEFAULTS.icons.BETWEEN}`;
  @Input() lastMarkerUrl = `${mapPath}${MAP_DEFAULTS.icons.END}`;
  @Input() useHeatMap = false;
  @Input() heatMapGradient: string[] = [
    'rgba(0, 255, 255, 0)',
    'rgba(0, 255, 255, 1)',
    'rgba(0, 191, 255, 1)',
    'rgba(0, 127, 255, 1)',
    'rgba(0, 63, 255, 1)',
    'rgba(0, 0, 255, 1)',
    'rgba(0, 0, 223, 1)',
    'rgba(0, 0, 191, 1)',
    'rgba(0, 0, 159, 1)',
    'rgba(0, 0, 127, 1)',
    'rgba(63, 0, 91, 1)',
    'rgba(127, 0, 63, 1)',
    'rgba(191, 0, 31, 1)',
    'rgba(255, 0, 0, 1)'
  ];
  @Input() heatMapRadius = 20;
  @Input() heatMapOpacity = 0.6;
  center!: google.maps.LatLngLiteral;
  mapOptions: google.maps.MapOptions = {
    zoomControl: true,
    scrollwheel: true,
    streetViewControl: true,
    disableDoubleClickZoom: true,
    maxZoom: MAP_DEFAULTS.maxZoom,
    minZoom: MAP_DEFAULTS.minZoom,
  };
  infoWindowContent?: string;
  private heatmapLayer: google.maps.visualization.HeatmapLayer | null = null;
  heatMapData: google.maps.LatLng[] = [];

  ngOnChanges(changes: SimpleChanges) {
    if (changes?.markers?.currentValue?.length) {
      // Reset zoom level
      this.mapOptions = { ...this.mapOptions, zoom: this.defaultZoom };

      // Calculate center based on all markers (average)
      this.calculateCenter();

      // Prepare heat map data
      if (this.useHeatMap) {
        this.prepareHeatMapData();
        // Initialize heat map if map is already ready
        if (this.map?.googleMap) {
          setTimeout(() => {
            this.initializeHeatMap();
          }, 100);
        }
      }
    }
    if (changes?.mapTypeId?.currentValue) {
      this.mapOptions.mapTypeId = this.mapTypeId;
    }
    if (changes?.useHeatMap?.currentValue !== undefined) {
      if (this.useHeatMap && this.markers?.length) {
        this.prepareHeatMapData();
        if (this.map?.googleMap) {
          setTimeout(() => {
            this.initializeHeatMap();
          }, 100);
        }
      } else {
        this.clearHeatMap();
      }
    }
  }

  calculateCenter() {
    if (!this.markers || this.markers.length === 0) {
      this.center = { lat: 0, lng: 0 };
      return;
    }

    // Calculate average latitude and longitude
    const sumLat = this.markers.reduce((sum, marker) => sum + marker.latitude, 0);
    const sumLng = this.markers.reduce((sum, marker) => sum + marker.longitude, 0);
    const avgLat = sumLat / this.markers.length;
    const avgLng = sumLng / this.markers.length;

    this.center = {
      lat: avgLat,
      lng: avgLng,
    };
  }

  prepareHeatMapData() {
    if (!this.markers || this.markers.length === 0) {
      return;
    }

    // Convert markers to LatLng objects for heat map
    this.heatMapData = this.markers.map(marker =>
      new google.maps.LatLng(marker.latitude, marker.longitude)
    );

    // Initialize heat map when map is ready
    if (this.map?.googleMap) {
      this.initializeHeatMap();
    }
  }

  onMapReady() {
    if (this.useHeatMap && this.heatMapData.length > 0) {
      // Use setTimeout to ensure map is fully initialized
      setTimeout(() => {
        this.initializeHeatMap();
      }, 100);
    }
  }

  initializeHeatMap() {
    const mapInstance = this.map?.googleMap;
    if (!mapInstance || !this.heatMapData.length) {
      return;
    }

    // Clear existing heat map layer
    this.clearHeatMap();

    // Create new heat map layer
    this.heatmapLayer = new google.maps.visualization.HeatmapLayer({
      data: this.heatMapData,
      map: mapInstance,
      radius: this.heatMapRadius,
      opacity: this.heatMapOpacity,
      gradient: this.heatMapGradient,
    });
  }

  clearHeatMap() {
    if (this.heatmapLayer) {
      this.heatmapLayer.setMap(null);
      this.heatmapLayer = null;
    }
  }

  getMarkerPosition(marker: MapConfig): google.maps.LatLngLiteral {
    return {
      lat: marker.latitude,
      lng: marker.longitude,
    };
  }

  getMarkerOptions(marker: MapConfig, first: boolean, last: boolean): google.maps.MarkerOptions {
    let icon = '';
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
