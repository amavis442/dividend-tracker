// src/controllers/toggle_controller.js
import { Controller } from '@hotwired/stimulus';
import { useClickOutside } from "stimulus-use";

/*
 * Usage
 * =====
 *
 * add data-controller="toggle" to common ancestor
 *
 * Action (add this to your button):
 * data-action="toggle#toggle"
 *
 * Targets (add this to the item to be shown/hidden):
 * data-toggle-target="toggleable" data-css-class="class-to-toggle"
 *
 */
export default class extends Controller {
    static targets = ["toggleable"];

    connect() {
        // Any clicks outside the controller’s element can
        // be setup to either add a 'hidden' class or
        // remove a 'open' class etc.
        useClickOutside(this);
        document.addEventListener('turbolinks:before-cache', this.handleCache);
    }

    toggle(event) {
        event.preventDefault();

        this.toggleableTargets.forEach((target) => {
            target.classList.toggle(target.dataset.cssClass);
        });
    }

    clickOutside(event) {
        this.toggleableTargets.forEach((target) => {
            target.classList.add(target.dataset.cssClass);
        });
    }

    handleCache(event) {
        this.toggleableTargets.forEach((target) => {
            target.classList.add(target.dataset.cssClass);
        });
    }
}
