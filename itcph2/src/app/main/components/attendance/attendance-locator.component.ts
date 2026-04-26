import { Component, OnDestroy, OnInit } from '@angular/core';
import { Subscription } from 'rxjs';
import { UntypedFormBuilder, UntypedFormGroup } from '@angular/forms';
import { finalize } from 'rxjs/operators';

import { FormService } from 'src/app/core/services/form.service';
import { environment } from 'src/environments/environment';
import { REQUEST_STATUS, STATIC_MODULES } from 'src/app/app.constants';
import { Functions } from 'src/app/core/utils/functions.list';
import { COMMON_VALIDATORS } from 'src/app/core/validators/validations.list';
import { DashboardData, DropdownList, GetAttendanceDataResponse, ViewAttendanceLocatorResponse } from 'src/app/core/interfaces/http-response.interface';
import { LoaderService } from 'src/app/core/services/loader.service';
import { MapConfig } from 'src/app/core/interfaces/common.interface';

@Component({
  templateUrl: './attendance-locator.component.html',
  standalone: false,
})
export class AttendanceLocatorComponent implements OnDestroy, OnInit {
  private subscription: Subscription[] = [];
  group!: UntypedFormGroup;
  total = 0;
  markers: MapConfig[] = [];
  districtOptions: DropdownList[] = [];
  branchOptions: DropdownList[] = [];
  teamOptions: DropdownList[] = [];
  teamTypeOptions: DropdownList[] = [];
  circleOptions: DropdownList[] = [];
  sectionOptions: DropdownList[] = [];
  wdCodeOptions: DropdownList[] = [];
  attendanceTimeOptions: DropdownList[] = [];
  errorMessages = {
    attendanceTime: COMMON_VALIDATORS.messages.requiredOnly('Attendance Time'),
    branch: COMMON_VALIDATORS.messages.dropdownAllOptional('Branch'),
    date: COMMON_VALIDATORS.messages.requiredOnly('Date'),
    team: COMMON_VALIDATORS.messages.dropdownAllOptional('Team Name'),
  };
  isDisabled = false;
  branchFilter = false

  constructor(private formService: FormService, private fb: UntypedFormBuilder, private loaderService: LoaderService) { }

