// src/controllers/tooltip_controller.js
import { Controller } from '@hotwired/stimulus';
//import { createPopper } from "@popperjs/core";
import { computePosition, autoUpdate, offset, flip, shift, arrow } from '@floating-ui/dom';

/*
 * Usage
 * =====
 * add data-controller="tooltip" to common ancestor
 *
 * Action (add this to your button):
 * data-action="mouseenter->tooltip#show mouseleave->tooltip#hide" data-tooltip-target="element"
 *
 * Targets (add this to the item to be shown/hidden):
 * data-tooltip-target="tooltip"
 *
 * Example:
 * =====
 *
 *  <span data-controller="tooltip">
 *     <i class="fas fa-info-circle"
 *        data-tooltip-target="element" data-action="mouseenter->tooltip#show mouseleave->tooltip#hide"></i>
 *       <div class="tooltip" role="tooltip" data-tooltip-target="tooltip">
 *           content to show
 *           <div class="arrow" data-tooltip-target="tooltipArrow"></div>
 *       </div>
 * </span>
 */




/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static targets = ["element", "tooltip","tooltipArrow"];
    static values = {
        placement: { type: String, default: "top" },
        offset: { type: Array, default: [0, 8] },
    };

    connect() {
        document.addEventListener('turbolinks:before-cache', this.handleCache);
    }

    updatePosition(referenceEl, floatingEl, arrowElement) {

        computePosition(referenceEl, floatingEl, {
            placement: this.placementValue,
            middleware: [
                offset({ mainAxis: 10, crossAxis: 30 }),
                flip(),
                shift(),
                arrow({ element: arrowElement }),
            ]
        }).then(({ x, y, placement, middlewareData }) => {
            Object.assign(floatingEl.style, {
                left: `${x}px`,
                top: `${y}px`,
            });

            // Accessing the data
            const { x: arrowX, y: arrowY } = middlewareData.arrow;

            const staticSide = {
                top: 'bottom',
                right: 'left',
                bottom: 'top',
                left: 'right',
            }[placement.split('-')[0]];

            Object.assign(arrowElement.style, {
                left: arrowX != null ? `${arrowX}px` : '',
                top: arrowY != null ? `${arrowY}px` : '',
                right: '',
                bottom: '',
                [staticSide]: '-4px',
            });
        });
    }

    show(event) {
        this.tooltipTarget.setAttribute("data-show", "");

        const arrowElement = this.tooltipArrowTarget;
        const referenceEl = this.elementTarget;
        const floatingEl = this.tooltipTarget;

        this.cleanup = autoUpdate(referenceEl, floatingEl, () => { this.updatePosition(referenceEl, floatingEl, arrowElement) });
    }

    hide(event) {
        this.tooltipTarget.removeAttribute("data-show");
        this.cleanup();
    }

    handleCache(event) {
        this.hide(event);
        this.tooltipTarget.addAttribute("hidden");
    }
}
