import { Controller } from '@hotwired/stimulus';
import Swal from 'sweetalert2';
import * as Turbo from '@hotwired/turbo';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static values = {
        title: String,
        text: String,
        icon: String,
        confirmButtonText: String,
        cancelButtonText: String,
    }
    connect() {
        this.submit = false
    }

    onSubmit(event) {
        if (this.submit) {
            return true
        }
        event.preventDefault();

        //console.debug(event.target.dataset)
        Swal.fire({
            title: this.titleValue || null,
            text: this.textValue || null,
            icon: this.iconValue || null,
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            cancelButtonText: this.cancelButtonTextValue || "Cancel",
            confirmButtonText: this.confirmButtonTextValue || 'Yes',
        }).then((result) => {
            if (result.isConfirmed) {
                this.submit = true
                this.element.requestSubmit()
            }
        })
    }
}
