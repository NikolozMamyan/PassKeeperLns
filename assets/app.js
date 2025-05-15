// assets/controllers/index.js
import { Application } from "@hotwired/stimulus";
import PasswordToggleController from "./controllers/password_toggle_controller.js";
import PasswordStrengthController from "./controllers/password_strength_controller.js";
import CredentialFormController from "./controllers/credential_form_controller.js";

const application = Application.start();

application.register("password-toggle", PasswordToggleController);
application.register("password-strength", PasswordStrengthController);
application.register("credential-form", CredentialFormController);
