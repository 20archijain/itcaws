import { Component, EventEmitter, Input, OnChanges, Output, SimpleChanges } from '@angular/core';
import { trigger, style, transition, animate } from '@angular/animations';
import * as XLSX from 'xlsx';
import * as FileSaver from 'file-saver';

@Component({
    selector: 'app-npsr-accordion',
    templateUrl: './npsr-accordion.component.html',
    styleUrls: ['./npsr-accordion.component.scss'],
    animations: [
        trigger('columnAnimation', [
            transition(':enter', [
                style({ opacity: 0, transform: 'translateX(50px)' }),
                animate('300ms ease-out', style({ opacity: 1, transform: 'translateX(0)' })),
            ]),
            transition(':leave', [
                animate('300ms ease-in', style({ opacity: 0, transform: 'translateX(50px)' })),
            ]),
        ]),
    ],
    standalone: false
})
export class NpsrAccordionComponent implements OnChanges {
  @Input() monthlySalesData: any;
  @Output() mapClicked = new EventEmitter<any>();
  expandedMap = new Set();
  showDownloadPopup = false;
  downloadLevels = ['district', 'branch', 'circle', 'section', 'wd', 'team', 'route', 'outlet'];

  ngOnChanges(changes: SimpleChanges): void {
    if (changes['monthlySalesData'] && this.monthlySalesData) {
      this.restoreExpansion(this.monthlySalesData);
    }
  }

  onLevelSelect(level: string): void {
    this.showDownloadPopup = false;
    this.downloadVisibleDataAsExcel(level);  // Call your existing method
  }

  closeDownloadPopup(): void {
    this.showDownloadPopup = false;
  }

  downloadVisibleDataAsExcel(_label: string): void {
    if (!this.monthlySalesData) return;

    const rows: any[] = [];


    const visibleMonths = this.getVisibleMonths();

    if (_label == 'district') {
      this.monthlySalesData.districtData.forEach((district: any) => {
        rows.push(this.createRow({ district: district.district }, district.districtLevelSale, visibleMonths));
      });
    }
    else if (_label == 'branch') {
      this.monthlySalesData.districtData.forEach((district: any) => {
        district.branchData.forEach((branch: any) => {
          rows.push(this.createRow({ district: district.district, branch: branch.branch_name }, branch.branchLevelSale, visibleMonths));

        });
      });
    }
    else if (_label == 'circle') {
      this.monthlySalesData.districtData.forEach((district: any) => {
        district.branchData.forEach((branch: any) => {
          branch.circleData.forEach((circle: any) => {
            rows.push(this.createRow({ district: district.district, branch: branch.branch_name, circle: circle.circle }, circle.circleLevelSale, visibleMonths));
          });
        });
      });
    }
    else if (_label == 'section') {
      this.monthlySalesData.districtData.forEach((district: any) => {
        district.branchData.forEach((branch: any) => {
          branch.circleData.forEach((circle: any) => {
            circle.sectionData.forEach((section: any) => {
              rows.push(this.createRow({ district: district.district, branch: branch.branch_name, circle: circle.circle, section: section.section }, section.sectionLevelSale, visibleMonths));
            });
          });
        });
      });
    }
    else if (_label == 'wd') {
      this.monthlySalesData.districtData.forEach((district: any) => {
        district.branchData.forEach((branch: any) => {
          branch.circleData.forEach((circle: any) => {
            circle.sectionData.forEach((section: any) => {
              section.wdData.forEach((wd: any) => {
                rows.push(this.createRow({ district: district.district, branch: branch.branch_name, circle: circle.circle, section: section.section, wd: wd.wd_code }, wd.wdLevelSale, visibleMonths));
              });
            });
          });
        });
      });
    }
    else if (_label == 'team') {
      this.monthlySalesData.districtData.forEach((district: any) => {
        district.branchData.forEach((branch: any) => {
          branch.circleData.forEach((circle: any) => {
            circle.sectionData.forEach((section: any) => {
              section.wdData.forEach((wd: any) => {
                wd.teamData.forEach((team: any) => {
                  rows.push(this.createRow({ district: district.district, branch: branch.branch_name, circle: circle.circle, section: section.section, wd: wd.wd_code, team: team.team_name }, team.teamLevelSale, visibleMonths));
                });
              });
            });
          });
        });
      });
    }
    else if (_label == 'route') {
      this.monthlySalesData.districtData.forEach((district: any) => {
        district.branchData.forEach((branch: any) => {
          branch.circleData.forEach((circle: any) => {
            circle.sectionData.forEach((section: any) => {
              section.wdData.forEach((wd: any) => {
                wd.teamData.forEach((team: any) => {
                  team.routeData?.forEach((route: any) => {
                    rows.push(this.createRow({ district: district.district, branch: branch.branch_name, circle: circle.circle, section: section.section, wd: wd.wd_code, team: team.team_name, route: route.route_name }, route.routeLevelSale, visibleMonths));
                  });
                });
              });
            });
          });
        });
      });
    }
    else if (_label == 'outlet') {
      this.monthlySalesData.districtData.forEach((district: any) => {
        district.branchData.forEach((branch: any) => {
          branch.circleData.forEach((circle: any) => {
            circle.sectionData.forEach((section: any) => {
              section.wdData.forEach((wd: any) => {
                wd.teamData.forEach((team: any) => {
                  team.routeData?.forEach((route: any) => {
                    route.outletData?.forEach((outlet: any) => {
                      rows.push(this.createRow({ district: district.district, branch: branch.branch_name, circle: circle.circle, section: section.section, wd: wd.wd_code, team: team.team_name, route: route.route_name, outlet: outlet.outlet_name }, outlet.outletLevelSale, visibleMonths));
                    });
                  });
                });
              });
            });
          });
        });
      });
    }

    const worksheet: XLSX.WorkSheet = XLSX.utils.json_to_sheet(rows);
    const workbook: XLSX.WorkBook = { Sheets: { 'Sales Data': worksheet }, SheetNames: ['Sales Data'] };
    const excelBuffer: any = XLSX.write(workbook, { bookType: 'xlsx', type: 'array' });

    const data: Blob = new Blob([excelBuffer], { type: 'application/octet-stream' });
    FileSaver.saveAs(data, `${this.monthlySalesData.Title || 'productive-dashboard'}.xlsx`);
  }

