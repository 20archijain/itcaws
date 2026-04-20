import { Component, OnDestroy, OnInit } from '@angular/core';
import { Subscription } from 'rxjs';
import { UntypedFormBuilder, UntypedFormGroup } from '@angular/forms';
import { finalize } from 'rxjs/operators';

import { FormService } from 'src/app/core/services/form.service';
import { environment } from 'src/environments/environment';
import { REQUEST_STATUS, STATIC_MODULES } from 'src/app/app.constants';
import { Functions } from 'src/app/core/utils/functions.list';
import { COMMON_VALIDATORS } from 'src/app/core/validators/validations.list';
import { DashboardData, DropdownList, GetDownloadBillCutResponse, GetDownloadFileDetails } from 'src/app/core/interfaces/http-response.interface';
import { LoaderService } from 'src/app/core/services/loader.service';

@Component({
    templateUrl: './dowload-bill-cut.component.html',
    standalone: false
})
export class DowloadBillCutComponent implements OnDestroy, OnInit {
  private subscription: Subscription[] = [];
  group: UntypedFormGroup;
  branchOptions: DropdownList[] = [];
  productOptions: DropdownList[] = [];
  teamOptions: DropdownList[] = [];
  teamTypeOptions: DropdownList[] = [];
  circleOptions: DropdownList[] = [];
  sectionOptions: DropdownList[] = [];
  wdCodeOptions: DropdownList[] = [];
  districtOptions: DropdownList[] = [];
  wdMarketOptions: DropdownList[] = [];
  wdPopGroupOptions: DropdownList[] = [];
  errorMessages = {
    branch: COMMON_VALIDATORS.messages.requiredOnly('Branch'),
    dateFrom: COMMON_VALIDATORS.messages.requiredOnly('From'),
    dateTo: COMMON_VALIDATORS.messages.requiredOnly('To'),
  };
  isDisabled = false;
  url = environment.getUobReportDataUrl;

  constructor(private formService: FormService, private fb: UntypedFormBuilder, private loaderService: LoaderService) { }

  ngOnInit() {
    this.group = this.fb.group({
      branch: ['', COMMON_VALIDATORS.validators.requiredOnly],
      dateFrom: ['', COMMON_VALIDATORS.validators.date],
      dateTo: ['', COMMON_VALIDATORS.validators.date],
      circle: [''],
      section: [''],
      wdCode: [''],
      dsType: [''],
      dsName: [''],
      district: [''],
      wdMarket: [''],
      wdPopGroup: [''],
      product: ['']
    });

    this.loaderService.startLoader();
    this.subscription.push(
      this.formService.getData<GetDownloadBillCutResponse>(this.url)
        .pipe(
          finalize(() => this.loaderService.stopLoader()),
        )
        .subscribe(resp => {
          if (resp && resp.status === REQUEST_STATUS.SUCCESS) {
            this.districtOptions = resp.data.districtList;
            this.branchOptions = resp.data.branchList;
            this.circleOptions = resp.data.circleList;
            this.sectionOptions = resp.data.sectionList;
            this.wdCodeOptions = resp.data.wdCodeList;
            this.teamOptions = resp.data.teamList;
            this.teamTypeOptions = resp.data.teamType;
            this.wdMarketOptions = resp.data.wdMarketList;
            this.wdPopGroupOptions = resp.data.wdPopGroupList;
          }
        })
    );
  }

  ngOnDestroy() {
    this.subscription.forEach(sub => sub.unsubscribe());
  }

  download() {
    if (!this.isDisabled && this.group.valid) {
      this.isDisabled = true;
      this.loaderService.startLoader();
      this.subscription.push(
        this.formService.customActionCall<GetDownloadFileDetails>(STATIC_MODULES.custom.getDownloadData, this.group.getRawValue(),
          null, environment.downloadExcelUrl)
          .pipe(
            finalize(() => {
              this.isDisabled = false;
              this.loaderService.stopLoader();
            })
          )
          .subscribe(resp => {
            if (resp && resp.status === REQUEST_STATUS.SUCCESS) {
              Functions.downloadFile(resp.data.filePath, resp.data.fileName);
            }
          })
      );
    }
  }

