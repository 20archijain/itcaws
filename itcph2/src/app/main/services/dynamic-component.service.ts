import { Injectable } from '@angular/core';

import { ComponentItem } from 'src/app/core/utils/component.item';
import { URL_PARAMS_KEYS } from 'src/app/app.constants';
import { SessionUtil } from 'src/app/core/utils/session.util';
import { Functions } from 'src/app/core/utils/functions.list';
import { SessionModule, SessionModuleObject } from 'src/app/core/interfaces/common.interface';
import { MAP_MAIN_COMPONENTS } from 'src/app/main/components';

@Injectable()
export class DynamicComponentService {
  private modules: ComponentItem[] = [];

  private getComponent(mod: SessionModule) {
    if (Functions.isEmptyArray(Object.keys(mod.submodules))) {
      this.modules.push(
        new ComponentItem(MAP_MAIN_COMPONENTS[mod.componentName], {
          [URL_PARAMS_KEYS.modc]: mod[URL_PARAMS_KEYS.modc],
          [URL_PARAMS_KEYS.pmodc]: mod[URL_PARAMS_KEYS.pmodc]
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
      modules = JSON.parse(SessionUtil.getItem('modules'));
      const mainModules = Object.keys(modules);

      // list of main modules
      mainModules.forEach(mod => {
        this.getComponent(modules[mod]);
      });
    }

    return this.modules;
  }
}
