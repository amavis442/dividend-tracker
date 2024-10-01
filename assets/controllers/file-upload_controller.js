import { Controller } from '@hotwired/stimulus';


class FileUpload {
    addTagButton;
    newLinkLi;
    collectionHolder;
    index;
    template;
    liTemplate = '<li class="list-group-item d-flex justify-content-between align-items-center">__name__</li>';




    constructor() {
        this.addTagButton = document.getElementById('addTagButton');
        this.newLinkLi = '';
        this.collectionHolder = document.querySelector('ul.attachments');
        this.index = this.collectionHolder.dataset.index;
        //this.template = this.collectionHolder.dataset.prototype;


        //var buttonLi = this.liTemplate;
        //buttonLi = buttonLi.replace(/__name__/g, this.addTagButton.outerHTML);
        //this.collectionHolder.innerHTML += buttonLi;

        this.init();
    }


    addTagForm() {
        // Get the data-prototype explained earlier

        // get the new index
        var newForm = this.template;
        // You need this only if you didn't set 'label' => false in your tags field in TaskType
        // Replace '__name__label__' in the prototype's HTML to
        // instead be a number based on how many items we have
        // newForm = newForm.replace(/__name__label__/g, index);

        // Replace '__name__' in the prototype's HTML to
        // instead be a number based on how many items we have
        newForm = newForm.replace(/__name__/g, this.index);

        console.log(newForm);

        var newFormLi = '<li id="input_li_' + this.index + '" class="list-group-item d-flex justify-content-between align-items-center">' + newForm + '</li>';
        console.log(newFormLi);
        // Display the form in the page in an li, before the "Add a tag" link li

        // var newFormLi = $('<li class="list-group-item d-flex justify-content-between align-items-center"></li>').append(newForm);
        //this.newLinkLi.before(newFormLi);
        this.collectionHolder.innerHTML = newFormLi + this.collectionHolder.innerHTML;

        const index = this.index;
        document.querySelector("#btnUpload" + index).addEventListener('click', (event) => {
            event.preventDefault();
            //console.log('You clicked me: ', index);
            const inputFile = document.getElementById('research_attachments_' + index + '_attachmentFile');
            //console.log(inputFile);
            inputFile.click();
            return false;

        })

        document.getElementById('remove_row_' + index).addEventListener('click', (event) => {
            event.preventDefault();
            var li = document.getElementById('input_li_' + index);
            li.parentNode.removeChild(li);
            //event.target.removeEventListener('click', event);

            return false;
        })
        this.index = +1;
    }

    removeFile(ob) {
        if (confirm('Are you sure you want to delete this item?')) {
            ob.parent().parent().remove();
        }
    }

    removeRow(ob) {
        ob.parent().remove();
    }

    init() {
        document.querySelectorAll('.removeFile').forEach((element) => {
            element.addEventListener('click', (event) => {
                event.preventDefault();
                // add a new tag form (see next code block)
                this.removeFile(event.target);
            });
        });

        if (this.addTagButton) {
            this.addTagButton.addEventListener('click', (event) => {
                this.addTagForm();
            });
        }
    }

    /*
    $(function () {
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


    });
    */
}

export default class extends Controller {
    connect() {
        const fileupl = new FileUpload();
    }

}
