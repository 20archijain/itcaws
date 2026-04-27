import { Component, Input, OnDestroy, OnInit } from '@angular/core';
import { NavigationEnd, Router } from '@angular/router';
import { Title } from '@angular/platform-browser';
import { Subscription } from 'rxjs';

import { INavigationItem, NavigationItem } from '../navigation/navigation';
import { Functions } from 'src/app/core/utils/functions.list';
import { RoutingService } from 'src/app/core/services/routing.service';
import { CONSTANTS } from 'src/app/app.constants';
import { Breadcrumb } from 'src/app/core/interfaces/common.interface';

@Component({
  selector: 'app-breadcrumb',
  templateUrl: './breadcrumb.component.html',
  standalone: false,
})
export class BreadcrumbComponent implements OnDestroy, OnInit {
  private subscription: Subscription[] = [];
  @Input() type: string;

  public navigation: INavigationItem[] = [];
  public navigationList: Array<Breadcrumb> = [];

  constructor(private route: Router, public nav: NavigationItem, private titleService: Title,
    private routerService: RoutingService) {
    this.type = 'theme2';
  }

  ngOnInit() {
    this.navigation = this.nav.get();
    this.setBreadcrumb();
    const routerUrl = this.route.url;
    if (routerUrl && typeof routerUrl === 'string') {
      this.filterNavigation(routerUrl);
    }
  }

  ngOnDestroy() {
    this.subscription.forEach(sub => sub.unsubscribe());
  }

  goToHome() {
    const params = Functions.getHomeLocation() ?? [];
    if (params) {
      this.routerService.navigate('app', params as never[]);
    }
  }

  setBreadcrumb() {
    let routerUrl: string;
    this.subscription.push(
      this.route.events
        .subscribe((router: any) => {
          routerUrl = router.urlAfterRedirects;
          if (router instanceof NavigationEnd && routerUrl && typeof routerUrl === 'string') {
            const activeLink = router.url;
            this.filterNavigation(activeLink);
          }
        })
    );
  }

  filterNavigation(activeLink: string) {
    let result: Breadcrumb[] = [];
    let title = 'Welcome';
    this.navigation.forEach((a) => {
      if (a.type === 'item' && 'url' in a && a.url === activeLink) {
        result = [
          {
            breadcrumbs: ('breadcrumbs' in a) ? (a.breadcrumbs ?? false) : true,
            title: a.title ?? '',
            type: a.type,
            url: ('url' in a) ? a.url : false,
          }
        ];
        title = a.title ?? '';
      } else {
        if (a.type === 'collapse' && 'children' in a) {
          a.children?.forEach((b) => {
            if (b.type === 'item' && 'url' in b && b.url === activeLink) {
              result = [
                {
                  breadcrumbs: ('breadcrumbs' in a) ? (a.breadcrumbs ?? false) : true,
                  title: a.title ?? '',
                  type: a.type ?? 'collapse',
                  url: false,
                },
                {
                  breadcrumbs: ('breadcrumbs' in b) ? (b.breadcrumbs ?? false) : true,
                  title: b.title ?? '',
                  type: b.type ?? 'collapse',
                  url: ('url' in b) ? b.url : false,
                }
              ];
              title = b.title ?? '';
            }
          });
        }
      }
    });
    this.navigationList = result;
    this.titleService.setTitle(`${title}${CONSTANTS.WINDOW_TITLE}`);
  }
}
