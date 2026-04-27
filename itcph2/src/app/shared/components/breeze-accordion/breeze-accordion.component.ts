import {
  Component,
  EventEmitter,
  Input,
  Output,
  OnChanges,
  SimpleChanges,
} from "@angular/core";
import { trigger, style, transition, animate } from "@angular/animations";
import * as XLSX from "xlsx";
import * as FileSaver from "file-saver";

@Component({
  selector: "app-breeze-accordion",
  templateUrl: "./breeze-accordion.component.html",
  styleUrls: ["./breeze-accordion.component.scss"],
  animations: [
    trigger("columnAnimation", [
      transition(":enter", [
        style({ opacity: 0, transform: "translateX(50px)" }),
        animate("300ms ease-out", style({ opacity: 1, transform: "translateX(0)" })),
      ]),
      transition(":leave", [
        animate("300ms ease-in", style({ opacity: 0, transform: "translateX(50px)" })),
      ]),
    ]),
  ],
  standalone: false,
})
export class BreezeAccordionComponent implements OnChanges {
  @Input() monthlySalesData: any;
  @Output() mapClicked = new EventEmitter<any>();
  expandedMap = new Set();
  showDownloadPopup = false;
  downloadLevels = [
    "district",
    "branch",
    "circle",
    "section",
    "wd",
    "type",
    "team",
  ];

  ngOnChanges(changes: SimpleChanges): void {
    if (changes["monthlySalesData"] && this.monthlySalesData) {
      this.restoreExpansion(this.monthlySalesData);
    }
  }

  toggleExpand(item: any, level: string): void {
    const key = this.getUniqueKey(item, level);
    item.isExpanded = !item.isExpanded;

    if (item.isExpanded) {
      this.expandedMap.add(key);
    } else {
      this.expandedMap.delete(key);
    }
  }

  onLevelSelect(level: string): void {
    this.showDownloadPopup = false;
    this.downloadVisibleDataAsExcel(level); // Call your existing method
  }

  closeDownloadPopup(): void {
    this.showDownloadPopup = false;
  }

  getUniqueKey(item: any, level: string): string {
    switch (level) {
      case "district":
        return `district-${item.district}`;
      case "branch":
        return `branch-${item.branch_name}`;
      case "circle":
        return `circle-${item.circle}`;
      case "section":
        return `section-${item.section}`;
      case "wd":
        return `wd-${item.wd_code}`;
      case "type":
        return `typw-${item.type}`;
      case "team":
        return `team-${item.team_name}`;
      default:
        return "";
    }
  }

  // restoreExpansion(data: any): void {
  //   data.districtData?.forEach((district: any) => {
  //     district.isExpanded = this.expandedMap.has(this.getUniqueKey(district, 'district'));

  //     district.branchData?.forEach((branch: any) => {
  //       branch.isExpanded = this.expandedMap.has(this.getUniqueKey(branch, 'branch'));

  //       branch.circleData?.forEach((circle: any) => {
  //         circle.isExpanded = this.expandedMap.has(this.getUniqueKey(circle, 'circle'));

  //         circle.sectionData?.forEach((section: any) => {
  //           section.isExpanded = this.expandedMap.has(this.getUniqueKey(section, 'section'));

  //           section.wdData?.forEach((wd: any) => {
  //             wd.isExpanded = this.expandedMap.has(this.getUniqueKey(wd, 'wd'));

  //             wd.teamData?.forEach((team: any) => {
  //               team.isExpanded = this.expandedMap.has(this.getUniqueKey(team, 'team'));
  //             });
  //           });
  //         });
  //       });
  //     });
  //   });
  // }

  restoreExpansion(data: any): void {
    data.districtData?.forEach((district: any) => {
      district.isExpanded = this.expandedMap.has(
        this.getUniqueKey(district, "district"),
      );
      district.branchData?.forEach((branch: any) => {
        branch.isExpanded = this.expandedMap.has(
          this.getUniqueKey(branch, "branch"),
        );
        branch.circleData?.forEach((circle: any) => {
          circle.isExpanded = this.expandedMap.has(
            this.getUniqueKey(circle, "circle"),
          );
          circle.sectionData?.forEach((section: any) => {
            section.isExpanded = this.expandedMap.has(
              this.getUniqueKey(section, "section"),
            );
            section.wdData?.forEach((wd: any) => {
              wd.isExpanded = this.expandedMap.has(this.getUniqueKey(wd, "wd"));
              // NEW: traverse teamTypeData
              wd.teamTypeData?.forEach((teamType: any) => {
                teamType.isExpanded = this.expandedMap.has(
                  this.getUniqueKey(teamType, "type"),
                );
                teamType.teamData?.forEach((team: any) => {
                  team.isExpanded = this.expandedMap.has(
                    this.getUniqueKey(team, "team"),
                  );
                });
              });
            });
          });
        });
      });
    });
  }

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