  ngOnInit() {
    const currentDate = Functions.currentDate();
    this.group = this.fb.group({
      attendanceTime: ['0', COMMON_VALIDATORS.validators.requiredOnly],
      branch: ['', COMMON_VALIDATORS.validators.dropdownAllOptional],
      date: [currentDate, COMMON_VALIDATORS.validators.date],
      district: [''],
      teamType: [''],
      circle: [''],
      section: [''],
      wdCode: [''],
      dsName: ['', COMMON_VALIDATORS.validators.dropdownAllOptional],
    });

    this.loaderService.startLoader();
    this.subscription.push(
      this.formService.getData<GetAttendanceDataResponse>(environment.getAttendanceDataUrl)
        .pipe(
          finalize(() => this.loaderService.stopLoader()),
        )
        .subscribe(resp => {
          if (resp && resp.status === REQUEST_STATUS.SUCCESS && resp.data) {
            this.districtOptions = resp.data.districtList;
            this.branchOptions = resp.data.branchList;
            this.circleOptions = resp.data.circleList ?? [];
            this.sectionOptions = resp.data.sectionList ?? [];
            this.wdCodeOptions = resp.data.wdCodeList ?? [];
            this.teamOptions = resp.data.teamList;
            this.attendanceTimeOptions = resp.data.attendanceTimeList;
            this.branchFilter = resp.data.branchFilter ?? false;
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
      this.total = 0;
      this.markers = [];
      this.loaderService.startLoader();

      this.subscription.push(
        this.formService.getList<ViewAttendanceLocatorResponse>(environment.viewAttendanceLocatorUrl, this.group.getRawValue())
          .pipe(
            finalize(() => {
              this.isDisabled = false;
              this.loaderService.stopLoader();
            })
          )
          .subscribe(resp => {
            if (resp && resp.status === REQUEST_STATUS.SUCCESS && resp.data) {
              this.total = resp.data.total;
              this.markers = resp.data.markers;
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
    this.loaderService.startLoader();
    this.subscription.push(
      this.formService.customActionCall<DashboardData>(STATIC_MODULES.custom.getBranch, { district: this.group.get('district')?.value }, null, environment.getAttendanceDataUrl)
        .pipe(finalize(() => this.loaderService.stopLoader()))
        .subscribe(resp => {
          if (resp && resp.status === REQUEST_STATUS.SUCCESS && resp.data) {
            this.branchOptions = resp.data.branchList;
            this.circleOptions = resp.data.circleList;
            this.sectionOptions = resp.data.sectionList;
            this.wdCodeOptions = resp.data.wdCodeList;
            this.teamOptions = resp.data.teamList;
            this.teamTypeOptions = resp.data.teamType;
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
    this.loaderService.startLoader();
    this.subscription.push(
      this.formService.customActionCall<DashboardData>(STATIC_MODULES.custom.getCircle, { branch: this.group.get('branch')?.value },
        null, environment.getAttendanceDataUrl)
        .pipe(finalize(() => this.loaderService.stopLoader()))
        .subscribe(resp => {
          if (resp && resp.status === REQUEST_STATUS.SUCCESS && resp.data) {
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
      this.formService.customActionCall<DashboardData>(STATIC_MODULES.custom.getSection, { branch: this.group.get('branch')?.value, circle: this.group.get('circle')?.value },
        null, environment.getAttendanceDataUrl)
        .pipe(finalize(() => this.loaderService.stopLoader()))
        .subscribe(resp => {
          if (resp && resp.status === REQUEST_STATUS.SUCCESS && resp.data) {
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
      this.formService.customActionCall<DashboardData>(STATIC_MODULES.custom.getWDList, { branch: this.group.get('branch')?.value, circle: this.group.get('circle')?.value, section: this.group.get('section')?.value },
        null, environment.getAttendanceDataUrl)
        .pipe(finalize(() => this.loaderService.stopLoader()))
        .subscribe(resp => {
          if (resp && resp.status === REQUEST_STATUS.SUCCESS && resp.data) {
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
      this.formService.customActionCall<DashboardData>(STATIC_MODULES.custom.getTeamsTypeList, { branch: this.group.get('branch')?.value, circle: this.group.get('circle')?.value, section: this.group.get('section')?.value, wdCode: this.group.get('wdCode')?.value },
        null, environment.getAttendanceDataUrl)
        .pipe(finalize(() => this.loaderService.stopLoader()))
        .subscribe(resp => {
          if (resp && resp.status === REQUEST_STATUS.SUCCESS && resp.data) {
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
      this.formService.customActionCall<DashboardData>(STATIC_MODULES.custom.getTeamsList, { branch: this.group.get('branch')?.value, circle: this.group.get('circle')?.value, section: this.group.get('section')?.value, wdCode: this.group.get('wdCode')?.value, teamType: this.group.get('teamType')?.value },
        null, environment.getAttendanceDataUrl)
        .pipe(finalize(() => this.loaderService.stopLoader()))
        .subscribe(resp => {
          if (resp && resp.status === REQUEST_STATUS.SUCCESS && resp.data) {
            this.teamOptions = resp.data.teamList;
          }
        })
    );
  }


  set branchValue(value: string | null) {
    this.branchOptions = [];
    this.group.get('branch')?.setValue(value);
  }

  set circleValue(value: string | null) {
    this.circleOptions = [];
    this.group.get('circle')?.setValue(value);
  }
  set sectionValue(value: string | null) {
    this.sectionOptions = [];
    this.group.get('section')?.setValue(value);
  }
  set wdCodeValue(value: string | null) {
    this.wdCodeOptions = [];
    this.group.get('wdCode')?.setValue(value);
  }
  set dsTypeValue(value: string | null) {
    this.teamTypeOptions = [];
    this.group.get('teamType')?.setValue(value);
  }
  set dsNameValue(value: string | null) {
    this.teamOptions = [];
    this.group.get('dsName')?.setValue(value);
  }
}
