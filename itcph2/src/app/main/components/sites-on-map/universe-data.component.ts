import { Component, OnDestroy, OnInit } from '@angular/core';
import { UntypedFormBuilder, UntypedFormGroup } from '@angular/forms';
import { Subscription } from 'rxjs';
import { finalize } from 'rxjs/operators';

import { FormService } from 'src/app/core/services/form.service';
import { REQUEST_STATUS, STATIC_MODULES } from 'src/app/app.constants';
import { MapConfig } from 'src/app/core/interfaces/common.interface';
import { COMMON_VALIDATORS } from 'src/app/core/validators/validations.list';
import { DashboardData, DropdownList, GetLocationCoveredResponse } from 'src/app/core/interfaces/http-response.interface';
import { environment } from 'src/environments/environment';
import { LoaderService } from 'src/app/core/services/loader.service';

@Component({
  templateUrl: './universe-data.component.html',
  styleUrls: ["./sites-on-map.component.scss"],
})
export class UniverseDataComponent implements OnDestroy, OnInit {
  private subscription: Subscription[] = [];
  url = environment.getOutletUniverseDataUrl;
  group: UntypedFormGroup;
  branchOptions: DropdownList[] = [];
  teamOptions: DropdownList[] = [];
  teamTypeOptions: DropdownList[] = [];
  rmdNameOptions: DropdownList[] = [];
  circleOptions: DropdownList[] = [];
  sectionOptions: DropdownList[] = [];
  wdCodeOptions: DropdownList[] = [];
  columnSize = 12;
  noOfMaps: string[] = [];
  markers: MapConfig[] = [];
  isBranchRequired = false;
  isTeamRequired = false;
  isTeamTypeRequired = false;
  // isDateRequired = true;
  isRmdNameRequired = false;
  branchFilter = false;
  // errorMessages = {
  //   dateFrom: COMMON_VALIDATORS.messages.requiredOnly('From'),
  //   dateTo: COMMON_VALIDATORS.messages.requiredOnly('To'),
  //   // branch: COMMON_VALIDATORS.messages.dropdownAll('Branch'),
  // };

  constructor(private formService: FormService, private fb: UntypedFormBuilder, private loaderService: LoaderService) { }

  ngOnInit() {
    this.group = this.fb.group({
      // dateFrom: ['', COMMON_VALIDATORS.validators.date],
      // dateTo: ['', COMMON_VALIDATORS.validators.date],
      rmdName: ['', this.isRmdNameRequired ? COMMON_VALIDATORS.validators.dropdownStringValue : []],
      searchbar: this.fb.group({
        branch: [''],
        circle: [''],
        section: [''],
        wdCode: [''],
        dsType: [''],
        dsName: [''],
      })
    });

    this.initialData();
  }

  ngOnDestroy() {
    this.subscription.forEach(sub => sub.unsubscribe());
  }

  initialData() {
    this.loaderService.startLoader();
    this.subscription.push(
      this.formService.getData<GetLocationCoveredResponse>(this.url)
        .pipe(
          finalize(() => this.loaderService.stopLoader()),
        )
        .subscribe(resp => {
          if (resp && resp.status === REQUEST_STATUS.SUCCESS) {
            this.branchOptions = resp.data.branchList;
            this.circleOptions = resp.data.circleList;
            this.sectionOptions = resp.data.sectionList;
            this.wdCodeOptions = resp.data.wdCodeList;
            this.teamTypeOptions = resp.data.teamType;
            this.teamOptions = resp.data.teamList;
            this.branchFilter = resp.data.branchFilter
          }
        })
    );
  }

  getData() {
    if (this.group.valid) {
      this.markers = [];
      this.loaderService.startLoader();

      this.subscription.push(
        this.formService.getList<GetLocationCoveredResponse>(this.url, this.group.getRawValue())
          .pipe(
            finalize(() => this.loaderService.stopLoader()),
          )
          .subscribe(resp => {
            if (resp && resp.status === REQUEST_STATUS.SUCCESS) {

              // column size of map
              this.columnSize = +resp.data.columnSize || 12;
              // repeat map by given number
              this.noOfMaps = Array(+resp.data.repeatMapBy || 1).fill('');

              if (resp.data.markers && resp.data.markers.length > 0) {
                this.markers = resp.data.markers.map(marker => {
                  return { ...marker, latitude: +marker.latitude, longitude: +marker.longitude };
                });
              }
            }
          })
      );
    }
  }

  getCircle() {
    this.circleValue = null;
    this.sectionValue = null;
    this.wdCodeValue = null;
    this.dsTypeValue = null;
    this.dsNameValue = null;
    this.loaderService.startLoader();
    this.subscription.push(
      this.formService.customActionCall<DashboardData>(STATIC_MODULES.custom.getCircle, { branch: this.group.get('searchbar').get('branch').value },
        null, this.url)
        .pipe(finalize(() => this.loaderService.stopLoader()))
        .subscribe(resp => {
          if (resp && resp.status === REQUEST_STATUS.SUCCESS) {
            this.circleOptions = resp.data.circleList;
            this.sectionOptions = resp.data.sectionList;
            this.wdCodeOptions = resp.data.wdCodeList;
            this.teamOptions = resp.data.teamList;
            this.teamTypeOptions = resp.data.teamType;
          }
        })
    );
  }

