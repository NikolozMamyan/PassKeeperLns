import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
  connect() {
    this.timeout = setTimeout(() => {
      this.close();
    }, 4500); // correspond au moment du swipeOut
  }

  close() {
    this.element.style.transition = 'transform 0.5s ease, opacity 0.5s ease';
    this.element.style.transform = 'translateX(100%)';
    this.element.style.opacity = '0';
    setTimeout(() => {
      this.element.remove();
    }, 500);
  }
}
