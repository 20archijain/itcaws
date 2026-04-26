import { Component, EventEmitter, OnInit, Output } from '@angular/core';

import { SessionUtil } from 'src/app/core/utils/session.util';
import { LoginDataClient } from 'src/app/core/interfaces/http-response.interface';
import { NextConfig } from 'src/app/app-config';
import { Functions } from 'src/app/core/utils/functions.list';

@Component({
  selector: 'app-navbar',
  templateUrl: './navbar.component.html',
  standalone: false,
})
export class NavBarComponent implements OnInit {
  nextConfig: any;
  menuClass: boolean;
  collapseStyle: string;
  windowWidth: number;
  clientInfo!: LoginDataClient;
  homePage = '';

  @Output() onNavCollapse = new EventEmitter();
  @Output() onNavHeaderMobCollapse = new EventEmitter();

  constructor() {
    this.nextConfig = NextConfig.config;
    this.menuClass = false;
    this.collapseStyle = 'none';
    this.windowWidth = window.innerWidth;
  }

  ngOnInit() {
    this.clientInfo = JSON.parse(SessionUtil.getItem('client') || '{}');
    const params = Functions.getHomeLocation() ?? [];
    if (params.length) {
      this.homePage = `/app/${params[0]}/${params[1]}`;
    }
  }

  toggleMobOption() {
    this.menuClass = !this.menuClass;
    this.collapseStyle = (this.menuClass) ? 'block' : 'none';
  }

  navCollapse() {
    if (this.windowWidth >= 992) {
      this.onNavCollapse.emit();
    } else {
      this.onNavHeaderMobCollapse.emit();
    }
  }
}
