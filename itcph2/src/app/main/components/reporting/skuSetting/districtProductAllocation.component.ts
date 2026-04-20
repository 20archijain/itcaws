import { AfterViewInit, Component, OnDestroy, OnInit } from '@angular/core';
import { UntypedFormBuilder, UntypedFormGroup } from '@angular/forms';
import { Subscription } from 'rxjs';
import { finalize } from 'rxjs/operators';

import { FormService } from 'src/app/core/services/form.service';
import { LoaderService } from 'src/app/core/services/loader.service';
import {
  DashboardData,
  DropdownList, GetProductSelectorDataResponse,
  ProductItem,
  ProductSelectorPayload,
  SubmitSelectedProductsResponse
} from 'src/app/core/interfaces/http-response.interface';
import { REQUEST_STATUS, STATIC_MODULES } from 'src/app/app.constants';
import { environment } from 'src/environments/environment';
import { COMMON_VALIDATORS } from 'src/app/core/validators/validations.list';
import { CanGoBackGuard } from 'src/app/core/guards/can-go-back-guard.service';
import { ToastrService } from 'src/app/core/services/toastr.service';
import { Functions } from 'src/app/core/utils/functions.list';

export interface CategoryGroup {
  category: string;
  products: ProductItem[];
}

@Component({
    templateUrl: './branchProductAllocation.component.html',
    styleUrls: ['./branchProductAllocation.component.scss'],
    standalone: false
})
export class DistrictProductAllocationComponent implements AfterViewInit, OnInit, OnDestroy {

  private subscription: Subscription[] = [];

  form: UntypedFormGroup;
  productOptions: DropdownList[] = [];
  mainBranchOptions: DropdownList[] = [];
  regionOptions: DropdownList[] = [];
  teamTypeOptions: DropdownList[] = [];
  header: string[] = [];
  body: string[] = [];
  isSelectable: boolean;
  statusFlagCond = false;
  defaultDspm = false;
  submittedDataList = [];
  currentDate = Functions.currentDate();
  skuDefaultAllocation = false;

  availableProducts: ProductItem[] = [];   // left panel — from backend
  selectedProducts: ProductItem[] = [];    // right panel — temp array

  // ── Accordion (frontend-derived, no backend change) ────────────────────────
  categoryGroups: CategoryGroup[] = [];
  openCategories = new Set<string>(); // multiple open simultaneously

  // ── Checkbox state maps (keyed by product.id) ──────────────────────────────
  dspmBrandMap: Record<number, boolean> = {};
  isFocusBrandMap: Record<number, boolean> = {};

  readonly DSPM_BRAND_MAX = 2;

  draggedProduct: ProductItem | null = null;
  dragSource: 'available' | 'selected' | null = null;
  isDragOverSelected = false;
  isDragOverAvailable = false;

  isDisabled = false;
  url = environment.getActiveVariantsDataUrl;

  errorMessages = {
    main_branch: COMMON_VALIDATORS.messages.requiredOnly('Branch'),
    region: COMMON_VALIDATORS.messages.requiredOnly('Region'),
    teamType: COMMON_VALIDATORS.messages.requiredOnly('Team Type'),
  };

  constructor(
    private formService: FormService,
    private fb: UntypedFormBuilder,
    private loaderService: LoaderService,
    private canGoBackGuard: CanGoBackGuard,
    private toastrService: ToastrService,
  ) { }

  ngOnInit() {
    this.form = this.fb.group({
      branch: [],
      category: [],
      main_branch: [null, COMMON_VALIDATORS.validators.requiredOnly],
      region: [null, COMMON_VALIDATORS.validators.requiredOnly],
      teamType: [null, COMMON_VALIDATORS.validators.requiredOnly],
    });
    this.getDefaultData();
  }

  ngOnDestroy() {
    this.subscription.forEach(sub => sub.unsubscribe());
  }

  // ── Data loading (API response untouched) ───────────────────────────────────

