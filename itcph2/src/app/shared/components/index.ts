import { AlertComponent } from './alert/alert.component';
import { CaptchaComponent } from './captcha/captcha.component';
import { ComingSoonComponent } from './coming-soon/coming-soon.component';
import { ModalComponent } from './modal/modal.component';
import { CardComponent } from './card/card.component';
import { CustomGalleryComponent } from './custom-gallery/custom-gallery.component';
import { EditModalComponent } from './edit-modal/edit-modal.component';
import { ListingSearchbarComponent } from './tables/listing-searchbar/listing-searchbar.component';
import { LoaderComponent } from './loader/loader.component';
import { PaginationComponent } from './tables/pagination/pagination.component';
import { SkeletonComponent } from './skeleton/skeleton.component';
import { TableListingComponent } from './tables/table-listing/table-listing.component';
import { LocationOnMapModalComponent } from './location-on-map-model/location-on-map-model.component';
import { MAPS_COMPONENTS } from './maps';
import { CHARTS_COMPONENTS } from './charts';
import { StatisticsComponent } from './statistics/statistics.component';
import { ListingColumnComponent } from './tables/listing-column/listing-column.component';
import { TableListingNewComponent } from './tables/table-listing-new/table-listing.new.component';
import { AccordionComponent } from './accordion/accordion.component';
import { AccordionOutletVisitComponent } from './accordion/outlet-visit-accordion.component';
import { NpsrAccordionComponent } from './npsr-accordion/npsr-accordion.component';
import { ProductiveAccordionComponent } from './productive-accordion/productive-accordion.component';
import { ColorAccordionComponent } from './color-accordion/color-accordion.component';

export const SHARED_COMPONENTS = [
  AlertComponent,
  CaptchaComponent,
  ComingSoonComponent,
  ModalComponent,
  CardComponent,
  CustomGalleryComponent,
  EditModalComponent,
  LoaderComponent,
  LocationOnMapModalComponent,
  ListingSearchbarComponent,
  PaginationComponent,
  SkeletonComponent,
  StatisticsComponent,
  TableListingComponent,
  ListingColumnComponent,
  TableListingNewComponent,
  ...MAPS_COMPONENTS,
  ...CHARTS_COMPONENTS,
  AccordionComponent,
  AccordionOutletVisitComponent,
  NpsrAccordionComponent,
  ProductiveAccordionComponent,
  ColorAccordionComponent,
];
