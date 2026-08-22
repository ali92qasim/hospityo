import $ from 'jquery';
import * as FilePond from 'filepond';
import FilePondPluginFileValidateSize from 'filepond-plugin-file-validate-size';
import FilePondPluginFileValidateType from 'filepond-plugin-file-validate-type';
import 'filepond/dist/filepond.min.css';
import '../css/file-input.css';

FilePond.registerPlugin(
    FilePondPluginFileValidateSize,
    FilePondPluginFileValidateType,
);

const DEFAULT_MAX_SIZE = '10MB';

function shouldEnhanceInput(input) {
    if (!(input instanceof HTMLInputElement)) {
        return false;
    }

    if (input.type !== 'file') {
        return false;
    }

    if (input.dataset.filepond === 'true' || input.classList.contains('filepond--browser')) {
        return false;
    }

    if (input.hasAttribute('data-native-file-input') || input.hasAttribute('onchange')) {
        return false;
    }

    return true;
}

function buildFilePondOptions(input) {
    const options = {
        credits: false,
        storeAsFile: true,
        allowMultiple: input.hasAttribute('multiple'),
        maxFileSize: input.dataset.maxFileSize || DEFAULT_MAX_SIZE,
        labelIdle: 'Drag & drop a file or <span class="filepond--label-action">Browse</span>',
    };

    if (input.accept) {
        options.acceptedFileTypes = input.accept
            .split(',')
            .map((type) => type.trim())
            .filter(Boolean);
    }

    return options;
}

function enhanceFileInput(input) {
    if (!shouldEnhanceInput(input)) {
        return null;
    }

    input.dataset.filepond = 'true';

    return FilePond.create(input, buildFilePondOptions(input));
}

function enhanceFileInputs(root = document) {
    $(root).find('input[type="file"]').each(function enhanceEach() {
        enhanceFileInput(this);
    });
}

$(function initializeGlobalFileInputs() {
    enhanceFileInputs(document);

    const observer = new MutationObserver((mutations) => {
        mutations.forEach((mutation) => {
            mutation.addedNodes.forEach((node) => {
                if (!(node instanceof HTMLElement)) {
                    return;
                }

                if (node.matches?.('input[type="file"]')) {
                    enhanceFileInput(node);
                }

                enhanceFileInputs(node);
            });
        });
    });

    observer.observe(document.body, { childList: true, subtree: true });
});

window.FileInput = {
    enhance: enhanceFileInput,
    enhanceAll: enhanceFileInputs,
};

export { enhanceFileInput, enhanceFileInputs };
