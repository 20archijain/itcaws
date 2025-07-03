import { LayoutComponent } from './layout.component';
import { BodyComponent } from './body/body.component';
import { NavBarComponent } from './body/navbar/navbar.component';
import { NavbarLeftComponent } from './body/navbar/navbar-left/navbar-left.component';
import { NavbarSearchComponent } from './body/navbar/navbar-left/navbar-search/navbar-search.component';
import { NavbarRightComponent } from './body/navbar/navbar-right/navbar-right.component';
import { NavigationComponent } from './body/navigation/navigation.component';
import { NavContentComponent } from './body/navigation/nav-content/nav-content.component';
import { NavCollapseComponent } from './body/navigation/nav-content/nav-collapse/nav-collapse.component';
import { NavGroupComponent } from './body/navigation/nav-content/nav-group/nav-group.component';
import { NavItemComponent } from './body/navigation/nav-content/nav-item/nav-item.component';
import { ConfigurationComponent } from './configuration/configuration.component';
import { BreadcrumbComponent } from './body/breadcrumb/breadcrumb.component';

export const LAYOUT_COMPONENTS = [
  LayoutComponent,
  BodyComponent,
  BreadcrumbComponent,
  NavBarComponent,
  NavbarLeftComponent,
  NavbarSearchComponent,
  NavbarRightComponent,
  NavigationComponent,
  NavContentComponent,
  NavCollapseComponent,
  NavGroupComponent,
  NavItemComponent,
  ConfigurationComponent,
];
