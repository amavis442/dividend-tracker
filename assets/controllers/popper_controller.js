// src/controllers/popper_controller.js
import { Controller } from '@hotwired/stimulus';
//import { createPopper } from "@popperjs/core";
import { computePosition, autoUpdate, offset, flip, shift, arrow } from '@floating-ui/dom';


/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static targets = ["element", "tooltip","tooltipArrow"];
    static values = {
        placement: { type: String, default: "top" },
        offset: { type: Array, default: [0, 8] },
    };


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

}
