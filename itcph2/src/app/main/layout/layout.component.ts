import { Component } from '@angular/core';

import { SessionTimeoutService } from 'src/app/core/services/session.timeout.service';

@Component({
  selector: 'app-layout',
  templateUrl: './layout.component.html',
  standalone: false,
})

export class LayoutComponent {
  constructor(private sessionTimeoutService: SessionTimeoutService) { }
}
