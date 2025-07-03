import { Component, ComponentFactoryResolver, OnDestroy, OnInit, ViewChild } from '@angular/core';
import { Subscription } from 'rxjs';
import { ActivatedRoute } from '@angular/router';

import { NextConfig } from 'src/app/app-config';
import { ComponentItem } from 'src/app/core/utils/component.item';
import { DynamicComponentDirective } from 'src/app/main/directives/dynamic-component.directive';
import { DynamicComponentService } from 'src/app/main/services/dynamic-component.service';
import { URL_PARAMS_KEYS } from 'src/app/app.constants';
import { MAP_MAIN_COMPONENTS } from '../../components';

@Component({
  selector: 'app-body',
  templateUrl: './body.component.html'
})
export class BodyComponent implements OnDestroy, OnInit {
  @ViewChild(DynamicComponentDirective, { static: true }) private dynComp: DynamicComponentDirective;
  private windowWidth: number;
  private components: ComponentItem[];
  private subscription: Subscription[] = [];
  nextConfig: any;
  navCollapsed: boolean;
  navCollapsedMob: boolean;
  toggleZIndex = false;

  constructor(private dynamicComponentService: DynamicComponentService,
    private componentFactoryResolver: ComponentFactoryResolver,
    private route: ActivatedRoute) {
    this.nextConfig = NextConfig.config;
    this.windowWidth = window.innerWidth;

    if (this.windowWidth >= 992 && this.windowWidth <= 1024) {
      this.nextConfig.collapseMenu = true;
    }

    this.navCollapsed = (this.windowWidth >= 992) ? this.nextConfig.collapseMenu : false;
    this.navCollapsedMob = false;
  }

  ngOnInit() {
    if (this.windowWidth < 992) {
      // this.nextConfig.layout = 'vertical';
      setTimeout(() => {
        document.querySelector('.pcoded-navbar').classList.add('menupos-static');
        if ((document.querySelector('#nav-ps-next') as HTMLElement)?.style) {
          (document.querySelector('#nav-ps-next') as HTMLElement).style.maxHeight = '100%'; // 100%
        }
      }, 500);
    }

    // Load dynamic component
    this.components = this.dynamicComponentService.getComponents();
    this.loadComponent();
  }

  ngOnDestroy() {
    this.subscription.forEach(sub => sub.unsubscribe());
  }

  navMobClick() {
    if (this.windowWidth < 992) {
      if (!(document.querySelector('app-navigation.pcoded-navbar').classList.contains('mob-open'))) {
        this.navCollapsedMob = !this.navCollapsedMob;
        setTimeout(() => {
          this.navCollapsedMob = !this.navCollapsedMob;
        }, 100);
      } else {
        this.navCollapsedMob = !this.navCollapsedMob;
      }
    }
  }

  loadComponent() {
    this.subscription.push(
      this.route.paramMap
        .subscribe((routeParams) => {
          const route = {
            [URL_PARAMS_KEYS.modc]: routeParams.get(URL_PARAMS_KEYS.modc),
            [URL_PARAMS_KEYS.pmodc]: routeParams.get(URL_PARAMS_KEYS.pmodc)
          };

          const mod = this.components.find(module => JSON.stringify(module.module) === JSON.stringify(route));

          const componentFactory = this.componentFactoryResolver
            .resolveComponentFactory(mod && mod.component ? mod.component : MAP_MAIN_COMPONENTS.ComingSoonComponent);
          const viewContainerRef = this.dynComp.viewContainerRef;
          viewContainerRef.clear();

          viewContainerRef.createComponent(componentFactory);
        })
    );
  }
}
