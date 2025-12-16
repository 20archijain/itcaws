import { Component, OnDestroy, OnInit } from '@angular/core';
import { UntypedFormArray, UntypedFormBuilder, UntypedFormGroup, Validators } from '@angular/forms';
import { finalize } from 'rxjs/operators';
import { Subscription } from 'rxjs';

import { REQUEST_STATUS, STATIC_MODULES } from 'src/app/app.constants';
import { DownloadReports, DropdownList, GetDownloadFileDetails } from 'src/app/core/interfaces/http-response.interface';
import { FormService } from 'src/app/core/services/form.service';
import { LoaderService } from 'src/app/core/services/loader.service';
import { Functions } from 'src/app/core/utils/functions.list';
import { COMMON_VALIDATORS } from 'src/app/core/validators/validations.list';
import { environment } from 'src/environments/environment';

@Component({
  styleUrls: [
    './download-db-table.component.scss',
  ],
  templateUrl: './download-db-table.component.html',
})
export class DownloadDBTableComponent implements OnDestroy, OnInit {
  private subscription: Subscription[] = [];
  form: UntypedFormGroup;
  dataBaseListOptions: DropdownList[] = [];
  typeOptions: DropdownList[] = [];
  projectOptions: DropdownList[] = [];
  tableOptions: DropdownList[] = [];
  columnOptions: DropdownList[] = [];
  operatorOptions: DropdownList[] = [];
  logicalOperatorOptions: DropdownList[] = [];
  searchValue: any;
  url = environment.dashboardDataUrl;

  errorMessages = {
    database: COMMON_VALIDATORS.messages.requiredOnly('Database'),
    table: COMMON_VALIDATORS.messages.requiredOnly('Table'),
    column: COMMON_VALIDATORS.messages.requiredOnly('Column'),
    operator: COMMON_VALIDATORS.messages.requiredOnly('Operator'),
    value: COMMON_VALIDATORS.messages.requiredOnly('Value'),
  };

  constructor(
    protected formService: FormService,
    private fb: UntypedFormBuilder,
    protected loaderService: LoaderService
  ) { }

  ngOnInit() {
    this.form = this.fb.group({
      dateRange: this.fb.group({
        from: [''],
        to: ['']
      }),
      database: [null, COMMON_VALIDATORS.validators.requiredOnly],
      table: [null, COMMON_VALIDATORS.validators.requiredOnly],
      projectId: [''],
      type: ['data'],
      conditions: this.fb.array([])
    });

    this.getInitialData();
  }

  get conditions(): UntypedFormArray {
    return this.form.get('conditions') as UntypedFormArray;
  }

  getInitialData() {
    this.loaderService.startLoader();
    this.subscription.push(
      this.formService
        .getData<DownloadReports>(this.url)
        .pipe(finalize(() => this.loaderService.stopLoader()))
        .subscribe((resp) => {
          if (resp && resp.status === REQUEST_STATUS.SUCCESS) {
            this.dataBaseListOptions = resp.data.dataBaseList;
            this.typeOptions = resp.data.typeList;
            this.operatorOptions = resp.data.operatorList;
            this.logicalOperatorOptions = resp.data.logicalOperatorList;
          }
        })
    );
  }

  getTables() {
    this.form.get('table')?.setValue('');
    this.conditions.clear();
    this.loaderService.startLoader();
    this.subscription.push(
      this.formService.customActionCall<DownloadReports>(
        STATIC_MODULES.custom.getTables,
        { database: this.form.get('database')?.value }
      )
        .pipe(finalize(() => this.loaderService.stopLoader()))
        .subscribe(resp => {
          if (resp && resp.status === REQUEST_STATUS.SUCCESS) {
            this.tableOptions = resp.data.tableList;
            this.projectOptions = resp.data.projectList;
          }
        })
    );
  }

  getColumns() {
    this.conditions.clear();
    if (!this.form.get('database')?.value || !this.form.get('table')?.value) {
      return;
    }

    this.loaderService.startLoader();
    this.subscription.push(
      this.formService.customActionCall<any>(
        STATIC_MODULES.custom.getTableColumns,
        {
          database: this.form.get('database')?.value,
          table: this.form.get('table')?.value
        }
      )
        .pipe(finalize(() => this.loaderService.stopLoader()))
        .subscribe(resp => {
          if (resp && resp.status === REQUEST_STATUS.SUCCESS) {
            this.columnOptions = resp.data.columnList;
          }
        })
    );
  }

  getProjectId() {
    this.loaderService.startLoader();
    this.subscription.push(
      this.formService.customActionCall<DownloadReports>(
        STATIC_MODULES.custom.getProjectsList,
        {
          database: this.form.get('database')?.value,
          table: this.form.get('table')?.value
        }
      )
        .pipe(finalize(() => this.loaderService.stopLoader()))
        .subscribe(resp => {
          if (resp && resp.status === REQUEST_STATUS.SUCCESS) {
            this.projectOptions = resp.data.projectList;
          }
        })
    );
  }

  addCondition() {
    const conditionGroup = this.fb.group({
      logicalOperator: ['AND'],
      column: ['', Validators.required],
      operator: ['=', Validators.required],
      value: [''],
      value2: [''] // For BETWEEN operator
    });

    this.conditions.push(conditionGroup);
  }

