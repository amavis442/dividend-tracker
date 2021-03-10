import jquery from 'jquery';

const $ = jquery;
global.$ = global.jQuery = $;

require('bootstrap/js/dist/popover');
require('bootstrap/js/dist/tooltip');
require('bootstrap/js/dist/collapse');

//import 'bootstrap';
import 'summernote/dist/summernote-bs4.min.js';

// any CSS you import will output into a single css file (app.css in this case)
import './styles/app.css';
import './styles/global.scss';

// or you can include specific pieces
//require('bootstrap/js/dist/tooltip');
//require('bootstrap/js/dist/popover');

jQuery(function(){
    $('[data-toggle="popover"]').popover();
    $('[data-toggle="tooltip"]').tooltip();
    $('.summernote').summernote({
        height: 600,   //set editable area's height
        codemirror: { // codemirror options
          theme: 'monokai'
        }
      });
});
