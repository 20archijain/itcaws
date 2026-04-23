import { Pipe, PipeTransform } from '@angular/core';

@Pipe({
  name: 'slice',
  standalone: false,
})
export class SlicePipe implements PipeTransform {

  transform(items: any[], startIndex: number, endIndex?: number) {
    if (!items || items.length === 0) {
      return [];
    }

    return endIndex ? items.slice(startIndex, endIndex + 1) : items.slice(startIndex);
  }
}
