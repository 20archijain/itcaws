import { Component } from '@angular/core';

import { LocationsCoveredComponent } from './locations-covered.component';

@Component({
    templateUrl: './rmd-locations-covered.component.html',
    standalone: false
})
export class RMDLocationsCoveredComponent extends LocationsCoveredComponent {
  isDateRequired = false;
  isRmdNameRequired = true;
}
