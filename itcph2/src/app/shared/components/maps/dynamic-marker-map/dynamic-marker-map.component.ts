import { Component, EventEmitter, Input, OnChanges, OnInit, Output, SimpleChanges, ViewChild } from '@angular/core';
import { MapInfoWindow, GoogleMap } from '@angular/google-maps';

import { MapConfig } from 'src/app/core/interfaces/common.interface';
import { mapPath, MAP_DEFAULTS, MAP_STYLES } from 'src/app/app.constants';
import { DropdownList } from 'src/app/core/interfaces/http-response.interface';

@Component({
  selector: 'app-dynamic-marker-map',
  templateUrl: './dynamic-marker-map.component.html',
  standalone: false,
})
export class HeatMapComponent implements OnChanges, OnInit {
  @ViewChild(MapInfoWindow, { static: false }) infoWindow!: MapInfoWindow;
  @ViewChild(GoogleMap, { static: false }) map!: GoogleMap;
  selectedMapStyle: keyof typeof MAP_STYLES = 'UBER_STYLE';
  mapStyleKeys = Object.keys(MAP_STYLES) as (keyof typeof MAP_STYLES)[];
  @Input() options: string[] = [];
  @Output() onChange = new EventEmitter<string>();
  @Input() showMapStyleDropdown = false;
  @Input() markers: MapConfig[] = [];
  @Input() defaultZoom = MAP_DEFAULTS.defaultZoom;
  @Input() mapTypeId = MAP_DEFAULTS.type.ROADMAP;
  // @Input() useBlackWhiteStyle = false;
  private customMarkers: google.maps.Marker[] = [];
  private markerMap = new Map();

  center!: google.maps.LatLngLiteral;
  markerData: google.maps.LatLng[] = [];
  infoWindowContent = ''; // Store the HTML content
  mapOptions!: google.maps.MapOptions;

  ngOnInit(): void {
    this.setMapOptions();
  }


  onStyleChanged(newStyle: DropdownList | keyof typeof MAP_STYLES): void {
    this.selectedMapStyle = newStyle as keyof typeof MAP_STYLES;
    this.setMapOptions();
  }

  setMapOptions(): void {
    this.mapOptions = {
      zoomControl: true,
      scrollwheel: true,
      streetViewControl: true,
      disableDoubleClickZoom: true,
      maxZoom: MAP_DEFAULTS.maxZoom,
      minZoom: MAP_DEFAULTS.minZoom,
      styles: MAP_STYLES[this.selectedMapStyle] || undefined
    };
  }

  ngOnChanges(changes: SimpleChanges) {
    if (changes?.useBlackWhiteStyle) {
      this.setMapOptions();
    }

    if (changes?.markers?.currentValue?.length) {
      this.center = {
        lat: this.markers[0]?.latitude || 0,
        lng: this.markers[0]?.longitude || 0,
      };

      this.markerData = this.markers.map(m => new google.maps.LatLng(m.latitude, m.longitude));

    }
  }

  onMapLoad(map: google.maps.Map) {
    this.clearExistingMarkers();

    this.markers.forEach((markerData) => {
      const position = new google.maps.LatLng(markerData.latitude, markerData.longitude);
      const marker = new google.maps.Marker({
        position,
        map,
        icon: this.getCustomMarkerIcon(markerData, map.getZoom() as number),
        // title: markerData.windowTitle || ''
      });

      this.markerMap.set(marker, markerData);
      this.customMarkers.push(marker);

      // Add listeners for hover
      marker.addListener('mouseover', () => {
        this.infoWindowContent = markerData.windowTitle ?? '';
        this.infoWindow.close();
        this.infoWindow.options = { position };
        this.infoWindow.open();
        setTimeout(() => {
          const closeButton = document.querySelector('.gm-ui-hover-effect');
          if (closeButton) {
            (closeButton as HTMLElement).style.display = 'none';
          }

          // Inject CSS rule to hide close button globally
          const style = document.createElement('style');
          style.innerHTML = `
            .gm-ui-hover-effect {
              display: none !important;
            }
          `;
          document.head.appendChild(style);
        }, 100);

      });

      marker.addListener('mouseout', () => {
        this.infoWindow.close();
      });
    });


    map.addListener('zoom_changed', () => {
      const zoom = map.getZoom();
      this.customMarkers.forEach(marker => {
        const markerData = this.markerMap.get(marker);
        if (markerData) {
          marker.setIcon(this.getCustomMarkerIcon(markerData, zoom as number));
        }
      });
    });

  }

  getCustomMarkerIcon(markerData: MapConfig, zoom: number): google.maps.Icon | string {
    const baseSize = 20;
    const sizeFactor = 0.1;
    const newSize = baseSize + (zoom * sizeFactor);

    const iconUrl = markerData.markerUrl || `${mapPath}${MAP_DEFAULTS.icons.GREEN}`;

    return {
      url: iconUrl,
      size: new google.maps.Size(newSize, newSize), // Set size of the icon dynamically
      scaledSize: new google.maps.Size(newSize, newSize), // Scaled size
      anchor: new google.maps.Point(newSize / 2, newSize / 2) // Anchor the marker to the center
    };
  }

  clearExistingMarkers() {
    this.customMarkers.forEach(marker => marker.setMap(null));
    this.customMarkers = [];
  }

}