  getDefaultData() {
    this.loaderService.startLoader();
    this.subscription.push(
      this.formService.getList<GetProductSelectorDataResponse>(this.url, this.form.getRawValue())
        .pipe(finalize(() => this.loaderService.stopLoader()))
        .subscribe(resp => {
          if (resp && resp.status === REQUEST_STATUS.SUCCESS) {
            this.mainBranchOptions = resp.data.mainBranchList;
            this.skuDefaultAllocation = resp.data.skuDefaultAllocation;
          }
        })
    );
  }


  getInitialData() {
    if (this.form.valid) {
      const mainBranch = this.form.get('teamType').value;
      if (mainBranch === '5') {
        this.defaultDspm = true;
      } else {
        this.defaultDspm = false;
      }
      this.loaderService.startLoader();
      this.subscription.push(
        this.formService.getData<GetProductSelectorDataResponse>(this.url, this.form.getRawValue())
          .pipe(finalize(() => this.loaderService.stopLoader()))
          .subscribe(resp => {
            if (resp && resp.status === REQUEST_STATUS.SUCCESS) {
              this.statusFlagCond = resp.data.statusFlag;
              this.submittedDataList = resp.data.submittedList;

              this.selectedProducts = resp.data.selectedDataList;
              //Check In data already submitted by
              if (resp.data.selectedDataList.length > 0) {
                const existingIds = new Set(resp.data.selectedDataList.map(p => p.id));

                // filter
                resp.data.productList = resp.data.productList.filter(p => !existingIds.has(p.value));
              }

              this.availableProducts = resp.data.productList.map(p => ({
                id: p.value,
                name: p.label,
                category: p.category,
              }));
              this.dspmBrandMap = resp.data.isDspmList;
              this.isFocusBrandMap = resp.data.isFocusList;
              this.openCategories = new Set();
              this.buildCategoryGroups(); // derive accordion from flat list
            }
          })
      );
    }
  }

  getRegionList() {
    this.selectedProducts = [];
    this.availableProducts = [];
    this.dspmBrandMap = {};
    this.isFocusBrandMap = {};
    this.openCategories = new Set();
    this.categoryGroups = [];
    this.regionValue = null;
    this.teamTypeValue = null;
    this.loaderService.startLoader();
    this.subscription.push(
      this.formService.customActionCall<DashboardData>(STATIC_MODULES.custom.getBranch, { mainBranch: this.form.get('main_branch').value }, null, environment.viewVanDsDataUrl)
        .pipe(finalize(() => this.loaderService.stopLoader()))
        .subscribe(resp => {
          if (resp && resp.status === REQUEST_STATUS.SUCCESS) {
            this.regionOptions = resp.data.regionList;
          }
        })
    );
  }

  getTeamTypeList() {
    this.selectedProducts = [];
    this.availableProducts = [];
    this.dspmBrandMap = {};
    this.isFocusBrandMap = {};
    this.openCategories = new Set();
    this.categoryGroups = [];
    this.availableProducts = [];
    this.teamTypeValue = null;
    this.loaderService.startLoader();
    this.subscription.push(
      this.formService.customActionCall<DashboardData>(STATIC_MODULES.custom.getTeamsTypeList, { region: this.form.get('region').value }, null, environment.viewVanDsDataUrl)
        .pipe(finalize(() => this.loaderService.stopLoader()))
        .subscribe(resp => {
          if (resp && resp.status === REQUEST_STATUS.SUCCESS) {
            this.teamTypeOptions = resp.data.teamTypeList;
          }
        })
    );
  }

