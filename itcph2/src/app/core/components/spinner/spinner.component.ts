import { Component, Input, OnDestroy, OnInit } from '@angular/core';
import { Subscription } from 'rxjs';

import { SPINKIT } from './spinkits';
import { SpinnerService } from '../../services/spinner.service';

@Component({
  selector: 'app-spinner',
  styleUrls: [
    './spinner.component.scss',
    './spinkit-css/sk-line-material.scss'
  ],
  templateUrl: './spinner.component.html',
})
export class SpinnerComponent implements OnDestroy, OnInit {
  private subscription: Subscription[] = [];
  @Input() backgroundColor = '#ff5252';
  @Input() spinner = SPINKIT.skLine;
  public isSpinnerVisible = false;
  public spinkit = SPINKIT;

  constructor(private spinnerService: SpinnerService) {
  }

  ngOnInit() {
    this.subscription.push(
      this.spinnerService.spinner
        .subscribe(showSpinner => {
          this.isSpinnerVisible = showSpinner;
        })
    );
  }

  ngOnDestroy() {
    this.subscription.forEach(sub => sub.unsubscribe());
    this.isSpinnerVisible = false;
  }
}
