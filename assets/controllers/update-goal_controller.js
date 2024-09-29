import { Controller } from '@hotwired/stimulus';
import JSConfetti from 'js-confetti';

/*
 * This is an example Stimulus controller!
 *
 * Any element with a data-controller="hello" attribute will cause
 * this controller to be executed. The name "hello" comes from the filename:
 * hello_controller.js -> "hello"
 *
 * Delete this file or adapt it for your use!
 */
export default class extends Controller {
    static targets = ['goal', 'formgoal'];
    static values = {
        goalnumber: Number,
        formvalid: Number
    }

    connect() {
        console.log(this.formvalidValue);
        this.formgoalTarget.classList.add("tw-hidden");

        if (this.formvalidValue == -1) {
            this.goalTarget.classList.add("tw-hidden");
            this.formgoalTarget.classList.remove("tw-hidden");
        }
    }

    open() {
        this.goalTarget.classList.add("tw-hidden");
        this.formgoalTarget.classList.remove("tw-hidden");
    }

    close() {
        this.goalTarget.classList.remove("tw-hidden");
        this.formgoalTarget.classList.add("tw-hidden");
    }

    async submitForm(event) {
        event.preventDefault();

        var form = this.inputgoalTarget.getElementsByTagName('form');
        console.log(form);
        console.log(form[0].action);
        console.log(form[0].method);
        console.log(form[0][0].value);

        this.inputgoalTarget.classList.add("tw-hidden");
        this.goalTarget.classList.remove("tw-hidden");
        /*await fetch(this.formUrlValue + '?form=1', {
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },
            method: "POST",
            body: JSON.stringify({ goal: goalValue })
        }).then((response) => {
            console.log(response);
        });
        */
    }

    poof() {
        const jsConfetti = new JSConfetti();
        jsConfetti.addConfetti();
    }
}
