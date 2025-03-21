import { Controller } from '@hotwired/stimulus';
import {
    ClassicEditor,
    Essentials,
    Bold,
    Italic,
    Font,
    Paragraph,
    Highlight,
    Link,
    Heading,
    Table,
    Image,
    ImageCaption,
    ImageResize,
    ImageStyle,
    ImageToolbar,
    ImageUpload,
} from 'ckeditor5';

const LICENSE_KEY = 'GPL';

export default class extends Controller {
    connect() {
        if (document.getElementsByClassName('ckeditor5').length > 0) {
            ClassicEditor
                .create(document.querySelector('.ckeditor5'), {
                    licenseKey: LICENSE_KEY,
                    plugins: [
                        Heading,
                        Essentials,
                        Bold,
                        Italic,
                        Font,
                        Paragraph,
                        Highlight,
                        Link,
                        Table,
                        Image,
                        ImageCaption,
                        ImageResize,
                        ImageStyle,
                        ImageToolbar,
                        ImageUpload,
                    ],
                    toolbar: {
                        shouldNotGroupWhenFull: true,
                        items: [
                            'undo', 'redo', '|', 'heading', '|', 'bold', 'italic', '|',
                            'fontSize', 'fontFamily', 'fontColor', 'fontBackgroundColor', '|', 'link', '|', 'highlight',
                            'insertImage', 'insertTable'
                        ]
                    },
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

