//import { Modal } from 'bootstrap';

const TurboHelper = class {
    constructor() {
        document.addEventListener('turbo:before-cache', () => {
            //this.closeModal();
            this.closeSweetalert();
        });

        /*
        document.addEventListener("turbo:load", () => {
            this.googleAnalytics();
        });
        */
    }

    /*
    closeModal() {
        if (document.body.classList.contains('modal-open')) {
            const modalEl = document.querySelector('.modal');
            const modal = Modal.getInstance(modalEl);
            modalEl.classList.remove('fade');
            modal._backdrop._config.isAnimated = false;
            modal.hide();
            modal.dispose();
        }
    }
    */

    closeSweetalert() {
        // internal way to see if sweetalert2 has been imported yet
        import('sweetalert2').then((Swal) => {
            if (Swal.default.isVisible()) {
                Swal.default.getPopup().style.animationDuration = '0ms';
                Swal.default.close();
            }
        });
    }

    googleAnalytics() {
        // https://github.com/turbolinks/turbolinks/issues/73#issuecomment-812484452
        // Google Analytics.
        // https://github.com/turbolinks/turbolinks/issues/73#issuecomment-460028854
        if (typeof (gtag) == "function") {
            gtag("config", window.googleAnalyticsIDForScript, {
                "page_title": document.title,
                "page_path": location.href.replace(location.origin, ""),
            })
        }
    }
}

export default new TurboHelper();
