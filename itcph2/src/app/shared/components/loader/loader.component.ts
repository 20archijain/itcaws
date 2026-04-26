import { Component, OnDestroy, OnInit } from '@angular/core';
import { Subscription } from 'rxjs';

import { LoaderService } from 'src/app/core/services/loader.service';

@Component({
  selector: 'app-loader',
  templateUrl: './loader.component.html',
  standalone: false,
})
export class LoaderComponent implements OnInit, OnDestroy {
  private subscription: Subscription[] = [];
  showLoader = false;

  constructor(private loaderService: LoaderService) { }

  ngOnInit() {
    this.subscription.push(
      this.loaderService.loader
        .subscribe(showLoader => {
          this.showLoader = showLoader;
        })
    );
  }

  ngOnDestroy() {
    this.subscription.forEach(sub => sub.unsubscribe());
  }
}
