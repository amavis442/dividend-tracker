import { Controller } from '@hotwired/stimulus';
import { ClassicEditor, Essentials, Bold, Italic, Font, Paragraph, Highlight } from 'ckeditor5';
import '../styles/ckeditor5.css';

export default class extends Controller {
    connect() {
        if (document.getElementsByClassName('ckeditor5').length > 0) {
            ClassicEditor
                .create(document.querySelector('.ckeditor5'), {
                    plugins: [Essentials, Bold, Italic, Font, Paragraph, Highlight],
                    toolbar: [
                        'undo', 'redo', '|', 'bold', 'italic', '|',
                        'fontSize', 'fontFamily', 'fontColor', 'fontBackgroundColor', 'highlight'
                    ]
                })
                .then(editor => {
                    console.log(editor);
                })
                .catch(error => {
                    console.error(error);
                });
        }
    }

}

