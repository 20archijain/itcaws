export class SessionUtil {

  static setItem(key: string, value: string) {
    sessionStorage.setItem(key, value);
  }

  static getItem(key: string) {
    return sessionStorage.getItem(key);
  }

  static clear() {
    return sessionStorage.clear();
  }

  static setItemLocal(key: string, value: string) {
    localStorage.setItem(key, value);
  }

  static getItemLocal(key: string) {
    return localStorage.getItem(key);
  }

  static clearLocal() {
    return localStorage.clear();
  }
}
