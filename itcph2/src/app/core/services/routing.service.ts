import { Router } from '@angular/router';
import { Injectable } from '@angular/core';
import { Location } from '@angular/common';

@Injectable()
export class RoutingService {

  constructor(private router: Router, private location: Location) { }

  navigate(path: string, params = []) {
    this.router.navigate([path, ...params]);
  }

  back() {
    const routerUrl = this.router.url;
    if (routerUrl && routerUrl !== '/auth/login') {
      this.location.back();
    }
  }
}
