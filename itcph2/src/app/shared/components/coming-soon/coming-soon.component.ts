import { Component } from '@angular/core';

import { RoutingService } from 'src/app/core/services/routing.service';

@Component({
  templateUrl: './coming-soon.component.html',
})
export class ComingSoonComponent {
  constructor(private routingService: RoutingService) { }

  onBackClick() {
    this.routingService.back();
  }
}
