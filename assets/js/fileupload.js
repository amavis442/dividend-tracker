const $ = require('jquery');

function createAddFile(fileCount) {
    // grab the prototype template
    var newWidget = $("#research_files").attr('data-prototype');
    // replace the "__name__" used in the id and name of the prototype
    newWidget = newWidget.replace(/__name__label__/g, '');
    newWidget = newWidget.replace(/__name__/g, fileCount);
    newWidget = "<div style='display:none'>" + newWidget + "</div>";

    hideStuff = "";
    hideStuff += "<div class='col col-xs-1' id='jsRemove" + fileCount + "' style='display: none;'>";
    hideStuff += removeButton;
    hideStuff += "</div>";

    hideStuff += "<div class='col col-xs-11' id='jsPreview" + fileCount + "'>";
    hideStuff += "</div>";

    hideStuff += "<div class='col col-xs-12'>";
    hideStuff += "<button type='button' id='jsBtnUpload" + fileCount + "' class='btn btn-warning'>";
    hideStuff += "<i class='fa fa-plus'></i> document";
    hideStuff += "</button>";
    hideStuff += "</div>";

    $("#filesBox").append("<div class='row'>" + hideStuff + newWidget + "</div>");
	$("#jsBtnUpload" + fileCount).on('click', function(e){
		$('#research_files_' + fileCount + '_file').trigger('click');
	});

    
    // Once the file is added
    $('#research_files_' + fileCount + '_file').on('change', function () {
        fileName = $(this).prop('files')[0].name;
        $("#jsPreview" + fileCount).append(fileName);
		// Hide the add file button
		$("#jsBtnUpload" + fileCount).hide();
		// Show the remove file button
		$("#jsRemove" + fileCount).show();

        // Create another instance of add file button and company
        createAddFile(parseInt(fileCount) + 1);
    });
}

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


    $("#btnUpload" + index).on('click', function(e){
        $('#research_attachments_' + index + '_attachmentFile').trigger('click');
        return false;
	});

}

function removeFile(ob, id) {
    if (confirm('Are you sure you want to delete this item?')) {
        ob.parent().parent().remove();
    }
}

// setup an "add a tag" link
var addTagButton = $('#addTagButton');
// var $addTagButton = $('<button type="button" class="btn btn-warning">Add a attachment</button>');
var newLinkLi = $('<li class="list-group-item d-flex justify-content-between align-items-center"></li>').append(addTagButton);

jQuery(function(){
    //var fileCount = 0;
    //createAddFile(fileCount++);

    // Get the ul that holds the collection of tags
    var collectionHolder = $('ul.attachments');

    // add the "add a tag" anchor and li to the tags ul
    collectionHolder.append(newLinkLi);

    // count the current form inputs we have (e.g. 2), use that as the new
    // index when inserting a new item (e.g. 2)
    addTagButton.on('click', function (e) {
        // add a new tag form (see next code block)
        addTagForm(collectionHolder, newLinkLi);
    });

    $('.removeFile').on('click', function (e) {
        var id = this.dataset.id;
        // add a new tag form (see next code block)
        removeFile($(this), id);
    });
});
