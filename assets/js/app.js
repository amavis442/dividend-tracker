const $ = require('jquery');
// this "modifies" the jquery module: adding behavior to it
// the bootstrap module doesn't export/return anything
global.$ = global.jQuery = $;

import 'popper.js';
import 'tooltip.js';
import 'bootstrap';
import 'summernote/dist/summernote-bs4.min.js';

require('../css/app.css');
require('../css/global.scss');

// or you can include specific pieces
//require('bootstrap/js/dist/tooltip');
//require('bootstrap/js/dist/popover');

$(document).ready(function() {
    $('[data-toggle="popover"]').popover();
    $('[data-toggle="tooltip"]').tooltip();
    $('.summernote').summernote({
        height: 600,   //set editable area's height
        codemirror: { // codemirror options
          theme: 'monokai'
        }
      });
});
