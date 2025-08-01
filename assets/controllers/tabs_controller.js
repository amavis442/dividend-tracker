// app/javascript/controllers/tabs_controller.js
import { Controller } from "@hotwired/stimulus"

// Connects to data-controller="tabs"
//
export default class extends Controller {
    static classes = ['active']
    static targets = ["btn", "tab"]
    static values = { defaultTab: String }

    connect() {
        // first, hide all tabs
        //this.tabTargets.map(x => x.hidden = true)

        // then, show the default tab
        //let selectedTab = this.tabTargets.find(element => element.id === this.defaultTabValue)
        //selectedTab.hidden = false

        // and activate the selected button
        let selectedBtn = this.btnTargets.find(element => element.id === this.defaultTabValue)
        selectedBtn.classList.add(...this.activeClasses)
    }

    // switch between tabs
    // add to your buttons: data-action="click->tabs#select"
    select(event) {
        // find tab matching (with same id as) the clicked btn
        //let selectedTab = this.tabTargets.find(element => element.id === event.currentTarget.id)
        //if (selectedTab.hidden) {
            // hide everything
            //this.tabTargets.map(x => x.hidden = true) // hide all tabs
            this.btnTargets.map(item => item.classList.remove(...this.activeClasses)) // deactive all btns

            // then show selected
            //selectedTab.hidden = false // show current tab
            event.currentTarget.classList.add(...this.activeClasses) // activate current button
        //}
    }
}
