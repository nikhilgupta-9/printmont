<?php
/**
 * The visual templates an admin can choose for a content block.
 *
 * A content block is not pinned to one page or one shape the way the
 * page editor's schema is — it is multi-page (admin names any page_key)
 * and multi-pattern (admin picks a template and fills that template's
 * fields). This file is the only place a pattern is described: its field
 * list drives both the admin builder UI (content-blocks.js) and the
 * server-side save validation (content-blocks-action.php). Add a pattern
 * here and it is immediately choosable, nowhere else to touch.
 *
 * Field shape
 *   name      key inside the block's JSON `data`
 *   label     what the admin sees
 *   input     text | textarea | richtext | image | select | repeater
 *   fields    repeater only: the field list for each item in the group
 */

return [

    'brand_directory' => [
        'label' => 'Brand Directory (heading + searched line + categories + closing text)',
        'note'  => 'The original "Top Stories" layout — a label, a bold most-searched line, category rows, and a closing paragraph.',
        'fields' => [
            ['name' => 'heading_label', 'label' => 'Label', 'input' => 'text', 'placeholder' => 'Top Stories'],
            ['name' => 'heading_highlight', 'label' => 'Highlighted title', 'input' => 'text', 'placeholder' => 'Printmont Brand Directory'],
            ['name' => 'most_searched_label', 'label' => 'Most-searched label', 'input' => 'text', 'placeholder' => 'MOST SEARCHED ON PRINTMONT:'],
            ['name' => 'most_searched_items', 'label' => 'Most-searched items (separate with |)', 'input' => 'textarea', 'rows' => 2],
            ['name' => 'categories', 'label' => 'Category rows', 'input' => 'repeater', 'itemLabel' => 'Category', 'addLabel' => 'Add category', 'fields' => [
                ['name' => 'label', 'label' => 'Category label', 'input' => 'text', 'placeholder' => 'CUSTOM APPAREL:'],
                ['name' => 'items', 'label' => 'Items (separate with |)', 'input' => 'textarea', 'rows' => 2],
            ]],
            ['name' => 'footer_heading', 'label' => 'Closing bold heading', 'input' => 'text'],
            ['name' => 'footer_html', 'label' => 'Closing paragraph', 'input' => 'richtext'],
        ],
    ],

    'text' => [
        'label' => 'Simple text block',
        'note'  => 'A heading and a rich-text paragraph. The general-purpose pattern.',
        'fields' => [
            ['name' => 'heading', 'label' => 'Heading', 'input' => 'text'],
            ['name' => 'body_html', 'label' => 'Body', 'input' => 'richtext'],
        ],
    ],

    'faq' => [
        'label' => 'FAQ accordion',
        'note'  => 'A heading and a list of question/answer pairs.',
        'fields' => [
            ['name' => 'heading', 'label' => 'Heading', 'input' => 'text'],
            ['name' => 'items', 'label' => 'Questions', 'input' => 'repeater', 'itemLabel' => 'Question', 'addLabel' => 'Add question', 'fields' => [
                ['name' => 'question', 'label' => 'Question', 'input' => 'text'],
                ['name' => 'answer_html', 'label' => 'Answer', 'input' => 'richtext'],
            ]],
        ],
    ],

    'image_text' => [
        'label' => 'Image + text',
        'note'  => 'A picture beside a heading and rich-text paragraph.',
        'fields' => [
            ['name' => 'image_url', 'label' => 'Image', 'input' => 'image'],
            ['name' => 'layout', 'label' => 'Image position', 'input' => 'select', 'choices' => [
                ['value' => 'image-left', 'label' => 'Image on the left'],
                ['value' => 'image-right', 'label' => 'Image on the right'],
            ]],
            ['name' => 'heading', 'label' => 'Heading', 'input' => 'text'],
            ['name' => 'body_html', 'label' => 'Body', 'input' => 'richtext'],
        ],
    ],

    'link_grid' => [
        'label' => 'Link grid',
        'note'  => 'A heading and a row of labelled links — good for "most searched" style link lists.',
        'fields' => [
            ['name' => 'heading', 'label' => 'Heading', 'input' => 'text'],
            ['name' => 'links', 'label' => 'Links', 'input' => 'repeater', 'itemLabel' => 'Link', 'addLabel' => 'Add link', 'fields' => [
                ['name' => 'label', 'label' => 'Label', 'input' => 'text'],
                ['name' => 'url', 'label' => 'URL', 'input' => 'text', 'placeholder' => '/category/apparel'],
            ]],
        ],
    ],

];
