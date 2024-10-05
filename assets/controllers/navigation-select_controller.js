import { Controller } from "@hotwired/stimulus";
import * as Turbo from "@hotwired/turbo"

/*
 * Usage
 * =====
 *
 * add data-controller="navigation-select" to common ancestor
 *
 * Action:
 * data-action="change->navigation-select#change"
 *
 */
export default class extends Controller {
    change(event) {
        const url = event.target.value;
        Turbo.visit(url);
    }
}
