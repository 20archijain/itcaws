import { } from 'jasmine';
import { ComponentFixture, TestBed } from '@angular/core/testing';
import { TranslateModule } from '@ngx-translate/core';
import { ActivatedRoute, NavigationEnd, Router } from '@angular/router';
import { CUSTOM_ELEMENTS_SCHEMA } from '@angular/core';
import { Subject } from 'rxjs';

import { AppComponent } from './app.component';
import { GAService } from './core/services/ga.service';

const routerEvents = new Subject();
const routerStub = {
  events: routerEvents.asObservable(),
};

describe('AppComponent', () => {
  let component: AppComponent;
  let fixture: ComponentFixture<AppComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [AppComponent],
      providers: [
        GAService,
        {
          provide: ActivatedRoute,
          useValue: {},
        },
        {
          provide: Router,
          useValue: routerStub,
        }
      ],
      imports: [TranslateModule.forRoot()],
      schemas: [CUSTOM_ELEMENTS_SCHEMA],
    }).compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(AppComponent);
    component = fixture.componentInstance;
  });

  it('should be created', () => {
    expect(component).toBeTruthy();
  });

  it('should start tracking pages', () => {
    const gaService = TestBed.inject(GAService);
    spyOn(gaService, 'initiateGMapTracking');
    fixture.detectChanges();
    expect(gaService.initiateGMapTracking).toHaveBeenCalled();
  });

  it('should scroll to top on NavigationEnd', () => {
    spyOn(window, 'scrollTo');
    fixture.detectChanges();
    routerEvents.next(new NavigationEnd(1, '/prev', '/current'));
    expect(window.scrollTo).toHaveBeenCalledWith(0, 0);
  });

  it('should scroll to top on non-NavigationEnd', () => {
    spyOn(window, 'scrollTo');
    fixture.detectChanges();
    routerEvents.next({ type: 'OtherEvent' });
    expect(window.scrollTo).not.toHaveBeenCalled();
  });
});
