import jQuery from 'jquery';
import 'popper.js';
import 'bootstrap';
import 'bootstrap/dist/css/bootstrap.min.css';
import 'summernote/dist/summernote-bs4';
import 'summernote/dist/summernote-bs4.css';
//import { createPopper } from '@popperjs/core';

const $ = jQuery;

$(function () {
  var noteHeight = 450;
  var noteElement = $('.summernote');

  if (typeof noteElement.data('noteHeight') !== 'undefined') {
    noteHeight = noteElement.data('noteHeight');
  }

  $(".summernote").summernote({
    height: noteHeight,   //set editable area's height
  });
});
