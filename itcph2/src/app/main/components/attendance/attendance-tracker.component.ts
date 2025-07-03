import { Component, OnDestroy, OnInit } from '@angular/core';
import { Subscription } from 'rxjs';
import { UntypedFormBuilder, UntypedFormGroup } from '@angular/forms';
import { finalize } from 'rxjs/operators';

import { CustomGalleryConfig, GalleryImagesList } from 'src/app/core/interfaces/common.interface';
import { FormService } from 'src/app/core/services/form.service';
import { environment } from 'src/environments/environment';
import { REQUEST_STATUS, STATIC_MODULES } from 'src/app/app.constants';
import { Functions } from 'src/app/core/utils/functions.list';
import { COMMON_VALIDATORS } from 'src/app/core/validators/validations.list';
import { DashboardData, DropdownList, GetAttendanceDataResponse, ViewAttendanceTrackerResponse } from 'src/app/core/interfaces/http-response.interface';
import { LoaderService } from 'src/app/core/services/loader.service';
import { CsvDataFormat } from 'src/app/core/interfaces/helpers.interface';

@Component({
  templateUrl: './attendance-tracker.component.html'
})
export class AttendanceTrackerComponent implements OnDestroy, OnInit {
  private subscription: Subscription[] = [];
  group: UntypedFormGroup;
  totalPresent = 0;
  totalTeams = 0;
  images: GalleryImagesList[] = [];
  branchOptions: DropdownList[] = [];
  teamOptions: DropdownList[] = [];
  teamTypeOptions: DropdownList[] = [];
  attendanceTimeOptions: DropdownList[] = [];
  circleOptions: DropdownList[] = [];
  sectionOptions: DropdownList[] = [];
  wdCodeOptions: DropdownList[] = [];
  yearList: DropdownList<number, number>[] = [];
  monthList: DropdownList[] = [];
  errorMessages = {
    attendanceTime: COMMON_VALIDATORS.messages.requiredOnly('Attendance Time'),
    branch: COMMON_VALIDATORS.messages.dropdownAllOptional('Branch'),
    date: COMMON_VALIDATORS.messages.requiredOnly('Date'),
    team: COMMON_VALIDATORS.messages.dropdownAllOptional('Team Name'),
    // teamType: COMMON_VALIDATORS.messages.dropdownAllOptional('Team Type'),
  };
  cgConfig: CustomGalleryConfig;
  deleteUrl = environment.deleteDataUrl;
  isDisabled = false;
  showAsUserCard = false;
  branchFilter = false

  constructor(private formService: FormService, private fb: UntypedFormBuilder, private loaderService: LoaderService) { }

  ngOnInit() {
    const currentDate = Functions.currentDate();
    this.group = this.fb.group({
      attendanceTime: ['0', COMMON_VALIDATORS.validators.requiredOnly],
      branch: ['', COMMON_VALIDATORS.validators.dropdownAllOptional],
      date: [currentDate, COMMON_VALIDATORS.validators.date],
      month: [''],
      year: [currentDate.year],
      circle: [''],
      section: [''],
      wdCode: [''],
      teamType: [''],
      dsName: ['', COMMON_VALIDATORS.validators.dropdownAllOptional],
    });

    this.loaderService.startLoader();
    this.subscription.push(
      this.formService.getData<GetAttendanceDataResponse>(environment.getAttendanceDataUrl)
        .pipe(
          finalize(() => this.loaderService.stopLoader()),
        )
        .subscribe(resp => {
          if (resp && resp.status === REQUEST_STATUS.SUCCESS) {
            this.showAsUserCard = resp.data.showAsUserCard;
            this.cgConfig = resp.data.cgConfig;
            this.branchOptions = resp.data.branchList;
            this.circleOptions = resp.data.circleList;
            this.sectionOptions = resp.data.sectionList;
            this.wdCodeOptions = resp.data.wdCodeList;
            this.monthList = resp.data.monthList;
            this.yearList = resp.data.yearList;
            this.teamOptions = resp.data.teamList;
            this.teamTypeOptions = resp.data.teamType;
            this.attendanceTimeOptions = resp.data.attendanceTimeList;
            this.branchFilter = resp.data.branchFilter
          }
        })
    );
  }

  ngOnDestroy() {
    this.subscription.forEach(sub => sub.unsubscribe());
  }

  getAttendance() {
    if (this.group.valid && !this.isDisabled) {
      this.isDisabled = true;
      this.totalPresent = 0;
      this.totalTeams = 0;
      this.images = [];
      this.loaderService.startLoader();

      this.subscription.push(
        this.formService.getList<ViewAttendanceTrackerResponse>(environment.viewAttendanceTrackerUrl, this.group.getRawValue())
          .pipe(
            finalize(() => {
              this.isDisabled = false;
              this.loaderService.stopLoader();
            })
          )
          .subscribe(resp => {
            if (resp) {
              this.totalPresent = resp.data.totalPresent;
              this.totalTeams = resp.data.totalTeams;
              this.images = resp.data.images;
            }
          })
      );
    }
  }

  download() {
    if (!this.isDisabled) {
      this.isDisabled = true;
      this.loaderService.startLoader();
      this.subscription.push(
        this.formService.customActionCall<CsvDataFormat>(STATIC_MODULES.custom.getDownloadData, this.group.getRawValue(),
          null, environment.downloadAttendanceUrl)
          .pipe(
            finalize(() => {
              this.isDisabled = false;
              this.loaderService.stopLoader();
            })
          )
          .subscribe(resp => {
            if (resp && resp.status === REQUEST_STATUS.SUCCESS) {
              Functions.createCSV(resp.data);
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
      this.formService.customActionCall<DashboardData>(STATIC_MODULES.custom.getCircle, { branch: this.group.get('branch').value },
        null, environment.getAttendanceDataUrl)
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
      this.formService.customActionCall<DashboardData>(STATIC_MODULES.custom.getSection, { branch: this.group.get('branch').value, circle: this.group.get('circle').value },
        null, environment.getAttendanceDataUrl)
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
      this.formService.customActionCall<DashboardData>(STATIC_MODULES.custom.getWDList, { branch: this.group.get('branch').value, circle: this.group.get('circle').value, section: this.group.get('section').value },
        null, environment.getAttendanceDataUrl)
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
      this.formService.customActionCall<DashboardData>(STATIC_MODULES.custom.getTeamsTypeList, { branch: this.group.get('branch').value, circle: this.group.get('circle').value, section: this.group.get('section').value, wdCode: this.group.get('wdCode').value },
        null, environment.getAttendanceDataUrl)
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
      this.formService.customActionCall<DashboardData>(STATIC_MODULES.custom.getTeamsList, { branch: this.group.get('branch').value, circle: this.group.get('circle').value, section: this.group.get('section').value, wdCode: this.group.get('wdCode').value, teamType: this.group.get('teamType').value },
        null, environment.getAttendanceDataUrl)
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
    this.group.get('teamType').setValue(value);
  }
  set dsNameValue(value: string) {
    this.teamOptions = [];
    this.group.get('dsName').setValue(value);
  }

  set teamTypeValue(value: string) {
    this.teamTypeOptions = [];
    this.group.get('teamType').setValue(value);
  }

  // set teamValue(value: string[]) {
  //   this.teamOptions = [];
  //   this.group.get('team').setValue(value);
  // }
}
