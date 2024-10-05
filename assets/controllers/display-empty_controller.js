// @see https://dev.to/phawk/hotwire-best-practices-for-stimulus-40e
import { Controller } from "@hotwired/stimulus";
import { useMutation } from "stimulus-use";

/*
 * Usage
 * =====
 *
 * add data-controller="display-empty" to common ancestor
 *
 * Classes:
 * data-display-empty-hide-class="hidden"
 *
 * Targets:
 * data-display-empty-target="emptyMessage"
 * data-display-empty-target="list"
 *
 */
export default class extends Controller {
  static targets = ["list", "emptyMessage"];
  static classes = ["hide"];

  connect() {
    useMutation(this, {
      element: this.listTarget,
      childList: true,
    });
  }

  mutate(entries) {
    for (const mutation of entries) {
      if (mutation.type === "childList") {
        if (this.listTarget.children.length > 0) {
          // hide empty state
          this.emptyMessageTarget.classList.add(this.hideClass);
        } else {
          // show empty state
          this.emptyMessageTarget.classList.remove(this.hideClass);
        }
      }
    }
  }
}
