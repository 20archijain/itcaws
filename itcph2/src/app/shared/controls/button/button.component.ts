import { Component, EventEmitter, Input, Output } from '@angular/core';

enum SpinnerType {
  Border = 1,
  Grow = 2
}

@Component({
  selector: 'app-button',
  templateUrl: './button.component.html',
  standalone: false,
})
export class ButtonComponent {
  @Output() private onClick = new EventEmitter<Event>();
  @Input() wrapperClass = '';
  @Input() textColorClass = 'text-white';
  @Input() btnClass = '';
  @Input() btnTitle = '';
  @Input() colorClass = 'btn-primary';
  @Input() type = 'button';
  @Input() text = '';
  @Input() isDisabled = false;
  @Input() showSpinner = false;
  @Input() showIconOnly = false;
  @Input() showIconInButton = false;
  @Input() isFaIcon = false;
  @Input() iconName = '';
  @Input() spinnerType = SpinnerType.Border;
  @Input() spinnerText = '';
  spinType = SpinnerType;

  onBtnClick($event: MouseEvent) {
    this.onClick.emit($event);
  }
}