  getColumnState(index: number): string {
    return this.shouldDisplayColumn(index) ? "visible" : "hidden";
  }

  // downloadVisibleDataAsExcel(_label : string): void {
  //   // console.log(_label);

  //   if (!this.monthlySalesData) return;

  //   const rows: any[] = [];

  //   this.monthlySalesData.districtData.forEach((district: any) => {
  //     rows.push(this.createRow(district.district, district.districtLevelSale));

  //     if (district.isExpanded) {
  //       district.branchData.forEach((branch: any) => {
  //         rows.push(this.createRow(`  ${branch.branch_name}`, branch.branchLevelSale));

  //         if (branch.isExpanded) {
  //           branch.circleData.forEach((circle: any) => {
  //             rows.push(this.createRow(`    ${circle.circle}`, circle.circleLevelSale));

  //             if (circle.isExpanded) {
  //               circle.sectionData.forEach((section: any) => {
  //                 rows.push(this.createRow(`      ${section.section}`, section.sectionLevelSale));

  //                 if (section.isExpanded) {
  //                   section.wdData.forEach((wd: any) => {
  //                     rows.push(this.createRow(`        ${wd.wd_code}`, wd.wdLevelSale));

  //                     if (wd.isExpanded) {
  //                       wd.teamData.forEach((team: any) => {
  //                         rows.push(this.createRow(`          ${team.team_name}`, team.teamLevelSale));
  //                       });
  //                     }
  //                   });
  //                 }
  //               });
  //             }
  //           });
  //         }
  //       });
  //     }
  //   });

  //   const worksheet: XLSX.WorkSheet = XLSX.utils.json_to_sheet(rows);
  //   const workbook: XLSX.WorkBook = { Sheets: { 'Sales Data': worksheet }, SheetNames: ['Sales Data'] };
  //   const excelBuffer: any = XLSX.write(workbook, { bookType: 'xlsx', type: 'array' });

  //   const data: Blob = new Blob([excelBuffer], { type: 'application/octet-stream' });
  //   FileSaver.saveAs(data, `${this.monthlySalesData.Title || 'productive-dashboard'}.xlsx`);
  // }

