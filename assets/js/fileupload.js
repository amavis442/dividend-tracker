import $ from 'jquery';

function addTagForm(collectionHolder, newLinkLi) {
  // Get the data-prototype explained earlier
  var prototype = collectionHolder.data('prototype');

  // get the new index
  var index = collectionHolder.data('index');
  var newForm = prototype;
  // You need this only if you didn't set 'label' => false in your tags field in TaskType
  // Replace '__name__label__' in the prototype's HTML to
  // instead be a number based on how many items we have
  // newForm = newForm.replace(/__name__label__/g, index);

  // Replace '__name__' in the prototype's HTML to
  // instead be a number based on how many items we have
  newForm = newForm.replace(/__name__/g, index);

  // increase the index with one for the next item
  collectionHolder.data('index', index + 1);

  // Display the form in the page in an li, before the "Add a tag" link li
  var newFormLi = $('<li class="list-group-item d-flex justify-content-between align-items-center"></li>').append(newForm);
  newLinkLi.before(newFormLi);


  $("#btnUpload" + index).on('click', function () {
    $('#research_attachments_' + index + '_attachmentFile').trigger('click');
    return false;
  });

}

function removeFile(ob) {
  if (confirm('Are you sure you want to delete this item?')) {
    ob.parent().parent().remove();
  }
}

function removeRow(ob) {
  ob.parent().remove();
}

// setup an "add a tag" link
var addTagButton = $('#addTagButton');
// var $addTagButton = $('<button type="button" class="btn btn-warning">Add a attachment</button>');
var newLinkLi = $('<li class="list-group-item d-flex justify-content-between align-items-center"></li>').append(addTagButton);

$(function () {
  //var fileCount = 0;
  //createAddFile(fileCount++);

  // Get the ul that holds the collection of tags
  var collectionHolder = $('ul.attachments');

  // add the "add a tag" anchor and li to the tags ul
  collectionHolder.append(newLinkLi);

  // count the current form inputs we have (e.g. 2), use that as the new
  // index when inserting a new item (e.g. 2)
  addTagButton.on('click', function () {
    // add a new tag form (see next code block)
    addTagForm(collectionHolder, newLinkLi);
  });

  $('.removeFile').on('click', function (event) {
    event.preventDefault();
    // add a new tag form (see next code block)
    removeFile($(this));
  });

  $(document).on('click', '.removeAttachmentRow', '', function (event) {
    event.preventDefault();
    // remove tag form (see next code block)
    removeRow($(this));
  });
});
