import 'bootstrap/dist/css/bootstrap.min.css';
import 'summernote/dist/summernote';
import 'summernote/dist/summernote.css';

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