    getBranch() {
      this.branchValue = null;
      this.circleValue = null;
      this.sectionValue = null;
      this.wdCodeValue = null;
      this.dsTypeValue = null;
      this.dsNameValue = null;
      this.wdMarketValue = null;
      this.wdPopGroupValue = null;
      this.loaderService.startLoader();
      this.subscription.push(
        this.formService.customActionCall<DashboardData>(STATIC_MODULES.custom.getBranch, { district: this.group.get('district').value }, null, environment.viewVanDsDataUrl)
          .pipe(finalize(() => this.loaderService.stopLoader()))
          .subscribe(resp => {
            if (resp && resp.status === REQUEST_STATUS.SUCCESS) {
              this.branchOptions = resp.data.branchList;
              this.circleOptions = resp.data.circleList;
              this.sectionOptions = resp.data.sectionList;
              this.wdCodeOptions = resp.data.wdCodeList;
              this.teamOptions = resp.data.teamList;
              this.teamTypeOptions = resp.data.teamType;
              this.wdMarketOptions = resp.data.wdMarketList;
              this.wdPopGroupOptions = resp.data.wdPopGroupList;
            }
          })
      );
    }

    getCircle() {
      this.circleValue = null;
      this.sectionValue = null;
      this.wdCodeValue = null;
      this.dsTypeValue = null;
      this.dsNameValue = null;
      this.wdMarketValue = null;
      this.wdPopGroupValue = null;
      this.productValue = null;
      this.loaderService.startLoader();
      this.subscription.push(
        this.formService.customActionCall<DashboardData>(STATIC_MODULES.custom.getCircle, { branch: this.group.get('branch').value }, null, environment.viewVanDsDataUrl)
          .pipe(finalize(() => this.loaderService.stopLoader()))
          .subscribe(resp => {
            if (resp && resp.status === REQUEST_STATUS.SUCCESS) {
              this.circleOptions = resp.data.circleList;
              this.sectionOptions = resp.data.sectionList;
              this.wdCodeOptions = resp.data.wdCodeList;
              this.teamOptions = resp.data.teamList;
              this.teamTypeOptions = resp.data.teamType;
              this.wdMarketOptions = resp.data.wdMarketList;
              this.wdPopGroupOptions = resp.data.wdPopGroupList;
              this.productOptions = resp.data.productList;
            }
          })
      );
    }

    getSection() {
      this.sectionValue = null;
      this.wdCodeValue = null;
      this.dsTypeValue = null;
      this.dsNameValue = null;
      this.wdMarketValue = null;
      this.wdPopGroupValue = null;
      this.loaderService.startLoader();
      this.subscription.push(
        this.formService.customActionCall<DashboardData>(STATIC_MODULES.custom.getSection, { branch: this.group.get('branch').value, circle: this.group.get('circle').value }, null, environment.viewVanDsDataUrl)
          .pipe(finalize(() => this.loaderService.stopLoader()))
          .subscribe(resp => {
            if (resp && resp.status === REQUEST_STATUS.SUCCESS) {
              this.sectionOptions = resp.data.sectionList;
              this.wdCodeOptions = resp.data.wdCodeList;
              this.teamOptions = resp.data.teamList;
              this.teamTypeOptions = resp.data.teamType;
              this.wdMarketOptions = resp.data.wdMarketList;
              this.wdPopGroupOptions = resp.data.wdPopGroupList;
            }
          })
      );
    }

