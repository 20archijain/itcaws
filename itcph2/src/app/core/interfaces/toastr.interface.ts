type ToastrConfigPosition = 'toast-bottom-right' | 'toast-bottom-left' | 'toast-bottom-center' | 'toast-center-center' | 'toast-top-left'
  | 'toast-top-right' | 'toast-top-center' | 'toast-top-full-width' | 'toast-bottom-full-width';

type ToastrConfigType = 'info' | 'success' | 'error' | 'warning';

export interface ToastrConfig {
  closeOther?: boolean;
  msg?: string;
  position?: ToastrConfigPosition;
  showClose?: boolean;
  timeout?: number;
  title?: string;
  type?: ToastrConfigType;
}
