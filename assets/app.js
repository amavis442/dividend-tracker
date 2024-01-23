import jQuery from 'jquery';

const $ = jQuery;

import 'bootstrap';

// any CSS you import will output into a single css file (app.css in this case)
import './styles/global.scss';
import './styles/app.css';
import bsCustomFileInput from 'bs-custom-file-input';

jQuery(function () {
  $('[data-toggle="popover"]').popover();
  $('[data-toggle="tooltip"]').tooltip();
  bsCustomFileInput.init();
});