    getWDCode() {
      this.wdCodeValue = null;
      this.dsTypeValue = null;
      this.dsNameValue = null;
      this.wdMarketValue = null;
      this.wdPopGroupValue = null;
      this.loaderService.startLoader();
      this.subscription.push(
        this.formService.customActionCall<DashboardData>(STATIC_MODULES.custom.getWDList, { branch: this.group.get('branch').value, circle: this.group.get('circle').value, section: this.group.get('section').value }, null, environment.viewVanDsDataUrl)
          .pipe(finalize(() => this.loaderService.stopLoader()))
          .subscribe(resp => {
            if (resp && resp.status === REQUEST_STATUS.SUCCESS) {
              this.wdCodeOptions = resp.data.wdCodeList;
              this.teamOptions = resp.data.teamList;
              this.teamTypeOptions = resp.data.teamType;
              this.wdMarketOptions = resp.data.wdMarketList;
              this.wdPopGroupOptions = resp.data.wdPopGroupList;
            }
          })
      );
    }

    getTeamsType() {
      this.dsTypeValue = null;
      this.dsNameValue = null;
      this.loaderService.startLoader();
      this.subscription.push(
        this.formService.customActionCall<DashboardData>(STATIC_MODULES.custom.getTeamsTypeList, { branch: this.group.get('branch').value, circle: this.group.get('circle').value, section: this.group.get('section').value, wdCode: this.group.get('wdCode').value }, null, environment.viewVanDsDataUrl)
          .pipe(finalize(() => this.loaderService.stopLoader()))
          .subscribe(resp => {
            if (resp && resp.status === REQUEST_STATUS.SUCCESS) {
              this.teamTypeOptions = resp.data.teamType;
              this.teamOptions = resp.data.teamList;
            }
          })
      );
    }

    getTeamsName() {
      this.dsNameValue = null;
      this.loaderService.startLoader();
      this.subscription.push(
        this.formService.customActionCall<DashboardData>(STATIC_MODULES.custom.getTeamsList, { branch: this.group.get('branch').value, circle: this.group.get('circle').value, section: this.group.get('section').value, wdCode: this.group.get('wdCode').value, dsType: this.group.get('dsType').value }, null, environment.viewVanDsDataUrl)
          .pipe(finalize(() => this.loaderService.stopLoader()))
          .subscribe(resp => {
            if (resp && resp.status === REQUEST_STATUS.SUCCESS) {
              this.teamOptions = resp.data.teamList;
            }
          })
      );
    }

  // getProducts() {
  //   this.group.get('product').setValue('')
  //   this.loaderService.startLoader();
  //   this.subscription.push(
  //     this.formService.customActionCall<GetDownloadBillCutResponse>('get_product_list', { type: this.group.get('teamType').value, branch: this.group.get('branch').value }, null, this.url)
  //       .pipe(
  //         finalize(() => this.loaderService.stopLoader())
  //       )
  //       .subscribe(resp => {
  //         if (resp && resp.status === REQUEST_STATUS.SUCCESS) {
  //           this.productOptions = resp.data.productList;
  //         }
  //       })
  //   );
  // }

  get branchValue() {
    return this.group && this.group.get('branch').value;
  }

  set branchValue(value: string) {
    this.branchOptions = [];
    this.group.get('branch').setValue(value);
  }
  set circleValue(value: string) {
    this.circleOptions = [];
    this.group.get('circle').setValue(value);
  }
  set sectionValue(value: string) {
    this.sectionOptions = [];
    this.group.get('section').setValue(value);
  }
  set wdCodeValue(value: string) {
    this.wdCodeOptions = [];
    this.group.get('wdCode').setValue(value);
  }
  set dsTypeValue(value: string) {
    this.teamTypeOptions = [];
    this.group.get('dsType').setValue(value);
  }
  set dsNameValue(value: string) {
    this.teamOptions = [];
    this.group.get('dsName').setValue(value);
  }
  set wdMarketValue(value: string) {
    this.wdMarketOptions = [];
    this.group.get('wdMarket').setValue(value);
  }
  set wdPopGroupValue(value: string) {
    this.wdPopGroupOptions = [];
    this.group.get('wdPopGroup').setValue(value);
  }
  set productValue(value: string) {
    this.productOptions = [];
    this.group.get('product').setValue(value);
  }

}