  removeCondition(index: number) {
    this.conditions.removeAt(index);
  }

  onOperatorChange(index: number) {
    const condition = this.conditions.at(index);
    const operator = condition.get('operator')?.value;

    // Clear values when operator changes
    condition.get('value')?.setValue('');
    condition.get('value2')?.setValue('');

    // Set validation based on operator
    if (operator === 'IS NULL' || operator === 'IS NOT NULL') {
      condition.get('value')?.clearValidators();
      condition.get('value2')?.clearValidators();
    } else if (operator === 'BETWEEN') {
      condition.get('value')?.setValidators([Validators.required]);
      condition.get('value2')?.setValidators([Validators.required]);
    } else {
      condition.get('value')?.setValidators([Validators.required]);
      condition.get('value2')?.clearValidators();
    }

    condition.get('value')?.updateValueAndValidity();
    condition.get('value2')?.updateValueAndValidity();
  }

  shouldShowValue(operator: string): boolean {
    return operator !== 'IS NULL' && operator !== 'IS NOT NULL';
  }

  shouldShowValue2(operator: string): boolean {
    return operator === 'BETWEEN';
  }

  getOperatorLabel(value: string): string {
    const operator = this.operatorOptions.find(op => op.value === value);
    return operator ? operator.label : value;
  }

  ngOnDestroy() {
    this.subscription.forEach((sub) => sub.unsubscribe());
  }

  clearFilters() {
    this.form.reset({
      database: null,
      table: null,
      projectId: '',
      type: 'data',
      dateRange: {
        from: '',
        to: ''
      }
    });
    this.conditions.clear();
    this.columnOptions = [];
    this.tableOptions = [];
    this.projectOptions = [];
    this.getInitialData();
  }

  download() {
    if (this.form.valid) {
      const formData = this.form.getRawValue();

      // Validate conditions if any exist
      let hasValidConditions = true;
      if (formData.conditions && formData.conditions.length > 0) {
        hasValidConditions = formData.conditions.every((condition: any) => {
          if (!condition.column || !condition.operator) {
            return false;
          }

          const operator = condition.operator;
          if (operator === 'IS NULL' || operator === 'IS NOT NULL') {
            return true;
          } else if (operator === 'BETWEEN') {
            return condition.value && condition.value2;
          } else {
            return condition.value !== null && condition.value !== undefined && condition.value !== '';
          }
        });
      }

      if (!hasValidConditions) {
        // Show error message for invalid conditions
        console.error('Please complete all condition fields');
        return;
      }

      this.loaderService.startLoader();
      this.subscription.push(
        this.formService.customActionCall<GetDownloadFileDetails>(
          STATIC_MODULES.custom.getDownloadData,
          formData,
          null,
          environment.downloadAttendanceUrl
        )
          .pipe(finalize(() => this.loaderService.stopLoader()))
          .subscribe(resp => {
            if (resp && resp.status === REQUEST_STATUS.SUCCESS) {
              Functions.downloadFile(resp.data.filePath, resp.data.fileName);
            }
          })
      );
    }
  }

  previewQuery() {
    if (!this.form.get('database')?.value || !this.form.get('table')?.value) {
      return;
    }

    const formData = this.form.getRawValue();
    let query = `SELECT * FROM \`${formData.database}\`.\`${formData.table}\``;

    // Add WHERE conditions
    if (formData.conditions && formData.conditions.length > 0) {
      const whereConditions = formData.conditions
        .filter((condition: any) => condition.column && condition.operator)
        .map((condition: any, index: number) => {
          let conditionStr = '';

          if (index > 0) {
            conditionStr += ` ${condition.logicalOperator} `;
          }

          const column = condition.column;
          const operator = condition.operator;

          switch (operator) {
            case 'IS NULL':
            case 'IS NOT NULL':
              conditionStr += `\`${column}\` ${operator}`;
              break;
            case 'BETWEEN':
              conditionStr += `\`${column}\` BETWEEN '${condition.value}' AND '${condition.value2}'`;
              break;
            case 'IN':
            case 'NOT IN':
              conditionStr += `\`${column}\` ${operator} ('${condition.value}')`;
              break;
            case 'LIKE':
            case 'NOT LIKE':
              conditionStr += `\`${column}\` ${operator} '%${condition.value}%'`;
              break;
            default:
              conditionStr += `\`${column}\` ${operator} '${condition.value}'`;
          }

          return conditionStr;
        });

      if (whereConditions.length > 0) {
        query += ' WHERE ' + whereConditions.join('');
      }
    }

    // Add project ID condition if exists
    if (formData.projectId) {
      const projectCondition = formData.table === 'tblroute_details'
        ? `pid = '${formData.projectId}'`
        : `project_id = '${formData.projectId}'`;

      if (query.includes('WHERE')) {
        query += ` AND ${projectCondition}`;
      } else {
        query += ` WHERE ${projectCondition}`;
      }
    }

    // Add date range condition if exists
    if (formData.dateRange?.from && formData.dateRange?.to) {
      const dateCondition = `rcd BETWEEN '${formData.dateRange.from}' AND '${formData.dateRange.to}'`;

      if (query.includes('WHERE')) {
        query += ` AND ${dateCondition}`;
      } else {
        query += ` WHERE ${dateCondition}`;
      }
    }

    alert(`Generated Query:\n\n${query}`);
  }
}