  getSection() {
    this.sectionValue = null;
    this.wdCodeValue = null;
    this.dsTypeValue = null;
    this.dsNameValue = null;
    this.loaderService.startLoader();
    this.subscription.push(
      this.formService.customActionCall<DashboardData>(STATIC_MODULES.custom.getSection, { branch: this.group.get('searchbar').get('branch').value, circle: this.group.get('searchbar').get('circle').value },
        null, this.url)
        .pipe(finalize(() => this.loaderService.stopLoader()))
        .subscribe(resp => {
          if (resp && resp.status === REQUEST_STATUS.SUCCESS) {
            this.sectionOptions = resp.data.sectionList;
            this.wdCodeOptions = resp.data.wdCodeList;
            this.teamOptions = resp.data.teamList;
            this.teamTypeOptions = resp.data.teamType;
          }
        })
    );
  }

  getWDCode() {
    this.wdCodeValue = null;
    this.dsTypeValue = null;
    this.dsNameValue = null;
    this.loaderService.startLoader();
    this.subscription.push(
      this.formService.customActionCall<DashboardData>(STATIC_MODULES.custom.getWDList, { branch: this.group.get('searchbar').get('branch').value, circle: this.group.get('searchbar').get('circle').value, section: this.group.get('searchbar').get('section').value },
        null, this.url)
        .pipe(finalize(() => this.loaderService.stopLoader()))
        .subscribe(resp => {
          if (resp && resp.status === REQUEST_STATUS.SUCCESS) {
            this.wdCodeOptions = resp.data.wdCodeList;
            this.teamOptions = resp.data.teamList;
            this.teamTypeOptions = resp.data.teamType;
          }
        })
    );
  }

  getTeamsType() {
    this.dsTypeValue = null;
    this.dsNameValue = null;
    this.loaderService.startLoader();
    this.subscription.push(
      this.formService.customActionCall<DashboardData>(STATIC_MODULES.custom.getTeamsTypeList, { branch: this.group.get('searchbar').get('branch').value, circle: this.group.get('searchbar').get('circle').value, section: this.group.get('searchbar').get('section').value, wdCode: this.group.get('searchbar').get('wdCode').value },
        null, this.url)
        .pipe(finalize(() => this.loaderService.stopLoader()))
        .subscribe(resp => {
          if (resp && resp.status === REQUEST_STATUS.SUCCESS) {
            this.teamTypeOptions = resp.data.teamType;
            this.teamOptions = resp.data.teamList;
          }
        })
    );
  }

  getTeams() {
    this.dsNameValue = null;
    this.loaderService.startLoader();
    this.subscription.push(
      this.formService.customActionCall<DashboardData>(STATIC_MODULES.custom.getTeamsList, { branch: this.group.get('searchbar').get('branch').value, circle: this.group.get('searchbar').get('circle').value, section: this.group.get('searchbar').get('section').value, wdCode: this.group.get('searchbar').get('wdCode').value, teamType: this.group.get('searchbar').get('dsType').value },
        null, this.url)
        .pipe(finalize(() => this.loaderService.stopLoader()))
        .subscribe(resp => {
          if (resp && resp.status === REQUEST_STATUS.SUCCESS) {
            this.teamOptions = resp.data.teamList;
          }
        })
    );
  }

  get branchValue() {
    return this.group && this.group.get('branch').value;
  }

  set branchValue(value: string) {
    this.branchOptions = [];
    this.group.get('searchbar').get('branch').setValue(value);
  }

  // set dateValue(value: string) {
  //   this.group.get('date').setValue(value);
  // }

  set circleValue(value: string) {
    this.circleOptions = [];
    this.group.get('searchbar').get('circle').setValue(value);
  }
  set sectionValue(value: string) {
    this.sectionOptions = [];
    this.group.get('searchbar').get('section').setValue(value);
  }
  set wdCodeValue(value: string) {
    this.wdCodeOptions = [];
    this.group.get('searchbar').get('wdCode').setValue(value);
  }
  set dsTypeValue(value: string) {
    this.teamTypeOptions = [];
    this.group.get('searchbar').get('dsType').setValue(value);
  }
  set dsNameValue(value: string) {
    this.teamOptions = [];
    this.group.get('searchbar').get('dsName').setValue(value);
  }

  //minToDate: string | null = null;  // Initialize with null or any default

  // Method called when the "From" date changes
  // onFromDateChange(selectedDate: string) {
  //   this.minToDate = selectedDate;  // Set minToDate to the selected "From" date
  // }
}
