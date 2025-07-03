import { enableProdMode } from '@angular/core';
import { platformBrowserDynamic } from '@angular/platform-browser-dynamic';

import { AppModule } from './app/app.module';
import { environment } from './environments/environment';
import { GA_TRACKING_ID } from './app/app.constants';

if (environment.production) {
  loadGA();
  enableProdMode();
}

function loadGA() {
  const gaScript = document.createElement('script');
  gaScript.async = true;
  gaScript.src = `https://www.googletagmanager.com/gtag/js?id=${GA_TRACKING_ID}`;

  const loadGAScript = document.createElement('script');
  loadGAScript.innerText = `
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());

  gtag('config', '${GA_TRACKING_ID}');`;

  document.body.appendChild(gaScript);
  document.body.appendChild(loadGAScript);
}

platformBrowserDynamic().bootstrapModule(AppModule);
