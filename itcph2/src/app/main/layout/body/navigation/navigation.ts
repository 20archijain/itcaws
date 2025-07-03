import { Injectable } from '@angular/core';

import { SessionUtil } from 'src/app/core/utils/session.util';
import { SessionModule, SessionModuleObject } from 'src/app/core/interfaces/common.interface';

type NavigationItemType = 'item' | 'collapse' | 'group';

export interface INavigationItem {
  id?: string;
  title?: string;
  type?: NavigationItemType;
  translate?: string;
  icon?: string;
  hidden?: boolean;
  url?: string;
  classes?: string;
  exactMatch?: boolean;
  external?: boolean;
  target?: boolean;
  breadcrumbs?: boolean;
  function?: any;
  badge?: {
    title?: string;
    type?: string;
  };
  children?: NavigationItem[];
}

export interface Navigation extends INavigationItem {
  children?: NavigationItem[];
}

@Injectable()
export class NavigationItem {
  private getAsideItems() {
    let modules: SessionModuleObject;

    if (SessionUtil.getItem('modules')) {
      modules = JSON.parse(SessionUtil.getItem('modules'));

      return this.getModules(modules);
    }

    return [];
  }

  private getModules(modules: SessionModuleObject) {
    const newModules = Object.keys(modules);
    const modulesList = [];

    if (newModules && newModules.length > 0) {
      newModules.forEach(module => {
        modulesList.push(this.getAsideItem(modules[module]));
      });
    }

    return modulesList;
  }

  private getAsideItem(mod: SessionModule) {
    const subModules = mod && mod.submodules ? Object.keys(mod.submodules) : [];

    return {
      breadcrumbs: mod.breadcrumbs,
      children: subModules.length > 0 ? this.getModules(mod.submodules) : [],
      icon: `feather ${mod.icon}`,
      id: mod.name ? mod.name.toLowerCase() : '',
      title: mod.name,
      type: subModules.length > 0 ? 'collapse' : 'item',
      url: `/app/${mod.modc}/${mod.pmodc}`,
    };
  }

  get(): INavigationItem[] {
    return this.getAsideItems();
  }
}
