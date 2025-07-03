import { Component } from '@angular/core';

import { LocationsCoveredComponent } from './locations-covered.component';

@Component({
  templateUrl: './rmd-locations-covered.component.html'
})
export class RMDLocationsCoveredComponent extends LocationsCoveredComponent {
  isDateRequired = false;
  isRmdNameRequired = true;
}
