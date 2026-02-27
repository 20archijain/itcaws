import { Component, OnInit } from '@angular/core';
import { UntypedFormBuilder, UntypedFormGroup } from '@angular/forms';
import { Subscription } from 'rxjs';
import { finalize } from 'rxjs/operators';

import { FormService } from 'src/app/core/services/form.service';
import { LoaderService } from 'src/app/core/services/loader.service';
import { GetProductSelectorDataResponse,
  ProductItem
} from 'src/app/core/interfaces/http-response.interface';
import { REQUEST_STATUS } from 'src/app/app.constants';
import { environment } from 'src/environments/environment';
import { COMMON_VALIDATORS } from 'src/app/core/validators/validations.list';

export interface CategoryGroup {
  category: string;
  products: ProductItem[];
}

@Component({
  templateUrl: './allocationReport.component.html',
})
export class AllocationReportComponent implements OnInit {

  private subscription: Subscription[] = [];

  form: UntypedFormGroup;
  tableData = [];
  header: string[] = [];
  body: string[] = [];
  isSelectable: boolean;
  statusFlagCond = false;
  defaultDspm = false;
  submittedDataList = [];

  url = environment.getActiveVariantsDataUrl;

  errorMessages = {
    main_branch: COMMON_VALIDATORS.messages.requiredOnly('Branch'),
    region: COMMON_VALIDATORS.messages.requiredOnly('Region'),
    teamType: COMMON_VALIDATORS.messages.requiredOnly('Team Type'),
  };

  constructor(
    private formService: FormService,
    private loaderService: LoaderService,
    private fb: UntypedFormBuilder,
  ) { }

  ngOnInit() {
    this.form = this.fb.group({
      branch: [],
    });
    this.getDefaultData();
  }

  getDefaultData() {
      this.loaderService.startLoader();
      this.subscription.push(
        this.formService.getData<GetProductSelectorDataResponse>(this.url, this.form.getRawValue())
          .pipe(finalize(() => this.loaderService.stopLoader()))
          .subscribe(resp => {
            if (resp && resp.status === REQUEST_STATUS.SUCCESS) {
              this.tableData = resp.data.tableData;
            }
          })
      );

  }
}
