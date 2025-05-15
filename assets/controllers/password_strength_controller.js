// assets/controllers/password_strength_controller.js
import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    static targets = ["container", "progressBar", "feedback"];

    connect() {
        this.element.addEventListener('input', (event) => {
            if (event.target.id === 'credential_password') {
                this.check(event);
            }
        });
    }

    check(event) {
        const password = event.target.value;
        this.containerTarget.classList.remove('d-none');

        let strength = 0;
        let feedback = '';

        if (password.length < 8) {
            strength = 10;
            feedback = 'Très faible : Utilisez au moins 8 caractères';
        } else {
            strength = 25;
            feedback = 'Faible : ';

            if (password.length >= 12) strength += 15;
            if (password.match(/[A-Z]/)) {
                strength += 10;
            } else {
                feedback += 'Ajoutez une majuscule. ';
            }

            if (password.match(/[a-z]/)) {
                strength += 10;
            } else {
                feedback += 'Ajoutez une minuscule. ';
            }

            if (password.match(/[0-9]/)) {
                strength += 10;
            } else {
                feedback += 'Ajoutez un chiffre. ';
            }

            if (password.match(/[^A-Za-z0-9]/)) {
                strength += 10;
            } else {
                feedback += 'Ajoutez un caractère spécial. ';
            }

            if (strength >= 80) {
                feedback = 'Fort : Excellent mot de passe !';
            } else if (strength >= 60) {
                feedback = 'Moyen : ' + feedback;
            }
        }

        this.progressBarTarget.style.width = strength + '%';

        if (strength < 40) {
            this.progressBarTarget.style.backgroundColor = '#ef4444';
        } else if (strength < 70) {
            this.progressBarTarget.style.backgroundColor = '#f59e0b';
        } else {
            this.progressBarTarget.style.backgroundColor = '#10b981';
        }

        this.feedbackTarget.textContent = feedback;
    }
}
