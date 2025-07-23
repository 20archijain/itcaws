import { AfterViewInit, Component, Input, OnChanges, SimpleChanges, ViewChild } from '@angular/core';
import { MapPolyline, GoogleMap } from '@angular/google-maps';

import { MapConfig } from 'src/app/core/interfaces/common.interface';
import { MAP_DEFAULTS, mapPath } from 'src/app/app.constants';

@Component({
  selector: 'app-line-map',
  templateUrl: './line-map.component.html'
})
export class LineMapComponent implements AfterViewInit, OnChanges {
  @ViewChild(MapPolyline) mapPolyline: MapPolyline;
  @ViewChild(GoogleMap) googleMap: GoogleMap;
  @Input() markers: MapConfig[] = [];
  @Input() defaultZoom = MAP_DEFAULTS.defaultZoom;
  @Input() strokeWeight = MAP_DEFAULTS.strokeWeight;
  @Input() strokeColor = MAP_DEFAULTS.strokeColor;
  @Input() mapTypeId = MAP_DEFAULTS.type.ROADMAP;
  @Input() showAnimation = true;
  @Input() showMarkers = true;
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
      { icon: { path: google.maps.SymbolPath.CIRCLE, scale: 8, strokeColor: '#393' }, offset: '100%' }
    ] : [],
  };

  directionsService = new google.maps.DirectionsService();
  directionsRenderer = new google.maps.DirectionsRenderer();
    walkingIcon = {
    url: `${mapPath}walking-man.png`,
    scaledSize: new google.maps.Size(32, 32),
    origin: new google.maps.Point(0, 0),
    anchor: new google.maps.Point(16, 16)
  };

  marker: google.maps.Marker;

  ngOnChanges(changes: SimpleChanges) {
    if (changes?.markers?.currentValue?.length > 0) {
      this.mapOptions = { ...this.mapOptions, zoom: this.defaultZoom };
      this.center = { lat: this.markers[0].latitude, lng: this.markers[0].longitude };
      this.calculateRoute();
    }
  }

  ngAfterViewInit(): void {
    if (this.showAnimation && this.mapPolyline?.polyline) {
      this.animateCircle(this.mapPolyline.polyline);
    }
    this.directionsRenderer.setMap(this.googleMap.googleMap);
    this.directionsRenderer.setOptions({ suppressMarkers: true });
    this.initializeWalkingAnimation();
  }

  calculateRoute() {
    const waypoints = this.markers.slice(1, -1).map(marker => ({
      location: new google.maps.LatLng(marker.latitude, marker.longitude),
      stopover: true
    }));
    this.directionsService.route({
      origin: new google.maps.LatLng(this.markers[0].latitude, this.markers[0].longitude),
      destination: new google.maps.LatLng(this.markers[this.markers.length - 1].latitude, this.markers[this.markers.length - 1].longitude),
      waypoints: waypoints,
      travelMode: google.maps.TravelMode.DRIVING
    }, (response, status) => {
      if (status === google.maps.DirectionsStatus.OK) {
        this.directionsRenderer.setDirections(response);
         this.startWalkingAnimation(response.routes[0].overview_path);
      } else {
        console.error('Directions request failed due to ' + status);
      }
    });
  }

    initializeWalkingAnimation() {
    this.marker = new google.maps.Marker({
      map: this.googleMap.googleMap,
      icon: this.walkingIcon
    });
  }

  startWalkingAnimation(path: google.maps.LatLng[]) {
    if (!path || path.length < 1) return;
    let step = 0;
    this.marker.setPosition(path[0]);
    const interval = setInterval(() => {
      if (step < path.length - 1) {
        step++;
        const nextPosition = google.maps.geometry.spherical.interpolate(path[step - 1], path[step], 0.5);
        this.marker.setPosition(nextPosition);
      } else {
        clearInterval(interval);
      }
    }, 50000 / path.length); // Adjust speed
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
