<?php
/**
 * How each CMS page is laid out, described the way an editor sees it.
 *
 * The page_sections table is deliberately generic: title, content, extra,
 * image_path. That is fine for storage and useless for editing — nobody
 * should have to remember that "Extra" means the button label on a hero
 * row and a URL path on a tool row.
 *
 * This file closes that gap. Each page lists its bands in the order they
 * appear on the live site, each band lists its parts, and each field
 * carries the label the editor sees plus the column it is stored in. The
 * editor UI and the save endpoint are both generated from this, so a new
 * field is one entry here and nothing else.
 *
 * Field keys
 *   name        unique within the part; the form input name
 *   label       what the editor sees
 *   column      title | content | extra | image_path
 *   part        for columns holding two values joined by `separator`
 *   separator   defaults to "|"
 *   input       text | textarea | image
 *   size        image fields only: the exact pixel size to upload. Shown
 *               beside the label, because the storefront crops every image
 *               to a fixed box and a mismatched upload loses its edges.
 *   ratio       image fields only: the shape that box holds
 *   primary     use this value as the row's headline in lists
 *   secondary   show this value as the row's sub-line in lists
 *
 * Band parts
 *   kind=single  exactly one row, rendered as an inline form
 *   kind=list    many rows, rendered as a sortable list of cards
 *   match        extra column value that pins a row to this part
 */

/**
 * The two-tone band heading used across the seller page.
 *
 * Guarded because this file is a `require` that returns an array, so a
 * caller reading it twice in one request would otherwise redeclare it.
 */
if (!function_exists('pe_heading_fields')) {
    function pe_heading_fields(): array
    {
        return [
        [
            'name' => 'title', 'label' => 'Heading — first half', 'column' => 'title',
            'input' => 'text', 'required' => true, 'primary' => true,
            'placeholder' => 'Why do', 'help' => 'Shown in the dark heading colour.',
        ],
        [
            'name' => 'highlight', 'label' => 'Heading — highlighted half', 'column' => 'content', 'part' => 0,
            'input' => 'text', 'placeholder' => 'sellers love selling on Printmont?',
            'help' => 'Follows straight on from the first half, in the brand colour.',
        ],
        [
            'name' => 'intro', 'label' => 'Intro paragraph', 'column' => 'content', 'part' => 1,
            'input' => 'textarea', 'rows' => 3,
            'help' => 'A sentence or two under the heading. Leave blank to hide it.',
        ],
        ];
    }
}

