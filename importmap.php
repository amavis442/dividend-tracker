<?php

/**
 * Returns the importmap for this application.
 *
 * - "path" is a path inside the asset mapper system. Use the
 *     "debug:asset-map" command to see the full list of paths.
 *
 * - "entrypoint" (JavaScript only) set to true for any module that will
 *     be used as an "entrypoint" (and passed to the importmap() Twig function).
 *
 * The "importmap:require" command can be used to add new entries to this file.
 */
return [
    'app' => [
        'path' => './assets/app.js',
        'entrypoint' => true,
    ],
    '@symfony/stimulus-bundle' => [
        'path' => './vendor/symfony/stimulus-bundle/assets/dist/loader.js',
    ],
    '@hotwired/stimulus' => [
        'version' => '3.2.2',
    ],
    '@popperjs/core' => [
        'version' => '2.11.8',
    ],
    '@hotwired/turbo' => [
        'version' => '8.0.13',
    ],
    '@fortawesome/fontawesome-free/css/all.css' => [
        'version' => '6.7.2',
        'type' => 'css',
    ],
    '@symfony/stimulus-bridge' => [
        'version' => '4.0.1',
    ],
    'chart.js' => [
        'version' => '4.4.9',
    ],
    '@kurkle/color' => [
        'version' => '0.3.4',
    ],
    'bs-custom-file-input' => [
        'version' => '1.3.4',
    ],
    'jquery' => [
        'version' => '3.7.1',
    ],
    'ckeditor5' => [
        'version' => '45.2.0',
    ],
    '@ckeditor/ckeditor5-adapter-ckfinder/dist/index.js' => [
        'version' => '45.2.0',
    ],
    '@ckeditor/ckeditor5-alignment/dist/index.js' => [
        'version' => '45.2.0',
    ],
    '@ckeditor/ckeditor5-autoformat/dist/index.js' => [
        'version' => '45.2.0',
    ],
    '@ckeditor/ckeditor5-autosave/dist/index.js' => [
        'version' => '45.2.0',
    ],
    '@ckeditor/ckeditor5-basic-styles/dist/index.js' => [
        'version' => '45.2.0',
    ],
    '@ckeditor/ckeditor5-block-quote/dist/index.js' => [
        'version' => '45.2.0',
    ],
    '@ckeditor/ckeditor5-ckbox/dist/index.js' => [
        'version' => '45.2.0',
    ],
    '@ckeditor/ckeditor5-ckfinder/dist/index.js' => [
        'version' => '45.2.0',
    ],
    '@ckeditor/ckeditor5-clipboard/dist/index.js' => [
        'version' => '45.2.0',
    ],
    '@ckeditor/ckeditor5-cloud-services/dist/index.js' => [
        'version' => '45.2.0',
    ],
    '@ckeditor/ckeditor5-code-block/dist/index.js' => [
        'version' => '45.2.0',
    ],
    '@ckeditor/ckeditor5-core/dist/index.js' => [
        'version' => '45.2.0',
    ],
    '@ckeditor/ckeditor5-easy-image/dist/index.js' => [
        'version' => '45.2.0',
    ],
    '@ckeditor/ckeditor5-editor-balloon/dist/index.js' => [
        'version' => '45.2.0',
    ],
    '@ckeditor/ckeditor5-editor-classic/dist/index.js' => [
        'version' => '45.2.0',
    ],
    '@ckeditor/ckeditor5-editor-decoupled/dist/index.js' => [
        'version' => '45.2.0',
    ],
    '@ckeditor/ckeditor5-editor-inline/dist/index.js' => [
        'version' => '45.2.0',
    ],
    '@ckeditor/ckeditor5-editor-multi-root/dist/index.js' => [
        'version' => '45.2.0',
    ],
    '@ckeditor/ckeditor5-engine/dist/index.js' => [
        'version' => '45.2.0',
    ],
    '@ckeditor/ckeditor5-enter/dist/index.js' => [
        'version' => '45.2.0',
    ],
    '@ckeditor/ckeditor5-essentials/dist/index.js' => [
        'version' => '45.2.0',
    ],
    '@ckeditor/ckeditor5-find-and-replace/dist/index.js' => [
        'version' => '45.2.0',
    ],
    '@ckeditor/ckeditor5-font/dist/index.js' => [
        'version' => '45.2.0',
    ],
    '@ckeditor/ckeditor5-heading/dist/index.js' => [
        'version' => '45.2.0',
    ],
    '@ckeditor/ckeditor5-highlight/dist/index.js' => [
        'version' => '45.2.0',
    ],
    '@ckeditor/ckeditor5-horizontal-line/dist/index.js' => [
        'version' => '45.2.0',
    ],
    '@ckeditor/ckeditor5-html-embed/dist/index.js' => [
        'version' => '45.2.0',
    ],
    '@ckeditor/ckeditor5-html-support/dist/index.js' => [
        'version' => '45.2.0',
    ],
    '@ckeditor/ckeditor5-image/dist/index.js' => [
        'version' => '45.2.0',
    ],
    '@ckeditor/ckeditor5-indent/dist/index.js' => [
        'version' => '45.2.0',
    ],
    '@ckeditor/ckeditor5-language/dist/index.js' => [
        'version' => '45.2.0',
    ],
    '@ckeditor/ckeditor5-link/dist/index.js' => [
        'version' => '45.2.0',
    ],
    '@ckeditor/ckeditor5-list/dist/index.js' => [
        'version' => '45.2.0',
    ],
    '@ckeditor/ckeditor5-markdown-gfm/dist/index.js' => [
        'version' => '45.2.0',
    ],
    '@ckeditor/ckeditor5-media-embed/dist/index.js' => [
        'version' => '45.2.0',
    ],
    '@ckeditor/ckeditor5-mention/dist/index.js' => [
        'version' => '45.2.0',
    ],
    '@ckeditor/ckeditor5-minimap/dist/index.js' => [
        'version' => '45.2.0',
    ],
    '@ckeditor/ckeditor5-page-break/dist/index.js' => [
        'version' => '45.2.0',
    ],
    '@ckeditor/ckeditor5-paragraph/dist/index.js' => [
        'version' => '45.2.0',
    ],
    '@ckeditor/ckeditor5-paste-from-office/dist/index.js' => [
        'version' => '45.2.0',
    ],
    '@ckeditor/ckeditor5-remove-format/dist/index.js' => [
        'version' => '45.2.0',
    ],
    '@ckeditor/ckeditor5-restricted-editing/dist/index.js' => [
        'version' => '45.2.0',
    ],
    '@ckeditor/ckeditor5-select-all/dist/index.js' => [
        'version' => '45.2.0',
    ],
    '@ckeditor/ckeditor5-show-blocks/dist/index.js' => [
        'version' => '45.2.0',
    ],
    '@ckeditor/ckeditor5-source-editing/dist/index.js' => [
        'version' => '45.2.0',
    ],
    '@ckeditor/ckeditor5-special-characters/dist/index.js' => [
        'version' => '45.2.0',
    ],
    '@ckeditor/ckeditor5-style/dist/index.js' => [
        'version' => '45.2.0',
    ],
    '@ckeditor/ckeditor5-table/dist/index.js' => [
        'version' => '45.2.0',
    ],
    '@ckeditor/ckeditor5-typing/dist/index.js' => [
        'version' => '45.2.0',
    ],
    '@ckeditor/ckeditor5-ui/dist/index.js' => [
        'version' => '45.2.0',
    ],
    '@ckeditor/ckeditor5-undo/dist/index.js' => [
        'version' => '45.2.0',
    ],
    '@ckeditor/ckeditor5-upload/dist/index.js' => [
        'version' => '45.2.0',
    ],
    '@ckeditor/ckeditor5-utils/dist/index.js' => [
        'version' => '45.2.0',
    ],
    '@ckeditor/ckeditor5-watchdog/dist/index.js' => [
        'version' => '45.2.0',
    ],
    '@ckeditor/ckeditor5-widget/dist/index.js' => [
        'version' => '45.2.0',
    ],
    '@ckeditor/ckeditor5-word-count/dist/index.js' => [
        'version' => '45.2.0',
    ],
    'lodash-es' => [
        'version' => '4.17.21',
    ],
    'blurhash' => [
        'version' => '2.0.5',
    ],
    'marked' => [
        'version' => '4.0.12',
    ],
    'turndown' => [
        'version' => '7.2.0',
    ],
    'turndown-plugin-gfm' => [
        'version' => '1.0.2',
    ],
    'color-parse' => [
        'version' => '1.4.2',
    ],
    'color-convert' => [
        'version' => '2.0.1',
    ],
    'vanilla-colorful/lib/entrypoints/hex' => [
        'version' => '0.7.2',
    ],
    'color-name' => [
        'version' => '1.1.4',
    ],
    'js-confetti' => [
        'version' => '0.12.0',
    ],
    'ckeditor5/dist/ckeditor5.css' => [
        'version' => '45.2.0',
        'type' => 'css',
    ],
    'just-extend' => [
        'version' => '6.2.0',
    ],
    '@tailwindcss/forms' => [
        'version' => '0.5.10',
    ],
    'mini-svg-data-uri' => [
        'version' => '1.4.4',
    ],
    'tailwindcss/plugin' => [
        'version' => '4.1.10',
    ],
    'tailwindcss/defaultTheme' => [
        'version' => '4.1.10',
    ],
    'tailwindcss/colors' => [
        'version' => '4.1.10',
    ],
    'picocolors' => [
        'version' => '1.1.1',
    ],
    'tom-select/dist/css/tom-select.default.css' => [
        'version' => '2.4.3',
        'type' => 'css',
    ],
    '@floating-ui/dom' => [
        'version' => '1.7.1',
    ],
    '@floating-ui/core' => [
        'version' => '1.7.1',
    ],
    '@floating-ui/utils' => [
        'version' => '0.2.9',
    ],
    '@floating-ui/utils/dom' => [
        'version' => '0.2.9',
    ],
    '@stimulus-components/dropdown' => [
        'version' => '3.0.0',
    ],
    'alpinejs' => [
        'version' => '3.14.9',
    ],
    '@orchidjs/sifter' => [
        'version' => '1.1.0',
    ],
    '@orchidjs/unicode-variants' => [
        'version' => '1.1.2',
    ],
    'tom-select/dist/css/tom-select.default.min.css' => [
        'version' => '2.4.3',
        'type' => 'css',
    ],
    'sweetalert2' => [
        'version' => '11.22.0',
    ],
    'isarray' => [
        'version' => '2.0.5',
    ],
    'string_decoder' => [
        'version' => '1.3.0',
    ],
    'safe-buffer' => [
        'version' => '5.2.0',
    ],
    'stimulus-use' => [
        'version' => '0.52.3',
    ],
    'tom-select' => [
        'version' => '2.4.3',
    ],
    'byline' => [
        'version' => '5.0.0',
    ],
    'underscore.string' => [
        'version' => '3.3.6',
    ],
    'util-deprecate' => [
        'version' => '1.0.2',
    ],
    'through2' => [
        'version' => '4.0.2',
    ],
    'xtend' => [
        'version' => '4.0.2',
    ],
    'core-util-is' => [
        'version' => '1.0.3',
    ],
    'inherits' => [
        'version' => '2.0.4',
    ],
    'abort-controller' => [
        'version' => '3.0.0',
    ],
    'process' => [
        'version' => '0.11.10',
    ],
    'sprintf-js' => [
        'version' => '1.1.3',
    ],
    'readable-stream' => [
        'version' => '3.6.2',
    ],
    '@ckeditor/ckeditor5-bookmark/dist/index.js' => [
        'version' => '45.2.0',
    ],
    '@ckeditor/ckeditor5-emoji/dist/index.js' => [
        'version' => '45.2.0',
    ],
    '@ckeditor/ckeditor5-fullscreen/dist/index.js' => [
        'version' => '45.2.0',
    ],
    '@ckeditor/ckeditor5-icons/dist/index.js' => [
        'version' => '45.2.0',
    ],
    'fuzzysort' => [
        'version' => '3.1.0',
    ],
    'es-toolkit/compat' => [
        'version' => '1.32.0',
    ],
    'tom-select/dist/css/tom-select.bootstrap4.css' => [
        'version' => '2.4.3',
        'type' => 'css',
    ],
    'tom-select/dist/css/tom-select.bootstrap5.css' => [
        'version' => '2.4.3',
        'type' => 'css',
    ],
];
