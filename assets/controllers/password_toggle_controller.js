// assets/controllers/password_toggle_controller.js
import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    static targets = ["input", "icon", "button"];

    toggle() {
        if (this.inputTarget.type === 'password') {
            this.inputTarget.type = 'text';
            this.iconTarget.classList.remove('fa-eye');
            this.iconTarget.classList.add('fa-eye-slash');
        } else {
            this.inputTarget.type = 'password';
            this.iconTarget.classList.remove('fa-eye-slash');
            this.iconTarget.classList.add('fa-eye');
        }
    }
}