  capitalize(word: string): string {
    return word.charAt(0).toUpperCase() + word.slice(1);
  }

  getVisibleMonths(): string[] {
    const allMonths = this.monthlySalesData.MonthsAndYears;
    if (!allMonths || !Array.isArray(allMonths)) return [];

    return allMonths.filter((_, index) => this.shouldDisplayColumn(index));
  }



  createRow(labels: { [key: string]: string }, sales: any, visibleMonths: string[]): any {
    const row: any = {};

    // Add label columns (district, branch, etc.)
    Object.keys(labels).forEach((key) => {
      row[this.capitalize(key)] = labels[key];
    });

    // Add only visible months
    visibleMonths.forEach((month) => {
      row[month] = sales[month] || 0;
    });

    return row;
  }



  // createRow(labels: { [key: string]: string }, sales: any): any {
  //   const row: any = {};
  //   Object.keys(labels).forEach((key) => {
  //     row[this.capitalize(key)] = labels[key];
  //   });

  //   // Add sales data per month
  //   this.monthlySalesData.MonthsAndYears.forEach((month: string) => {
  //     row[month] = sales[month] || 0;
  //   });

  //   return row;
  // }

  // Managing expanded rows (Branch, Circle, etc.)
  toggleExpand(item: any, level: string): void {
    const key = this.getUniqueKey(item, level);
    item.isExpanded = !item.isExpanded;

    if (item.isExpanded) {
      this.expandedMap.add(key);
    } else {
      this.expandedMap.delete(key);
    }
  }

