import { Component, EventEmitter, Output } from '@angular/core';

import { NextConfig } from 'src/app/app-config';

@Component({
  selector: 'app-navigation',
  templateUrl: './navigation.component.html',
  standalone: false,
})
export class NavigationComponent {
  @Output() private onNavMobCollapse = new EventEmitter();
  private windowWidth: number;
  public nextConfig: any;

  constructor() {
    this.nextConfig = NextConfig.config;
    this.windowWidth = window.innerWidth;
  }

  navMobCollapse() {
    if (this.windowWidth < 992) {
      this.onNavMobCollapse.emit();
    }
  }
}
