import jQuery from 'jquery';
import 'bootstrap';
import 'bootstrap/dist/css/bootstrap.min.css';
import 'summernote/dist/summernote-bs5';
import 'summernote/dist/summernote-bs5.css';

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