  getUniqueKey(item: any, level: string): string {
    switch (level) {
      case 'district': return `district-${item.district}`;
      case 'branch': return `branch-${item.branch_name}`;
      case 'circle': return `circle-${item.circle}`;
      case 'section': return `section-${item.section}`;
      case 'wd': return `wd-${item.wd_code}`;
      case 'team': return `team-${item.team_name}`;
      case 'route': return `team-${item.route_name}`;
      case 'outlet': return `team-${item.outlet_name}`;
      default: return '';
    }
  }

  restoreExpansion(data: any): void {
    data.districtData?.forEach((district: any) => {
      district.isExpanded = this.expandedMap.has(this.getUniqueKey(district, 'district'));

      district.branchData?.forEach((branch: any) => {
        branch.isExpanded = this.expandedMap.has(this.getUniqueKey(branch, 'branch'));

        branch.circleData?.forEach((circle: any) => {
          circle.isExpanded = this.expandedMap.has(this.getUniqueKey(circle, 'circle'));

          circle.sectionData?.forEach((section: any) => {
            section.isExpanded = this.expandedMap.has(this.getUniqueKey(section, 'section'));

            section.wdData?.forEach((wd: any) => {
              wd.isExpanded = this.expandedMap.has(this.getUniqueKey(wd, 'wd'));

              wd.teamData?.forEach((team: any) => {
                team.isExpanded = this.expandedMap.has(this.getUniqueKey(team, 'team'));

                team.routeData?.forEach((route: any) => {
                  route.isExpanded = this.expandedMap.has(this.getUniqueKey(route, 'route'));

                  route.outletData?.forEach((outlet: any) => {
                    outlet.isExpanded = this.expandedMap.has(this.getUniqueKey(outlet, 'outlet'));
                  });
                });
              });
            });
          });
        });
      });
    });
  }

  // Control which columns are expanded
  columnGroupsExpanded: { [key: number]: boolean } = {};

  shouldDisplayColumn(index: number): boolean {
    if (index === 0 || index === 5 || index === 10) {
      return true;
    }
    if (index >= 1 && index <= 4) {
      return this.columnGroupsExpanded[0] === true;
    }
    if (index >= 6 && index <= 9) {
      return this.columnGroupsExpanded[5] === true;
    }
    if (index >= 11 && index <= 14) {
      return this.columnGroupsExpanded[10] === true;
    }
    return true;
  }

  handleHeaderClick(index: number): void {
    if (index === 0 || index === 5 || index === 10) {
      this.columnGroupsExpanded[index] = !this.columnGroupsExpanded[index];
    }
  }

  // Optional helper for better control
  getColumnState(index: number): string {
    return this.shouldDisplayColumn(index) ? 'visible' : 'hidden';
  }

  isWhiteColumn(index: number): boolean {
    return (index >= 1 && index <= 4) || (index >= 6 && index <= 9) || (index >= 11 && index <= 14);
  }
  openMap(type: any): void {
    this.mapClicked.emit(type);
  }

  toggleAllColumns(): void {
    const newState = !this.areAllGroupsExpanded();
    this.columnGroupsExpanded[0] = newState;
    this.columnGroupsExpanded[5] = newState;
    this.columnGroupsExpanded[10] = newState;
  }

  areAllGroupsExpanded(): boolean {
    return this.columnGroupsExpanded[0] && this.columnGroupsExpanded[5] && this.columnGroupsExpanded[10];
  }

  closeAllExpand(): void {
    this.expandedMap.clear();

    this.monthlySalesData?.districtData?.forEach((district: any) => {
      district.isExpanded = false;

      district.branchData?.forEach((branch: any) => {
        branch.isExpanded = false;

        branch.circleData?.forEach((circle: any) => {
          circle.isExpanded = false;

          circle.sectionData?.forEach((section: any) => {
            section.isExpanded = false;

            section.wdData?.forEach((wd: any) => {
              wd.isExpanded = false;

              wd.teamData?.forEach((team: any) => {
                team.isExpanded = false;

                team.routeData?.forEach((route: any) => {
                  route.isExpanded = false;
                });
              });
            });
          });
        });
      });
    });
  }
}