  downloadVisibleDataAsExcel(_label: string): void {
    if (!this.monthlySalesData) return;

    const rows: any[] = [];

    if (_label == "district") {
      this.monthlySalesData.districtData.forEach((district: any) => {
        rows.push(
          this.createRow(
            { district: district.district },
            district.districtLevelSale,
          ),
        );
      });
    } else if (_label == "branch") {
      this.monthlySalesData.districtData.forEach((district: any) => {
        district.branchData.forEach((branch: any) => {
          rows.push(
            this.createRow(
              { district: district.district, branch: branch.branch_name },
              branch.branchLevelSale,
            ),
          );
        });
      });
    } else if (_label == "circle") {
      this.monthlySalesData.districtData.forEach((district: any) => {
        district.branchData.forEach((branch: any) => {
          branch.circleData.forEach((circle: any) => {
            rows.push(
              this.createRow(
                {
                  district: district.district,
                  branch: branch.branch_name,
                  circle: circle.circle,
                },
                circle.circleLevelSale,
              ),
            );
          });
        });
      });
    } else if (_label == "section") {
      this.monthlySalesData.districtData.forEach((district: any) => {
        district.branchData.forEach((branch: any) => {
          branch.circleData.forEach((circle: any) => {
            circle.sectionData.forEach((section: any) => {
              rows.push(
                this.createRow(
                  {
                    district: district.district,
                    branch: branch.branch_name,
                    circle: circle.circle,
                    section: section.section,
                  },
                  section.sectionLevelSale,
                ),
              );
            });
          });
        });
      });
    } else if (_label == "wd") {
      this.monthlySalesData.districtData.forEach((district: any) => {
        district.branchData.forEach((branch: any) => {
          branch.circleData.forEach((circle: any) => {
            circle.sectionData.forEach((section: any) => {
              section.wdData.forEach((wd: any) => {
                rows.push(
                  this.createRow(
                    {
                      district: district.district,
                      branch: branch.branch_name,
                      circle: circle.circle,
                      section: section.section,
                      wd: wd.wd_code,
                    },
                    wd.wdLevelSale,
                  ),
                );
              });
            });
          });
        });
      });
    } else if (_label == 'type') {
      this.monthlySalesData.districtData.forEach((district: any) => {
        district.branchData.forEach((branch: any) => {
          branch.circleData.forEach((circle: any) => {
            circle.sectionData.forEach((section: any) => {
              section.wdData.forEach((wd: any) => {
                wd.teamTypeData.forEach((teamType: any) => {
                  rows.push(this.createRow({
                    district: district.district, branch: branch.branch_name,
                    circle: circle.circle, section: section.section,
                    wd: wd.wd_code, type: teamType.team_type
                  }, teamType.teamTypeLevelSale));
                });
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
                // NEW: go through teamTypeData first
                wd.teamTypeData.forEach((teamType: any) => {
                  teamType.teamData.forEach((team: any) => {
                    rows.push(this.createRow({
                      district: district.district, branch: branch.branch_name,
                      circle: circle.circle, section: section.section,
                      wd: wd.wd_code, type: teamType.team_type,
                      dsType: team.team_type, dsId: team.team_id, dsName: team.team_name
                    }, team.teamLevelSale));
                  });
                });
              });
            });
          });
        });
      });
    }
    // else if (_label == "team") {
    //   this.monthlySalesData.districtData.forEach((district: any) => {
    //     district.branchData.forEach((branch: any) => {
    //       branch.circleData.forEach((circle: any) => {
    //         circle.sectionData.forEach((section: any) => {
    //           section.wdData.forEach((wd: any) => {
    //             wd.teamData.forEach((team: any) => {
    //               rows.push(
    //                 this.createRow(
    //                   {
    //                     district: district.district,
    //                     branch: branch.branch_name,
    //                     circle: circle.circle,
    //                     section: section.section,
    //                     wd: wd.wd_code,
    //                     dsType: team.team_type,
    //                     dsId: team.team_id,
    //                     dsName: team.team_name,
    //                   },
    //                   team.teamLevelSale,
    //                 ),
    //               );
    //             });
    //           });
    //         });
    //       });
    //     });
    //   });
    // }

    const worksheet: XLSX.WorkSheet = XLSX.utils.json_to_sheet(rows);
    const workbook: XLSX.WorkBook = {
      Sheets: { "Sales Data": worksheet },
      SheetNames: ["Sales Data"],
    };
    const excelBuffer: any = XLSX.write(workbook, {
      bookType: "xlsx",
      type: "array",
    });

    const data: Blob = new Blob([excelBuffer], {
      type: "application/octet-stream",
    });
    FileSaver.saveAs(
      data,
      `${this.monthlySalesData.Title || "productive-dashboard"}.xlsx`,
    );
  }

  // createRow(name: string, sales: any): any {
  //   const row: any = { Name: name };
  //   this.monthlySalesData.MonthsAndYears.forEach((month: string) => {
  //     row[month] = sales[month] || 0;
  //   });
  //   return row;
  // }

  capitalize(word: string): string {
    return word.charAt(0).toUpperCase() + word.slice(1);
  }

  createRow(labels: { [key: string]: string }, sales: any): any {
    const row: any = {};
    Object.keys(labels).forEach((key) => {
      row[this.capitalize(key)] = labels[key];
    });

    // Add sales data per month
    this.monthlySalesData.MonthsAndYears.forEach((month: string) => {
      row[month] = sales[month] || 0;
    });

    return row;
  }

  // closeAllExpand(): void {
  //   this.expandedMap.clear();

  //   this.monthlySalesData?.districtData?.forEach((district: any) => {
  //     district.isExpanded = false;

  //     district.branchData?.forEach((branch: any) => {
  //       branch.isExpanded = false;

  //       branch.circleData?.forEach((circle: any) => {
  //         circle.isExpanded = false;

  //         circle.sectionData?.forEach((section: any) => {
  //           section.isExpanded = false;

  //           section.wdData?.forEach((wd: any) => {
  //             wd.isExpanded = false;

  //             wd.teamData?.forEach((team: any) => {
  //               team.isExpanded = false;
  //             });
  //           });
  //         });
  //       });
  //     });
  //   });
  // }
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
              // NEW: collapse teamTypeData
              wd.teamTypeData?.forEach((teamType: any) => {
                teamType.isExpanded = false;
                teamType.teamData?.forEach((team: any) => {
                  team.isExpanded = false;
                });
              });
            });
          });
        });
      });
    });
  }
}
