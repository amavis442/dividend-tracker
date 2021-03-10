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

jQuery(function(){
    createAddFile(fileCount);
    fileCount++;
});
