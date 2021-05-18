import jQuery from 'jquery';
import 'popper.js';
import 'bootstrap';
import 'bootstrap/dist/css/bootstrap.min.css';
import 'summernote/dist/summernote-bs4';
import 'summernote/dist/summernote-bs4.css';

const $ = jQuery;

jQuery(function () {
  $(".summernote").summernote({
    height: 450,   //set editable area's height
  });
});