  buildCategoryGroups(): void {
    const map = new Map<string, ProductItem[]>();

    this.availableProducts.forEach(p => {
      const key = p.category || 'Uncategorised';
      if (!map.has(key)) map.set(key, []);
      map.get(key)!.push(p);
    });

    // Preserve existing category order, add new ones at end, keep empty
    // categories if they still have selected products (for the green badge)
    const existingKeys = this.categoryGroups.map(g => g.category);
    const incomingKeys = Array.from(map.keys());
    const allKeys = [
      ...existingKeys.filter(k => map.has(k) || this.selectedCountFor(k) > 0),
      ...incomingKeys.filter(k => !existingKeys.includes(k)),
    ];

    this.categoryGroups = allKeys.map(k => ({
      category: k,
      products: map.get(k) ?? [],
    }));
  }

  /** Toggle a category open/closed — multiple can be open at once */
  toggleCategory(category: string): void {
    if (this.openCategories.has(category)) {
      this.openCategories.delete(category);
    } else {
      this.openCategories.add(category);
    }
    this.openCategories = new Set(this.openCategories); // trigger change detection
  }

  isCategoryOpen(category: string): boolean {
    return this.openCategories.has(category);
  }

  /** Products still available in a category */
  availableCountFor(category: string): number {
    return this.availableProducts.filter(
      p => (p.category || 'Uncategorised') === category
    ).length;
  }

  /** Products already selected from a category */
  selectedCountFor(category: string): number {
    return this.selectedProducts.filter(
      p => (p.category || 'Uncategorised') === category
    ).length;
  }

  // ── Checkbox helpers ────────────────────────────────────────────────────────

  get dspmBrandCount(): number {
    return Object.values(this.dspmBrandMap).filter(Boolean).length;
  }

  get dspmBrandLimitReached(): boolean {
    return this.dspmBrandCount >= this.DSPM_BRAND_MAX;
  }

  toggleDspmBrand(productId: number): void {
    const current = !!this.dspmBrandMap[productId];

    // If checking and limit reached → block
    if (!current && this.dspmBrandLimitReached) return;

    if (current) {
      // ✅ REMOVE key completely when unchecking
      // eslint-disable-next-line @typescript-eslint/no-unused-vars
      const { [productId]: _, ...rest } = this.dspmBrandMap;
      this.dspmBrandMap = rest;
    } else {
      // ✅ Add key when checking
      this.dspmBrandMap = { ...this.dspmBrandMap, [productId]: true };
    }
  }

  get isFocusBrandCount(): number {
    return Object.values(this.isFocusBrandMap).filter(Boolean).length;
  }


  toggleIsFocusBrand(productId: number): void {
    const current = !!this.isFocusBrandMap[productId];

    if (current) {
      // eslint-disable-next-line @typescript-eslint/no-unused-vars
      const { [productId]: _, ...rest } = this.isFocusBrandMap;
      this.isFocusBrandMap = rest;
    } else {
      this.isFocusBrandMap = { ...this.isFocusBrandMap, [productId]: true };
    }
  }

  private clearFlagState(productId: number): void {
    // eslint-disable-next-line @typescript-eslint/no-unused-vars
    const { [productId]: _f, ...restFirst } = this.dspmBrandMap;
    // eslint-disable-next-line @typescript-eslint/no-unused-vars
    const { [productId]: _s, ...restSecond } = this.isFocusBrandMap;
    this.dspmBrandMap = restFirst;
    this.isFocusBrandMap = restSecond;
  }

  // ── Submit ──────────────────────────────────────────────────────────────────

