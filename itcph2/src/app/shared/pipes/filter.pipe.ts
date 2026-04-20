import { Pipe, PipeTransform } from '@angular/core';
import { filter } from 'ramda';

@Pipe({
    name: 'filter',
    standalone: false
})
export class FilterPipe implements PipeTransform {

  private getValues(object: any, allValues = []) {
    if (object && typeof object === 'object') {
      // if array
      if (Array.isArray(object) && object.length > 0) {
        object.forEach(item => {
          this.getValues(item, allValues);
        });
      } else {
        const objKeys = Object.keys(object);

        objKeys.forEach(key => {
          this.getValues(object[key], allValues);
        });
      }
    } else {
      allValues.push(object);
    }

    return allValues;
  }

  transform(items: any[], searchText: string | boolean, labelKey = 'ALL', caseSensitive = false, exclude = false) {
    if (!items || items.length === 0) {
      return [];
    }

    if (!searchText) {
      return items;
    }

    // search all properties of an object
    if (labelKey === 'ALL') {
      return filter(item => {
        // find all the values in the object
        const values = this.getValues(item);

        if (values && values.length > 0) {
          // search for the given value
          return values.some(value => {
            if (caseSensitive) {
              return value && value.toString().indexOf(searchText) > -1;
            } else {
              return value && value.toString().toLowerCase().indexOf((searchText as string).toLowerCase()) > -1;
            }
          });
        }

        return false;
      }, items);
    } else {
      if (labelKey) {
        if (caseSensitive) {
          // exclude searchText from items
          if (exclude) {
            return items.filter(item => item[labelKey].toString().indexOf(searchText) === -1);
          } else {
            return items.filter(item => item[labelKey].toString().indexOf(searchText) > -1);
          }
        } else {
          // exclude searchText from items
          if (exclude) {
            return items.filter(item => item[labelKey].toString().toLowerCase().indexOf((searchText as string).toLowerCase()) === -1);
          } else {
            return items.filter(item => item[labelKey].toString().toLowerCase().indexOf((searchText as string).toLowerCase()) > -1);
          }
        }
      } else {
        if (caseSensitive) {
          // exclude searchText from items
          if (exclude) {
            return items.filter(item => item.toString().indexOf(searchText) === -1);
          } else {
            return items.filter(item => item.toString().indexOf(searchText) > -1);
          }
        } else {
          // exclude searchText from items
          if (exclude) {
            return items.filter(item => item.toString().toLowerCase().indexOf((searchText as string).toLowerCase()) === -1);
          } else {
            return items.filter(item => item.toString().toLowerCase().indexOf((searchText as string).toLowerCase()) > -1);
          }
        }
      }
    }
  }

}
