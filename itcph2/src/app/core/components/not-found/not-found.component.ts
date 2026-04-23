import { Component } from '@angular/core';

import { RoutingService } from '../../services/routing.service';

@Component({
  templateUrl: './not-found.component.html',
  standalone: false,
})
export class NotFoundComponent {
  constructor(private routingService: RoutingService) { }

  onBackClick() {
    this.routingService.back();
  }
}