  submit() {

    if (this.currentDate['day'] > 21) {

      const type = this.form.get('teamType')?.value;

      const focusKeys = Object.keys(this.isFocusBrandMap || {});
      const dspmKeys = Object.keys(this.dspmBrandMap || {});

      // ✅ Focus must be between 2 and 4
      const hasValidFocusCount = focusKeys.length > 1 && focusKeys.length < 5;

      // ✅ DSPM must be exactly 2
      const hasValidDspmCount = dspmKeys.length === 2;

      // ✅ Ensure type-safe comparison (fixes string/number mismatch issue)
      const focusSet = new Set(focusKeys.map(String));

      const allDspmInsideFocus = dspmKeys
        .map(String)
        .every(key => focusSet.has(key));


      let formSubmissionCond = false;

      if (type === '5') {
        formSubmissionCond =
          hasValidFocusCount &&
          hasValidDspmCount &&
          allDspmInsideFocus;
      } else {
        formSubmissionCond = hasValidFocusCount;
      }

      if (formSubmissionCond) {

        if (!this.isDisabled && this.selectedProducts.length > 0 && this.form.valid) {

          this.isDisabled = true;
          this.loaderService.startLoader();

          const productsWithFlags = this.selectedProducts.map(p => ({
            ...p,
            dspmBrand: !!this.dspmBrandMap[p.id],
            isFocusBrand: !!this.isFocusBrandMap[p.id],
          }));

          const payload: ProductSelectorPayload = {
            selectedProducts: productsWithFlags,
            formData: this.form.getRawValue(),
          };

          this.subscription.push(
            this.formService.customActionCall<SubmitSelectedProductsResponse>(
              STATIC_MODULES.custom.submitSelectedProducts,
              payload,
              null,
              this.url
            )
              .pipe(
                finalize(() => {
                  this.isDisabled = false;
                  this.loaderService.stopLoader();
                })
              )
              .subscribe(resp => {
                if (resp && resp.status === REQUEST_STATUS.SUCCESS) {
                  this.canGoBackGuard.markAsPristine();
                  this.clearForm();
                }
              })
          );
        }

      } else {

        // ❌ Validation Errors
        if (type === '5') {

          if (!hasValidFocusCount) {
            this.toastrService.toastr({
              msg: 'Focus Brand must be between 2 and 4 SKUs',
              type: 'error'
            });
          }
          else if (!hasValidDspmCount) {
            this.toastrService.toastr({
              msg: 'DSPM Brand must have exactly 2 SKUs',
              type: 'error'
            });
          }
          else if (!allDspmInsideFocus) {
            this.toastrService.toastr({
              msg: 'DSPM SKUs must be selected from Focus Brand only',
              type: 'error'
            });
          }

        } else {

          this.toastrService.toastr({
            msg: 'Focus Brand must be between 2 and 4 SKUs',
            type: 'error'
          });
        }
      }
    } else {
      this.toastrService.toastr({
        msg: 'You can not allocate SKU before 22',
        type: 'error'
      });
    }
  }

  clearForm() {
    this.form.reset();
    this.statusFlagCond = false;
    this.defaultDspm = false;
    this.selectedProducts = [];
    this.availableProducts = [];
    this.dspmBrandMap = {};
    this.isFocusBrandMap = {};
    this.openCategories = new Set();
    this.categoryGroups = [];
    this.submittedDataList = [];
    this.getInitialData();
  }

  // ── Drag events ─────────────────────────────────────────────────────────────

  onDragStart(event: DragEvent, product: ProductItem, source: 'available' | 'selected'): void {
    this.draggedProduct = product;
    this.dragSource = source;
    event.dataTransfer?.setData('text/plain', String(product.id));
    if (event.dataTransfer) event.dataTransfer.effectAllowed = 'move';
    setTimeout(() => (event.target as HTMLElement).classList.add('dragging'), 0);
  }

  onDragEnd(event: DragEvent): void {
    (event.target as HTMLElement).classList.remove('dragging');
    this.draggedProduct = null;
    this.dragSource = null;
    this.isDragOverSelected = false;
    this.isDragOverAvailable = false;
  }

  // ── Drop zone — right panel ─────────────────────────────────────────────────

  onDragOverSelected(event: DragEvent): void {
    event.preventDefault();
    if (event.dataTransfer) event.dataTransfer.dropEffect = 'move';
    this.isDragOverSelected = true;
  }

  onDragLeaveSelected(event: DragEvent): void {
    const ct = event.currentTarget as HTMLElement;
    if (!ct.contains(event.relatedTarget as Node)) this.isDragOverSelected = false;
  }

