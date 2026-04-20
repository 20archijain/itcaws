import { Component, Input } from '@angular/core';
import { animate, style, transition, trigger } from '@angular/animations';

import { INavigationItem } from '../../navigation';
import { NextConfig } from 'src/app/app-config';

@Component({
    animations: [
        trigger('slideInOut', [
            transition(':enter', [
                style({ transform: 'translateY(-100%)', display: 'block' }),
                animate('250ms ease-in', style({ transform: 'translateY(0%)' }))
            ]),
            transition(':leave', [
                animate('250ms ease-in', style({ transform: 'translateY(-100%)' }))
            ])
        ])
    ],
    selector: 'app-nav-collapse',
    templateUrl: './nav-collapse.component.html',
    standalone: false
})
export class NavCollapseComponent {
  private nextConfig: any;
  @Input() item: INavigationItem;
  public themeLayout: string;

  constructor() {
    this.nextConfig = NextConfig.config;
    this.themeLayout = this.nextConfig.layout;
  }

  navCollapse(e) {
    let parent = e.target;
    if (this.themeLayout === 'vertical') {
      parent = parent.parentElement;
    }

    const sections = document.querySelectorAll('.pcoded-hasmenu');
    sections.forEach(section => {
      if (section !== parent) {
        section.classList.remove('pcoded-trigger');
      }
    });

    let firstParent = parent.parentElement;
    let preParent = parent.parentElement.parentElement;
    if (firstParent.classList.contains('pcoded-hasmenu')) {
      do {
        firstParent.classList.add('pcoded-trigger');
        firstParent = firstParent.parentElement.parentElement.parentElement;
      } while (firstParent.classList.contains('pcoded-hasmenu'));
    } else if (preParent.classList.contains('pcoded-submenu')) {
      do {
        preParent.parentElement.classList.add('pcoded-trigger');
        preParent = preParent.parentElement.parentElement.parentElement;
      } while (preParent.classList.contains('pcoded-submenu'));
    }
    parent.classList.toggle('pcoded-trigger');
  }

}
