import { Controller } from "@hotwired/stimulus"

export default class extends Controller {
    static targets = ["input"]

    connect() {
        this.originalText = this.inputTarget.value
        this.focusInput()
        this.cursorToEnd()
    }

    focusInput() {
        this.inputTarget.focus()
    }

    cursorToEnd() {
        this.inputTarget.selectionStart =
            this.inputTarget.selectionEnd =
            this.inputTarget.value.length
    }

    submitForm() {
        if (this.inputTarget.disabled) return

        this.element.requestSubmit()
        this.inputTarget.disabled = true
    }

    abort() {
        this.inputTarget.value = this.originalText
        this.submitForm()
    }
}
