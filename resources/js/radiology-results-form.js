import $ from 'jquery';
import {
    ClassicEditor,
    Essentials,
    Bold,
    Italic,
    Paragraph,
    Heading,
    List,
    Link,
    BlockQuote,
} from 'ckeditor5';
import 'ckeditor5/ckeditor5.css';

const EDITOR_CONFIG = {
    licenseKey: 'GPL',
    plugins: [
        Essentials,
        Bold,
        Italic,
        Paragraph,
        Heading,
        List,
        Link,
        BlockQuote,
    ],
    toolbar: [
        'undo',
        'redo',
        '|',
        'heading',
        '|',
        'bold',
        'italic',
        'link',
        'bulletedList',
        'numberedList',
        'blockQuote',
    ],
    heading: {
        options: [
            { model: 'paragraph', title: 'Paragraph', class: 'ck-heading_paragraph' },
            { model: 'heading3', view: 'h3', title: 'Heading', class: 'ck-heading_heading3' },
        ],
    },
};

async function initRichTextEditor(textarea) {
    if (!(textarea instanceof HTMLTextAreaElement)) {
        return null;
    }

    if (textarea.dataset.richTextEditor === 'true') {
        return null;
    }

    textarea.dataset.richTextEditor = 'true';

    const config = {
        ...EDITOR_CONFIG,
        placeholder: textarea.getAttribute('placeholder') || '',
    };

    const editor = await ClassicEditor.create(textarea, config);

    const form = textarea.closest('form');

    if (form) {
        form.addEventListener('submit', () => {
            textarea.value = editor.getData();
        });
    }

    editor.model.document.on('change:data', () => {
        textarea.value = editor.getData();
    });

    return editor;
}

async function initRichTextEditors(root = document) {
    const textareas = $(root).find('textarea.rich-text-editor').get();

    await Promise.all(
        textareas.map((textarea) => initRichTextEditor(textarea).catch((error) => {
            console.error('Rich text editor init failed:', error);
            textarea.dataset.richTextEditor = 'false';
            return null;
        })),
    );
}

$(function initializeRichTextEditors() {
    initRichTextEditors(document);
});

window.RichTextEditor = {
    init: initRichTextEditor,
    initAll: initRichTextEditors,
};

export { initRichTextEditor, initRichTextEditors };
