import { Component, Input } from '@angular/core';
import { Location } from '@angular/common';

import { INavigationItem } from '../../navigation';
import { NextConfig } from 'src/app/app-config';

@Component({
  selector: 'app-nav-item',
  templateUrl: './nav-item.component.html',
})
export class NavItemComponent {
  private nextConfig: any;
  @Input() item: INavigationItem;
  public themeLayout: string;

  constructor(private location: Location) {
    this.nextConfig = NextConfig.config;
    this.themeLayout = this.nextConfig['layout'];
  }

  closeOtherMenu(event) {
    if (this.nextConfig['layout'] === 'vertical') {
      const ele = event.target.parentElement;
      if (ele !== null && ele !== undefined) {
        const parent = ele.parentElement;
        const up_parent = parent.parentElement.parentElement;
        const last_parent = up_parent.parentElement;
        const sections = document.querySelectorAll('.pcoded-hasmenu');
        sections.forEach(section => {
          section.classList.remove('active');
          section.classList.remove('pcoded-trigger');
        });

        if (parent.classList.contains('pcoded-hasmenu')) {
          parent.classList.add('pcoded-trigger');
          parent.classList.add('active');
        } else if (up_parent.classList.contains('pcoded-hasmenu')) {
          up_parent.classList.add('pcoded-trigger');
          up_parent.classList.add('active');
        } else if (last_parent.classList.contains('pcoded-hasmenu')) {
          last_parent.classList.add('pcoded-trigger');
          last_parent.classList.add('active');
        }
      }
      if ((document.querySelector('app-navigation.pcoded-navbar').classList.contains('mob-open'))) {
        document.querySelector('app-navigation.pcoded-navbar').classList.remove('mob-open');
      }
    } else {
      setTimeout(() => {
        const sections = document.querySelectorAll('.pcoded-hasmenu');
        sections.forEach(section => {
          section.classList.remove('active');
          section.classList.remove('pcoded-trigger');
        });

        let current_url = this.location.path();
        if (this.location['_baseHref']) {
          current_url = this.location['_baseHref'] + this.location.path();
        }
        current_url = current_url ? current_url.split('/').slice(0, 4).join('/') : current_url;
        const link = 'a.nav-link[ href=\'#' + current_url + '\' ]';
        const ele = document.querySelector(link);
        if (ele !== null && ele !== undefined) {
          const parent = ele.parentElement;
          const up_parent = parent.parentElement.parentElement;
          const last_parent = up_parent.parentElement;
          if (parent.classList.contains('pcoded-hasmenu')) {
            parent.classList.add('active');
          } else if (up_parent.classList.contains('pcoded-hasmenu')) {
            up_parent.classList.add('active');
          } else if (last_parent.classList.contains('pcoded-hasmenu')) {
            last_parent.classList.add('active');
          }
        }
      }, 500);
    }
  }

}
