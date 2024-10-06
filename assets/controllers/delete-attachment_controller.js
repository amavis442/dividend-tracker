import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ["attachment"]
    static values = {
        token: String,
        link: String,
    }

    delete(event) {
        event.preventDefault()

        if (confirm("Are you sure you want to delete this item?")) {
            fetch(this.linkValue, {
                method: "DELETE",
                headers: {
                    "X-Requested-With": "XMLHttpRequest",
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({ "_token": this.tokenValue })
            }).then(
                response => response.json()
            ).then(data => {

                if (data.success) {
                    this.attachmentTarget.parentNode.classList.add('transform', 'opacity-0', 'transition', 'duration-1000');
                    setTimeout(() => this.attachmentTarget.parentNode.remove(), 1000)
                } else {
                    alert(data.error)
                }
            }).catch(e => alert(e))
        }

    }
}
