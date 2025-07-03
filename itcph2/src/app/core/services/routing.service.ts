import { ActivatedRoute, Router, RouterState } from '@angular/router';
import { Injectable } from '@angular/core';
import { Location } from '@angular/common';

@Injectable()
export class RoutingService {

  constructor(private router: Router, private location: Location, private route: ActivatedRoute) { }

  navigate(path: string, params = []) {
    this.router.navigate([path, ...params]);
  }

  back() {
    const routerState = (this.route['_routerState'] as RouterState);
    if (routerState && routerState.snapshot.url && routerState.snapshot.url !== '/auth/login') {
      this.location.back();
    }
  }
}
