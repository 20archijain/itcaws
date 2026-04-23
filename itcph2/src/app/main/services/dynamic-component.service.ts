import { Injectable } from '@angular/core';

import { ComponentItem } from 'src/app/core/utils/component.item';
import { URL_PARAMS_KEYS } from 'src/app/app.constants';
import { SessionUtil } from 'src/app/core/utils/session.util';
import { Functions } from 'src/app/core/utils/functions.list';
import { HttpRequestModuleCodes, SessionModule, SessionModuleObject } from 'src/app/core/interfaces/common.interface';
import { MAP_MAIN_COMPONENTS } from 'src/app/main/components';

@Injectable()
export class DynamicComponentService {
  private modules: ComponentItem[] = [];

  private getComponent(mod: SessionModule) {
    if (Functions.isEmptyArray(Object.keys(mod.submodules))) {
      const modcKey = URL_PARAMS_KEYS.modc as keyof HttpRequestModuleCodes;
      const pmodcKey = URL_PARAMS_KEYS.pmodc as keyof HttpRequestModuleCodes;
      this.modules.push(
        new ComponentItem(MAP_MAIN_COMPONENTS[mod.componentName as keyof typeof MAP_MAIN_COMPONENTS], {
          [URL_PARAMS_KEYS.modc]: mod[modcKey],
          [URL_PARAMS_KEYS.pmodc]: mod[pmodcKey]
        }));
    } else {
      // list of sub modules of a main module
      const subModules = Object.keys(mod.submodules);
      subModules.forEach(subMod => {
        this.getComponent(mod.submodules[subMod]);
      });
    }
  }

  getComponents() {
    let modules: SessionModuleObject;
    this.modules = [];
    if (SessionUtil.getItem('modules')) {
      modules = JSON.parse(SessionUtil.getItem('modules') || '{}') as SessionModuleObject;
      const mainModules = Object.keys(modules);

      // list of main modules
      mainModules.forEach(mod => {
        this.getComponent(modules[mod]);
      });
    }

    return this.modules;
  }
}
