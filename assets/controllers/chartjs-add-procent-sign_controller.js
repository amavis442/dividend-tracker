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
        // The chart is not yet created
        // You can access the config that will be passed to "new Chart()"
        console.log(event.detail.config);

        // For instance you can format Y axis
        // To avoid overriding existing config, you should distinguish 3 cases:
        // # 1. No existing scales config => add a new scales config
        event.detail.config.options.scales = {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function (value, index, values) {
                        return value + '%';
                    },
                },
            },
        };

        event.detail.config.options.plugins.tooltip = {

            callbacks: {
                label: function (context) {
                    var label = context.dataset.label || '';
                    if (label) {
                        label += ': ';
                    }
                    if (context.parsed.y !== null) {
                        //label += context.parsed.y + '%';
                        label += new Intl.NumberFormat('nl-NL', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(context.parsed.y) + '%';
                    }

                    return label;
                },
            }
        };
    }
}
