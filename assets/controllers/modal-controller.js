// src/controllers/modal-controller.js
import { Controller } from '@hotwired/stimulus';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    open() {
        document.body.classList.add("modal-open");
        this.element.setAttribute("style", "display: block;");
        this.element.classList.add("show");
        document.body.innerHTML += '<div class="modal-backdrop fade show"></div>';
        document.addEventListener('turbolinks:before-cache', this.handleCache);
    }

    close() {
        document.body.classList.remove("modal-open");
        this.element.removeAttribute("style");
        this.element.classList.remove("show");
        document.getElementsByClassName("modal-backdrop")[0].remove();
    }

    handleCache(event) {
        this.close();
    }
}
