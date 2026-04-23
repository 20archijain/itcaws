import { AfterViewInit, Component, ElementRef, EventEmitter, OnDestroy, OnInit, Output, ViewChild } from '@angular/core';
import { Location } from '@angular/common';
import { NavigationEnd, Router } from '@angular/router';
import { Subscription } from 'rxjs';

import { NavigationItem } from '../navigation';
import { NextConfig } from 'src/app/app-config';

@Component({
  selector: 'app-nav-content',
  templateUrl: './nav-content.component.html',
  standalone: false,
})
export class NavContentComponent implements OnDestroy, OnInit, AfterViewInit {
  @Output() private onNavMobCollapse = new EventEmitter();
  @ViewChild('navbarContent', { static: false }) private navbarContent!: ElementRef;
  @ViewChild('navbarWrapper', { static: false }) private navbarWrapper!: ElementRef;
  private subscription: Subscription[] = [];
  private contentWidth: number;
  private wrapperWidth: any;
  private scrollWidth: any;
  private windowWidth: number;
  public nextConfig: any;
  public navigation: any;
  public prevDisabled: string;
  public nextDisabled: string;

  constructor(public nav: NavigationItem, private location: Location, private route: Router) {
    this.nextConfig = NextConfig.config;
    this.windowWidth = window.innerWidth;

    this.prevDisabled = 'disabled';
    this.nextDisabled = '';
    this.scrollWidth = 0;
    this.contentWidth = 0;
  }

  ngOnInit() {
    this.navigation = this.nav.get();
    if (this.windowWidth < 992) {
      // this.nextConfig['layout'] = 'vertical';
      setTimeout(() => {
        document.querySelector('.pcoded-navbar')?.classList.add('menupos-static');
        if ((document.querySelector('#nav-ps-next') as HTMLElement)?.style) {
          (document.querySelector('#nav-ps-next') as HTMLElement).style.maxHeight = '100%';
        }
      }, 500);
    }
  }

  ngOnDestroy() {
    this.subscription.forEach(sub => sub.unsubscribe());
  }

  ngAfterViewInit() {
    if (this.nextConfig['layout'] === 'horizontal') {
      this.contentWidth = this.navbarContent.nativeElement.clientWidth;
      this.wrapperWidth = this.navbarWrapper.nativeElement.clientWidth;
    }

    // used when page reload
    this.fireOutClick();

    // used when routing, especially Back button in Coming soon and Not found component
    this.subscription.push(
      this.route.events
        .subscribe((router: any) => {
          if (router instanceof NavigationEnd) {
            this.fireOutClick();
          }
        })
    );
  }

  scrollPlus() {
    this.scrollWidth = this.scrollWidth + (this.wrapperWidth - 80);
    if (this.scrollWidth > (this.contentWidth - this.wrapperWidth)) {
      this.scrollWidth = this.contentWidth - this.wrapperWidth + 80;
      this.nextDisabled = 'disabled';
    }
    this.prevDisabled = '';
    if (this.nextConfig.rtlLayout) {
      (document.querySelector('#side-nav-horizontal') as HTMLElement).style.marginRight = '-' + this.scrollWidth + 'px';
    } else {
      (document.querySelector('#side-nav-horizontal') as HTMLElement).style.marginLeft = '-' + this.scrollWidth + 'px';
    }
  }

  scrollMinus() {
    this.scrollWidth = this.scrollWidth - this.wrapperWidth;
    if (this.scrollWidth < 0) {
      this.scrollWidth = 0;
      this.prevDisabled = 'disabled';
    }
    this.nextDisabled = '';
    if (this.nextConfig.rtlLayout) {
      (document.querySelector('#side-nav-horizontal') as HTMLElement).style.marginRight = '-' + this.scrollWidth + 'px';
    } else {
      (document.querySelector('#side-nav-horizontal') as HTMLElement).style.marginLeft = '-' + this.scrollWidth + 'px';
    }

  }

  fireLeave() {
    const sections = document.querySelectorAll('.pcoded-hasmenu');
    sections.forEach(section => {
      section.classList.remove('active');
      section.classList.remove('pcoded-trigger');
    });

    let current_url = this.location.path();
    if ((this.location as any)['_baseHref']) {
      current_url = (this.location as any)['_baseHref'] + this.location.path();
    }
    current_url = current_url ? current_url.split('/').slice(0, 4).join('/') : current_url;
    const link = 'a.nav-link[ href=\'#' + current_url + '\' ]';
    const ele = document.querySelector(link);
    if (ele !== null && ele !== undefined) {
      const parent = ele.parentElement;
      const up_parent = parent?.parentElement?.parentElement;
      const last_parent = up_parent?.parentElement;
      if (parent?.classList.contains('pcoded-hasmenu')) {
        parent.classList.add('active');
      } else if (up_parent?.classList.contains('pcoded-hasmenu')) {
        up_parent.classList.add('active');
      } else if (last_parent?.classList.contains('pcoded-hasmenu')) {
        last_parent.classList.add('active');
      }
    }
  }

  navMob() {
    if (this.windowWidth < 992 && document.querySelector('app-navigation.pcoded-navbar')?.classList.contains('mob-open')) {
      this.onNavMobCollapse.emit();
    }
  }

  fireOutClick() {
    let current_url = this.location.path();
    if ((this.location as any)['_baseHref']) {
      current_url = (this.location as any)['_baseHref'] + this.location.path();
    }
    current_url = current_url ? current_url.split('/').slice(0, 4).join('/') : current_url;
    const link = 'a.nav-link[ href=\'#' + current_url + '\' ]';
    const ele = document.querySelector(link);
    if (ele !== null && ele !== undefined) {
      const parent = ele.parentElement;
      const up_parent = parent?.parentElement?.parentElement;
      const last_parent = up_parent?.parentElement;

      // remove active class from all opened collapse
      const sections = document.querySelectorAll('.pcoded-hasmenu');
      sections.forEach(section => {
        section.classList.remove('active');
        section.classList.remove('pcoded-trigger');
      });

      if (parent?.classList.contains('pcoded-hasmenu')) {
        if (this.nextConfig['layout'] === 'vertical') {
          parent.classList.add('pcoded-trigger');
        }
        parent.classList.add('active');
      } else if (up_parent?.classList.contains('pcoded-hasmenu')) {
        if (this.nextConfig['layout'] === 'vertical') {
          up_parent.classList.add('pcoded-trigger');
        }
        up_parent.classList.add('active');
      } else if (last_parent?.classList.contains('pcoded-hasmenu')) {
        if (this.nextConfig['layout'] === 'vertical') {
          last_parent.classList.add('pcoded-trigger');
        }
        last_parent.classList.add('active');
      }
    }
  }

}
