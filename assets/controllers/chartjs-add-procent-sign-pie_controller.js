import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    connect() {
        this.element.addEventListener('chartjs:pre-connect', this._onPreConnect);
    }

    disconnect() {
        // You should always remove listeners when the controller is disconnected to avoid side effects
        this.element.removeEventListener('chartjs:pre-connect', this._onPreConnect);
    }

    _onPreConnect(event) {
        //Deze werkt niet voor doughnut. Balen, maar er zijn plugins.
        // The chart is not yet created
        // You can access the config that will be passed to "new Chart()"

        // For instance you can format Y axis
        // To avoid overriding existing config, you should distinguish 3 cases:
        // # 1. No existing scales config => add a new scales config
        /* event.detail.config.options.plugins.tooltip = {
            callbacks: {
                label: function (context) {
                   console.log("Yep hij wordt aangeroepen");
                    var label = 'HELLO';
                    return label;
                },
                afterLabel: function(context) {
                    return '%';
                }
            }
        };
        */

    }
}