  onDropSelected(event: DragEvent): void {
    event.preventDefault();
    this.isDragOverSelected = false;
    if (!this.draggedProduct || this.dragSource !== 'available') return;

    const idx = this.availableProducts.findIndex(p => p.id === this.draggedProduct!.id);
    if (idx !== -1) {
      this.availableProducts.splice(idx, 1);
      this.selectedProducts.push({ ...this.draggedProduct });
      this.buildCategoryGroups(); // keep accordion in sync
    }
    this.draggedProduct = null;
    this.dragSource = null;
  }

  // ── Drop zone — left panel (return) ────────────────────────────────────────

  onDragOverAvailable(event: DragEvent): void {
    event.preventDefault();
    if (event.dataTransfer) event.dataTransfer.dropEffect = 'move';
    this.isDragOverAvailable = true;
  }

  onDragLeaveAvailable(event: DragEvent): void {
    const ct = event.currentTarget as HTMLElement;
    if (!ct.contains(event.relatedTarget as Node)) this.isDragOverAvailable = false;
  }

  onDropAvailable(event: DragEvent): void {
    event.preventDefault();
    this.isDragOverAvailable = false;
    if (!this.draggedProduct || this.dragSource !== 'selected') return;

    const idx = this.selectedProducts.findIndex(p => p.id === this.draggedProduct!.id);
    if (idx !== -1) {
      this.selectedProducts.splice(idx, 1);
      this.clearFlagState(this.draggedProduct.id);
      this.availableProducts.push({ ...this.draggedProduct });
      this.buildCategoryGroups(); // keep accordion in sync
    }
    this.draggedProduct = null;
    this.dragSource = null;
  }

  // ── Reorder within selected ─────────────────────────────────────────────────

  onDropOnItem(event: DragEvent, targetProduct: ProductItem): void {
    event.preventDefault();
    event.stopPropagation();
    if (!this.draggedProduct || this.dragSource !== 'selected') return;
    if (this.draggedProduct.id === targetProduct.id) return;

    const fromIdx = this.selectedProducts.findIndex(p => p.id === this.draggedProduct!.id);
    const toIdx = this.selectedProducts.findIndex(p => p.id === targetProduct.id);
    if (fromIdx !== -1 && toIdx !== -1) {
      const [item] = this.selectedProducts.splice(fromIdx, 1);
      this.selectedProducts.splice(toIdx, 0, item);
    }
  }

  // ── Quick click actions ─────────────────────────────────────────────────────

  addProduct(product: ProductItem): void {
    const idx = this.availableProducts.findIndex(p => p.id === product.id);
    if (idx !== -1) {
      this.availableProducts.splice(idx, 1);
      this.selectedProducts.push({ ...product });
      this.buildCategoryGroups(); // keep accordion in sync
    }
  }

  removeProduct(product: ProductItem): void {
    const idx = this.selectedProducts.findIndex(p => p.id === product.id);
    if (idx !== -1) {
      this.selectedProducts.splice(idx, 1);
      this.clearFlagState(product.id);
      this.availableProducts.push({ ...product });
      this.buildCategoryGroups(); // keep accordion in sync
    }
  }

  clearSelected(): void {
    this.selectedProducts.forEach(p => this.clearFlagState(p.id));
    this.availableProducts.push(...this.selectedProducts);
    this.selectedProducts = [];
    this.buildCategoryGroups(); // keep accordion in sync
  }

  trackById(_: number, product: ProductItem): number {
    return product.id;
  }

  trackByCategory(_: number, group: CategoryGroup): string {
    return group.category;
  }

  set regionValue(value: string) {
    this.regionOptions = [];
    this.form.get('region').setValue(value);
  }

  set teamTypeValue(value: string) {
    this.teamTypeOptions = [];
    this.form.get('teamType').setValue(value);
  }

  ngAfterViewInit() {
    this.canGoBackGuard.markAsPristine();

    this.subscription.push(
      this.form.valueChanges
        .subscribe(() => this.canGoBackGuard.markAsDirty())
    );
  }

}
