export class CustomPolyfill {

  static from(elements: any[]) {
    // Array.from
    return Array.prototype.slice.call(elements);
  }
}