return [

    // ------------------------------------------------------------------
    'become-a-seller' => [
        'label'   => 'Become a Seller',
        'note'    => 'The seller landing page — hero, benefits, stories, journey and the enquiry form.',
        'preview' => '/become-a-seller',
        'bands'   => [

            [
                'key' => 'hero', 'label' => 'Hero banner',
                'note' => 'The full-width banner at the top of the page.',
                'parts' => [[
                    'kind' => 'single', 'type' => 'hero', 'label' => 'Banner',
                    'fields' => [
                        ['name' => 'title', 'label' => 'Headline', 'column' => 'title', 'input' => 'text', 'required' => true, 'primary' => true,
                         'placeholder' => 'Sell online to crores of customers at 0% commission'],
                        ['name' => 'content', 'label' => 'Sub-line', 'column' => 'content', 'input' => 'textarea', 'rows' => 3],
                        ['name' => 'extra', 'label' => 'Button label', 'column' => 'extra', 'input' => 'text', 'placeholder' => 'Start Selling'],
                        ['name' => 'image', 'label' => 'Banner image', 'column' => 'image_path', 'input' => 'image',
                         'size' => '1920 × 640 px', 'ratio' => '3:1',
                         'help' => 'Sits behind the headline, under a dark tint. Keep faces and text away from the edges — the sides are cropped on narrow screens.'],
                    ],
                ]],
            ],

            [
                'key' => 'stats', 'label' => 'Numbers strip',
                'note' => 'The row of figures under the hero.',
                'parts' => [[
                    'kind' => 'list', 'type' => 'stat', 'label' => 'Figures', 'addLabel' => 'Add figure',
                    'fields' => [
                        ['name' => 'title', 'label' => 'Figure', 'column' => 'title', 'input' => 'text', 'required' => true, 'primary' => true,
                         'placeholder' => '10,000+'],
                        ['name' => 'content', 'label' => 'What it counts', 'column' => 'content', 'input' => 'text', 'secondary' => true,
                         'placeholder' => 'Sellers on Printmont'],
                    ],
                ]],
            ],

            [
                'key' => 'why', 'label' => 'Why sell with us',
                'note' => 'The benefit cards. Each card shows a built-in icon unless you upload an image.',
                'parts' => [
                    ['kind' => 'single', 'type' => 'heading', 'label' => 'Band heading',
                     'match' => ['extra' => 'why'], 'fields' => pe_heading_fields()],
                    ['kind' => 'list', 'type' => 'benefit', 'label' => 'Benefit cards', 'addLabel' => 'Add card',
                     'fields' => [
                        ['name' => 'title', 'label' => 'Card title', 'column' => 'title', 'input' => 'text', 'required' => true, 'primary' => true],
                        ['name' => 'content', 'label' => 'Description', 'column' => 'content', 'input' => 'textarea', 'rows' => 3, 'secondary' => true],
                        ['name' => 'image', 'label' => 'Icon image', 'column' => 'image_path', 'input' => 'image',
                         'size' => '96 × 96 px', 'ratio' => 'square',
                         'help' => 'Optional. Drawn at 28 px next to the card title, so a flat PNG on a transparent background reads best. A built-in icon is used when this is empty.'],
                     ]],
                    ['kind' => 'single', 'type' => 'aside', 'label' => 'Side panel',
                     'match' => ['extra' => 'why'],
                     'fields' => [
                        ['name' => 'title', 'label' => 'Image description', 'column' => 'title', 'input' => 'text', 'required' => true, 'primary' => true,
                         'placeholder' => 'A seller packing an order',
                         'help' => 'Read aloud by screen readers and shown if the image fails to load.'],
                        ['name' => 'image', 'label' => 'Panel image', 'column' => 'image_path', 'input' => 'image',
                         'size' => '800 × 600 px', 'ratio' => '4:3',
                         'help' => 'Fills the panel beside the benefit cards. Cropped to fit, so keep the subject centred. A shop icon is shown when this is empty.'],
                     ]],
                ],
            ],

            [
                'key' => 'stories', 'label' => 'Seller stories',
                'note' => 'Quotes from sellers, and the button underneath them.',
                'parts' => [
                    ['kind' => 'single', 'type' => 'heading', 'label' => 'Band heading',
                     'match' => ['extra' => 'stories'], 'fields' => pe_heading_fields()],
                    ['kind' => 'list', 'type' => 'story', 'label' => 'Stories', 'addLabel' => 'Add story',
                     'fields' => [
                        ['name' => 'title', 'label' => 'Seller name', 'column' => 'title', 'input' => 'text', 'required' => true, 'primary' => true],
                        ['name' => 'content', 'label' => 'Quote', 'column' => 'content', 'input' => 'textarea', 'rows' => 4, 'secondary' => true],
                        ['name' => 'extra', 'label' => 'Business name', 'column' => 'extra', 'input' => 'text', 'placeholder' => 'Sharma Prints, Jaipur'],
                        ['name' => 'image', 'label' => 'Photo', 'column' => 'image_path', 'input' => 'image',
                         'size' => '240 × 240 px', 'ratio' => 'square',
                         'help' => 'Cropped to a circle, so centre the face. The seller\'s initials are shown when this is empty.'],
                     ]],
                    ['kind' => 'single', 'type' => 'label', 'label' => 'Button under the stories',
                     'match' => ['extra' => 'stories_cta'],
                     'fields' => [
                        ['name' => 'title', 'label' => 'Button label', 'column' => 'title', 'input' => 'text', 'required' => true, 'primary' => true,
                         'placeholder' => 'See All Stories'],
                     ]],
                ],
            ],

            [
                'key' => 'journey', 'label' => 'Seller journey',
                'note' => 'The numbered steps a new seller follows.',
                'parts' => [
                    ['kind' => 'single', 'type' => 'heading', 'label' => 'Band heading',
                     'match' => ['extra' => 'journey'], 'fields' => pe_heading_fields()],
                    ['kind' => 'list', 'type' => 'journey', 'label' => 'Steps', 'addLabel' => 'Add step',
                     'fields' => [
                        ['name' => 'title', 'label' => 'Step title', 'column' => 'title', 'input' => 'text', 'required' => true, 'primary' => true],
                        ['name' => 'content', 'label' => 'What happens in this step', 'column' => 'content', 'input' => 'textarea', 'rows' => 3, 'secondary' => true],
                        ['name' => 'image', 'label' => 'Step artwork', 'column' => 'image_path', 'input' => 'image',
                         'size' => '480 × 360 px', 'ratio' => '4:3',
                         'help' => 'Cropped to fit the step tile. The step number is shown when this is empty.'],
                     ]],
                    ['kind' => 'single', 'type' => 'label', 'label' => 'Button under the steps',
                     'match' => ['extra' => 'journey_cta'],
                     'fields' => [
                        ['name' => 'title', 'label' => 'Button label', 'column' => 'title', 'input' => 'text', 'required' => true, 'primary' => true,
                         'placeholder' => 'Download Launch Kit'],
                     ]],
                ],
            ],

            [
                'key' => 'tools', 'label' => 'Growth tools',
                'note' => 'The grid of tools, and the large watermark behind it.',
                'parts' => [
                    ['kind' => 'single', 'type' => 'heading', 'label' => 'Band heading',
                     'match' => ['extra' => 'tools'], 'fields' => pe_heading_fields()],
                    ['kind' => 'list', 'type' => 'tool', 'label' => 'Tools', 'addLabel' => 'Add tool',
                     'fields' => [
                        ['name' => 'title', 'label' => 'Tool name', 'column' => 'title', 'input' => 'text', 'required' => true, 'primary' => true],
                        ['name' => 'content', 'label' => 'Description', 'column' => 'content', 'input' => 'textarea', 'rows' => 3, 'secondary' => true],
                        ['name' => 'extra', 'label' => 'Links to', 'column' => 'extra', 'input' => 'text', 'placeholder' => '/contact',
                         'help' => 'A path on your own site, such as /contact. Leave blank for no link.'],
                        ['name' => 'image', 'label' => 'Icon image', 'column' => 'image_path', 'input' => 'image',
                         'size' => '96 × 96 px', 'ratio' => 'square',
                         'help' => 'Optional. Drawn at 28 px next to the tool name. A built-in icon is used when this is empty.'],
                     ]],
                    ['kind' => 'single', 'type' => 'label', 'label' => 'Watermark behind the grid',
                     'match' => ['extra' => 'tools_watermark'],
                     'fields' => [
                        ['name' => 'title', 'label' => 'Watermark text', 'column' => 'title', 'input' => 'text', 'required' => true, 'primary' => true,
                         'placeholder' => '5x Growth', 'help' => 'Two or three words. Longer text overflows the band.'],
                     ]],
                ],
            ],

            [
                'key' => 'platform', 'label' => 'Platform preview',
                'note' => 'The slides showing the seller dashboard.',
                'parts' => [
                    ['kind' => 'single', 'type' => 'heading', 'label' => 'Band heading',
                     'match' => ['extra' => 'platform'], 'fields' => pe_heading_fields()],
                    ['kind' => 'list', 'type' => 'platform', 'label' => 'Slides', 'addLabel' => 'Add slide',
                     'fields' => [
                        ['name' => 'title', 'label' => 'Slide title', 'column' => 'title', 'input' => 'text', 'required' => true, 'primary' => true],
                        ['name' => 'content', 'label' => 'Description', 'column' => 'content', 'input' => 'textarea', 'rows' => 3, 'secondary' => true],
                        ['name' => 'extra', 'label' => 'Button label', 'column' => 'extra', 'input' => 'text'],
                        ['name' => 'image', 'label' => 'Screenshot', 'column' => 'image_path', 'input' => 'image',
                         'size' => '1280 × 720 px', 'ratio' => '16:9',
                         'help' => 'Every slide is held at 16:9, so upload all of them at the same size or the carousel will jump as it moves.'],
                     ]],
                ],
            ],

            [
                'key' => 'help', 'label' => 'Enquiry form',
                'note' => 'The form at the foot of the page and the topics it offers.',
                'parts' => [
                    ['kind' => 'single', 'type' => 'heading', 'label' => 'Band heading',
                     'match' => ['extra' => 'help'], 'fields' => pe_heading_fields()],
                    ['kind' => 'list', 'type' => 'topic', 'label' => 'Topics in the dropdown', 'addLabel' => 'Add topic',
                     'fields' => [
                        ['name' => 'title', 'label' => 'Topic', 'column' => 'title', 'input' => 'text', 'required' => true, 'primary' => true,
                         'placeholder' => 'Orders and shipping'],
                     ]],
                    ['kind' => 'single', 'type' => 'label', 'label' => 'Submit button',
                     'match' => ['extra' => 'form_submit'],
                     'fields' => [
                        ['name' => 'title', 'label' => 'Button label', 'column' => 'title', 'input' => 'text', 'required' => true, 'primary' => true,
                         'placeholder' => 'Send Query'],
                     ]],
                    ['kind' => 'single', 'type' => 'aside', 'label' => 'Side panel',
                     'match' => ['extra' => 'help'],
                     'fields' => [
                        ['name' => 'title', 'label' => 'Image description', 'column' => 'title', 'input' => 'text', 'required' => true, 'primary' => true,
                         'placeholder' => 'Our seller support team',
                         'help' => 'Read aloud by screen readers and shown if the image fails to load.'],
                        ['name' => 'image', 'label' => 'Panel image', 'column' => 'image_path', 'input' => 'image',
                         'size' => '960 × 540 px', 'ratio' => '16:9',
                         'help' => 'Fills the panel beside the enquiry form. A headset icon is shown when this is empty.'],
                     ]],
                ],
            ],
        ],
    ],

    // ------------------------------------------------------------------
    'affiliate' => [
        'label'   => 'Affiliate Program',
        'note'    => 'How the programme works, what it pays, and the questions people ask.',
        'preview' => '/affiliate-program',
        'bands'   => [
            [
                'key' => 'hero', 'label' => 'Hero banner',
                'parts' => [[
                    'kind' => 'single', 'type' => 'hero', 'label' => 'Banner',
                    'fields' => [
                        ['name' => 'title', 'label' => 'Headline', 'column' => 'title', 'input' => 'text', 'required' => true, 'primary' => true],
                        ['name' => 'content', 'label' => 'Intro', 'column' => 'content', 'input' => 'textarea', 'rows' => 3],
                        ['name' => 'extra', 'label' => 'Button label', 'column' => 'extra', 'input' => 'text', 'placeholder' => 'Join Now'],
                    ],
                ]],
            ],
            [
                'key' => 'highlights', 'label' => 'Why join',
                'note' => 'The cards under the hero.',
                'parts' => [[
                    'kind' => 'list', 'type' => 'highlight', 'label' => 'Cards', 'addLabel' => 'Add card',
                    'fields' => [
                        ['name' => 'title', 'label' => 'Card title', 'column' => 'title', 'input' => 'text', 'required' => true, 'primary' => true],
                        ['name' => 'content', 'label' => 'Description', 'column' => 'content', 'input' => 'textarea', 'rows' => 3, 'secondary' => true],
                    ],
                ]],
            ],
            [
                'key' => 'steps', 'label' => 'How it works',
                'note' => 'The numbered steps. They are numbered in the order shown here.',
                'parts' => [[
                    'kind' => 'list', 'type' => 'step', 'label' => 'Steps', 'addLabel' => 'Add step',
                    'fields' => [
                        ['name' => 'title', 'label' => 'Step title', 'column' => 'title', 'input' => 'text', 'required' => true, 'primary' => true],
                        ['name' => 'content', 'label' => 'What happens in this step', 'column' => 'content', 'input' => 'textarea', 'rows' => 3, 'secondary' => true],
                    ],
                ]],
            ],
            [
                'key' => 'rates', 'label' => 'Commission table',
                'parts' => [[
                    'kind' => 'list', 'type' => 'rate', 'label' => 'Rows', 'addLabel' => 'Add row',
                    'fields' => [
                        ['name' => 'title', 'label' => 'Category', 'column' => 'title', 'input' => 'text', 'required' => true, 'primary' => true,
                         'placeholder' => 'Corporate gifting'],
                        ['name' => 'extra', 'label' => 'Commission', 'column' => 'extra', 'input' => 'text', 'secondary' => true,
                         'placeholder' => '8%'],
                    ],
                ]],
            ],
            [
                'key' => 'faq', 'label' => 'Questions',
                'parts' => [[
                    'kind' => 'list', 'type' => 'faq', 'label' => 'Questions', 'addLabel' => 'Add question',
                    'fields' => [
                        ['name' => 'title', 'label' => 'Question', 'column' => 'title', 'input' => 'text', 'required' => true, 'primary' => true],
                        ['name' => 'content', 'label' => 'Answer', 'column' => 'content', 'input' => 'textarea', 'rows' => 4, 'secondary' => true],
                    ],
                ]],
            ],
            [
                'key' => 'contact', 'label' => 'Closing line',
                'note' => 'The sentence and email address at the foot of the page.',
                'parts' => [[
                    'kind' => 'single', 'type' => 'contact', 'label' => 'Closing line',
                    'fields' => [
                        ['name' => 'title', 'label' => 'Sentence', 'column' => 'title', 'input' => 'textarea', 'rows' => 2, 'required' => true, 'primary' => true],
                        ['name' => 'extra', 'label' => 'Email address', 'column' => 'extra', 'input' => 'text', 'placeholder' => 'affiliates@printmont.com'],
                    ],
                ]],
            ],
        ],
    ],

    // ------------------------------------------------------------------
    'business-solutions' => [
        'label'   => 'Business Solutions',
        'note'    => 'Services offered to companies, how an order runs, and who to contact.',
        'preview' => '/business-solutions',
        'bands'   => [
            [
                'key' => 'hero', 'label' => 'Hero banner',
                'parts' => [[
                    'kind' => 'single', 'type' => 'hero', 'label' => 'Banner',
                    'fields' => [
                        ['name' => 'title', 'label' => 'Headline', 'column' => 'title', 'input' => 'text', 'required' => true, 'primary' => true],
                        ['name' => 'content', 'label' => 'Intro', 'column' => 'content', 'input' => 'textarea', 'rows' => 3],
                        ['name' => 'extra', 'label' => 'Button label', 'column' => 'extra', 'input' => 'text', 'placeholder' => 'Talk to Us'],
                    ],
                ]],
            ],
            [
                'key' => 'features', 'label' => 'What we offer',
                'parts' => [[
                    'kind' => 'list', 'type' => 'feature', 'label' => 'Service cards', 'addLabel' => 'Add service',
                    'fields' => [
                        ['name' => 'title', 'label' => 'Service', 'column' => 'title', 'input' => 'text', 'required' => true, 'primary' => true],
                        ['name' => 'content', 'label' => 'Description', 'column' => 'content', 'input' => 'textarea', 'rows' => 3, 'secondary' => true],
                    ],
                ]],
            ],
            [
                'key' => 'steps', 'label' => 'How it works',
                'parts' => [[
                    'kind' => 'list', 'type' => 'step', 'label' => 'Steps', 'addLabel' => 'Add step',
                    'fields' => [
                        ['name' => 'title', 'label' => 'Step title', 'column' => 'title', 'input' => 'text', 'required' => true, 'primary' => true],
                        ['name' => 'content', 'label' => 'What happens in this step', 'column' => 'content', 'input' => 'textarea', 'rows' => 3, 'secondary' => true],
                    ],
                ]],
            ],
            [
                'key' => 'contact', 'label' => 'Closing line',
                'parts' => [[
                    'kind' => 'single', 'type' => 'contact', 'label' => 'Closing line',
                    'fields' => [
                        ['name' => 'title', 'label' => 'Sentence', 'column' => 'title', 'input' => 'textarea', 'rows' => 2, 'required' => true, 'primary' => true],
                        ['name' => 'extra', 'label' => 'Email address', 'column' => 'extra', 'input' => 'text', 'placeholder' => 'business@printmont.com'],
                    ],
                ]],
            ],
        ],
    ],

    // ------------------------------------------------------------------
    // Pages below live in their own tables. The `source` block maps this
    // editor's four canonical fields onto whatever the table calls them.
    // ------------------------------------------------------------------

    'about-us' => [
        'label'   => 'About Us',
        'note'    => 'The story, mission, figures and awards on the About page.',
        'preview' => '/about',
        'source'  => [
            'table'   => 'about_us',
            'key'     => null,          // the table holds one page only
            'columns' => [
                'title'   => 'section_title',
                'content' => 'section_content',
                'extra'   => null,      // no such column here
                'type'    => 'section_type',
            ],
        ],
        'bands' => [
            [
                'key' => 'intro', 'label' => 'Opening',
                'note' => 'The banner at the top of the page.',
                'parts' => [[
                    'kind' => 'single', 'type' => 'hero', 'label' => 'Banner',
                    'fields' => [
                        ['name' => 'title', 'label' => 'Headline', 'column' => 'title', 'input' => 'text', 'required' => true, 'primary' => true],
                        ['name' => 'content', 'label' => 'Introduction', 'column' => 'content', 'input' => 'textarea', 'rows' => 4],
                        ['name' => 'image', 'label' => 'Banner image', 'column' => 'image_path', 'input' => 'image',
                         'size' => '1920 × 640 px', 'ratio' => '3:1'],
                    ],
                ]],
            ],
            [
                'key' => 'mission', 'label' => 'Mission and vision',
                'note' => 'The two-column blocks under the banner.',
                'parts' => [
                    ['kind' => 'list', 'type' => 'mission', 'label' => 'Mission blocks', 'addLabel' => 'Add block',
                     'fields' => [
                        ['name' => 'title', 'label' => 'Heading', 'column' => 'title', 'input' => 'text', 'required' => true, 'primary' => true],
                        ['name' => 'content', 'label' => 'Text', 'column' => 'content', 'input' => 'textarea', 'rows' => 5, 'secondary' => true],
                        ['name' => 'image', 'label' => 'Image', 'column' => 'image_path', 'input' => 'image',
                         'size' => '800 × 600 px', 'ratio' => '4:3'],
                     ]],
                    ['kind' => 'single', 'type' => 'history', 'label' => 'Vision',
                     'fields' => [
                        ['name' => 'title', 'label' => 'Heading', 'column' => 'title', 'input' => 'text', 'required' => true, 'primary' => true],
                        ['name' => 'content', 'label' => 'Text', 'column' => 'content', 'input' => 'textarea', 'rows' => 5],
                        ['name' => 'image', 'label' => 'Image', 'column' => 'image_path', 'input' => 'image',
                         'size' => '800 × 600 px', 'ratio' => '4:3'],
                     ]],
                    ['kind' => 'single', 'type' => 'values', 'label' => 'Values',
                     'fields' => [
                        ['name' => 'title', 'label' => 'Heading', 'column' => 'title', 'input' => 'text', 'required' => true, 'primary' => true],
                        ['name' => 'content', 'label' => 'The values, one per line', 'column' => 'content', 'input' => 'textarea', 'rows' => 6,
                         'help' => 'Start each line with • to match the current styling.'],
                     ]],
                ],
            ],
            [
                'key' => 'story', 'label' => 'Our story',
                'parts' => [[
                    'kind' => 'single', 'type' => 'story', 'label' => 'Story',
                    'fields' => [
                        ['name' => 'title', 'label' => 'Heading', 'column' => 'title', 'input' => 'text', 'required' => true, 'primary' => true],
                        ['name' => 'content', 'label' => 'Text', 'column' => 'content', 'input' => 'textarea', 'rows' => 6],
                        ['name' => 'image', 'label' => 'Image', 'column' => 'image_path', 'input' => 'image',
                         'size' => '960 × 540 px', 'ratio' => '16:9'],
                    ],
                ]],
            ],
            [
                'key' => 'reach', 'label' => 'Reach and figures',
                'note' => 'The highlight strip and the numbers under it.',
                'parts' => [
                    ['kind' => 'single', 'type' => 'highlight', 'label' => 'Highlight strip',
                     'fields' => [
                        ['name' => 'title', 'label' => 'Headline', 'column' => 'title', 'input' => 'text', 'required' => true, 'primary' => true],
                        ['name' => 'content', 'label' => 'Text', 'column' => 'content', 'input' => 'textarea', 'rows' => 4],
                        ['name' => 'image', 'label' => 'Background image', 'column' => 'image_path', 'input' => 'image',
                         'size' => '1920 × 640 px', 'ratio' => '3:1'],
                     ]],
                    ['kind' => 'list', 'type' => 'stat', 'label' => 'Figures', 'addLabel' => 'Add figure',
                     'fields' => [
                        ['name' => 'title', 'label' => 'Figure', 'column' => 'title', 'input' => 'text', 'required' => true, 'primary' => true,
                         'placeholder' => '12 M+'],
                        ['name' => 'content', 'label' => 'What it counts', 'column' => 'content', 'input' => 'text', 'secondary' => true,
                         'placeholder' => 'deliveries worldwide'],
                     ]],
                ],
            ],
            [
                'key' => 'features', 'label' => 'What we offer',
                'parts' => [[
                    'kind' => 'list', 'type' => 'feature', 'label' => 'Feature cards', 'addLabel' => 'Add card',
                    'fields' => [
                        ['name' => 'title', 'label' => 'Card title', 'column' => 'title', 'input' => 'text', 'required' => true, 'primary' => true],
                        ['name' => 'content', 'label' => 'Description', 'column' => 'content', 'input' => 'textarea', 'rows' => 3, 'secondary' => true],
                        ['name' => 'image', 'label' => 'Icon image', 'column' => 'image_path', 'input' => 'image',
                         'size' => '96 × 96 px', 'ratio' => 'square'],
                    ],
                ]],
            ],
            [
                'key' => 'accolades', 'label' => 'Awards',
                'note' => 'The laurel row of awards near the foot of the page.',
                'parts' => [[
                    'kind' => 'list', 'type' => 'accolade', 'label' => 'Awards', 'addLabel' => 'Add award',
                    'fields' => [
                        ['name' => 'title', 'label' => 'Award', 'column' => 'title', 'input' => 'text', 'required' => true, 'primary' => true],
                        ['name' => 'content', 'label' => 'Year or awarding body', 'column' => 'content', 'input' => 'text', 'secondary' => true],
                    ],
                ]],
            ],
        ],
    ],

    'faq' => [
        'label'   => 'FAQ',
        'note'    => 'Questions and the categories they are filed under.',
        'preview' => '/faq',
        'bands'   => [
            [
                'key' => 'questions', 'label' => 'Questions',
                'note' => 'Drag to set the order they appear in.',
                'parts' => [[
                    'kind' => 'list', 'type' => null, 'label' => 'Questions', 'addLabel' => 'Add question',
                    'source' => [
                        'table'   => 'faqs',
                        'key'     => null,
                        'columns' => [
                            'title'      => 'question',
                            'content'    => 'answer',
                            'extra'      => 'category_id',
                            'image_path' => null,
                            'type'       => null,
                            'active'     => 'is_active',
                        ],
                    ],
                    'fields' => [
                        ['name' => 'title', 'label' => 'Question', 'column' => 'title', 'input' => 'text', 'required' => true, 'primary' => true],
                        ['name' => 'content', 'label' => 'Answer', 'column' => 'content', 'input' => 'textarea', 'rows' => 6, 'secondary' => true],
                        ['name' => 'extra', 'label' => 'Category', 'column' => 'extra', 'input' => 'select',
                         'options' => ['table' => 'faq_categories', 'value' => 'id', 'label' => 'name', 'order' => 'name'],
                         'help' => 'Manage the list of categories on the FAQ Categories screen.'],
                    ],
                ]],
            ],
        ],
    ],

    /**
     * Help Center shows FAQ categories with their questions nested inside —
     * help-center-api.php reads faq_categories and faqs, the same tables the
     * FAQ page uses. The help_categories, help_articles and help_faqs tables
     * exist but nothing on the storefront reads them, so editing those would
     * change nothing a visitor can see.
     */
    'help-center' => [
        'label'   => 'Help Center',
        'note'    => 'Categories and the questions filed under them. A category only appears on the site once it has at least one visible question.',
        'preview' => '/help-center',
        'bands'   => [
            [
                'key' => 'categories', 'label' => 'Categories',
                'note' => 'The list visitors pick from. Shared with the FAQ page.',
                'parts' => [[
                    'kind' => 'list', 'type' => null, 'label' => 'Categories', 'addLabel' => 'Add category',
                    'source' => [
                        'table'   => 'faq_categories',
                        'key'     => null,
                        'columns' => [
                            'title'      => 'name',
                            'content'    => 'description',
                            'extra'      => 'type',
                            'image_path' => null,
                            'type'       => null,
                            'active'     => 'is_active',
                        ],
                        // The table requires a slug; it is only the name again.
                        'derived' => ['slug' => 'title'],
                    ],
                    'fields' => [
                        ['name' => 'title', 'label' => 'Category name', 'column' => 'title', 'input' => 'text', 'required' => true, 'primary' => true],
                        ['name' => 'content', 'label' => 'Description', 'column' => 'content', 'input' => 'textarea', 'rows' => 3, 'secondary' => true],
                        ['name' => 'extra', 'label' => 'Reference key', 'column' => 'extra', 'input' => 'text',
                         'placeholder' => 'general', 'help' => 'Lower-case, dashes instead of spaces.'],
                    ],
                ]],
            ],
            [
                'key' => 'questions', 'label' => 'Questions',
                'note' => 'The same questions as the FAQ page — editing one changes both.',
                'parts' => [[
                    'kind' => 'list', 'type' => null, 'label' => 'Questions', 'addLabel' => 'Add question',
                    'source' => [
                        'table'   => 'faqs',
                        'key'     => null,
                        'columns' => [
                            'title'      => 'question',
                            'content'    => 'answer',
                            'extra'      => 'category_id',
                            'image_path' => null,
                            'type'       => null,
                            'active'     => 'is_active',
                        ],
                    ],
                    'fields' => [
                        ['name' => 'title', 'label' => 'Question', 'column' => 'title', 'input' => 'text', 'required' => true, 'primary' => true],
                        ['name' => 'content', 'label' => 'Answer', 'column' => 'content', 'input' => 'textarea', 'rows' => 6, 'secondary' => true],
                        ['name' => 'extra', 'label' => 'Category', 'column' => 'extra', 'input' => 'select', 'required' => true,
                         'options' => ['table' => 'faq_categories', 'value' => 'id', 'label' => 'name', 'order' => 'display_order']],
                    ],
                ]],
            ],
        ],
    ],

    'security' => [
        'label'   => 'Security Page',
        'note'    => 'The payment-security questions and answers.',
        'preview' => '/security',
        'bands'   => [
            [
                'key' => 'sections', 'label' => 'Questions',
                'parts' => [[
                    'kind' => 'list', 'type' => null, 'label' => 'Questions', 'addLabel' => 'Add question',
                    'source' => [
                        'table'      => 'security_sections',
                        'key'        => null,
                        'columns'    => [
                            'title'      => 'heading',
                            'content'    => 'content',
                            'extra'      => 'section_key',
                            'image_path' => null,
                            'type'       => null,
                            'order'      => 'sort_order',
                            'active'     => 'status',
                        ],
                        'active_on'  => 'active',
                        'active_off' => 'inactive',
                    ],
                    'fields' => [
                        ['name' => 'title', 'label' => 'Question', 'column' => 'title', 'input' => 'text', 'required' => true, 'primary' => true],
                        ['name' => 'content', 'label' => 'Answer', 'column' => 'content', 'input' => 'html', 'rows' => 8, 'secondary' => true,
                         'help' => 'Plain text or simple HTML.'],
                        ['name' => 'extra', 'label' => 'Reference key', 'column' => 'extra', 'input' => 'text',
                         'placeholder' => 'card-storage',
                         'help' => 'Lower-case, dashes instead of spaces. Used to link to this answer.'],
                    ],
                ]],
            ],
        ],
    ],
];
