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
                'key' => 'hero', 'label' => 'Hero',
                'note' => 'Headline and sub-line beside the sign-up card. The picture above it is managed in Page Banners → Become a Seller → Top Banner.',
                'parts' => [[
                    'kind' => 'single', 'type' => 'hero', 'label' => 'Hero text',
                    'fields' => [
                        ['name' => 'title', 'label' => 'Headline', 'column' => 'title', 'input' => 'text', 'required' => true, 'primary' => true,
                         'placeholder' => 'Sell online to crores of customers at 0% commission'],
                        ['name' => 'content', 'label' => 'Sub-line', 'column' => 'content', 'input' => 'textarea', 'rows' => 3],
                        ['name' => 'extra', 'label' => 'Button label', 'column' => 'extra', 'input' => 'text', 'placeholder' => 'Start Selling'],
                        ['name' => 'image', 'label' => 'Banner image', 'column' => 'image_path', 'input' => 'image',
                         'size' => '1920 × 640 px', 'ratio' => '3:1',
                         'help' => 'Sits behind the headline, under a dark tint. Keep faces and text away from the edges — the sides are cropped on narrow screens.'],
                    ],
                ],
                [
                    'kind' => 'list', 'type' => 'hero_point', 'label' => 'Quick highlights', 'addLabel' => 'Add highlight',
                    'fields' => [
                        ['name' => 'title', 'label' => 'Highlight', 'column' => 'title', 'input' => 'text', 'required' => true, 'primary' => true,
                         'placeholder' => '0% Marketplace Commission'],
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

            [
                'key' => 'categories', 'label' => 'Popular categories',
                'note' => 'The category cards. Clicking one pre-selects it in the registration form.',
                'parts' => [
                    ['kind' => 'single', 'type' => 'heading', 'label' => 'Band heading',
                     'match' => ['extra' => 'categories'], 'fields' => pe_heading_fields()],
                    ['kind' => 'list', 'type' => 'category', 'label' => 'Categories', 'addLabel' => 'Add category',
                     'fields' => [
                        ['name' => 'title', 'label' => 'Category name', 'column' => 'title', 'input' => 'text', 'required' => true, 'primary' => true],
                        ['name' => 'content', 'label' => 'Description', 'column' => 'content', 'input' => 'textarea', 'rows' => 2, 'secondary' => true],
                        ['name' => 'extra', 'label' => 'Margin tag', 'column' => 'extra', 'input' => 'text', 'placeholder' => '35-50% Margin'],
                     ]],
                ],
            ],

            [
                'key' => 'documents', 'label' => 'Documents needed',
                'note' => 'The checklist of what a seller needs to start.',
                'parts' => [
                    ['kind' => 'single', 'type' => 'heading', 'label' => 'Band heading',
                     'match' => ['extra' => 'documents'], 'fields' => pe_heading_fields()],
                    ['kind' => 'list', 'type' => 'document', 'label' => 'Checklist', 'addLabel' => 'Add item',
                     'fields' => [
                        ['name' => 'title', 'label' => 'Item', 'column' => 'title', 'input' => 'text', 'required' => true, 'primary' => true],
                        ['name' => 'content', 'label' => 'Description', 'column' => 'content', 'input' => 'textarea', 'rows' => 2, 'secondary' => true],
                     ]],
                ],
            ],

            [
                'key' => 'faqs', 'label' => 'Frequently asked questions',
                'note' => 'Accordion questions and answers for sellers.',
                'parts' => [
                    ['kind' => 'single', 'type' => 'heading', 'label' => 'Band heading',
                     'match' => ['extra' => 'faqs'], 'fields' => pe_heading_fields()],
                    ['kind' => 'list', 'type' => 'faq', 'label' => 'Questions', 'addLabel' => 'Add question',
                     'fields' => [
                        ['name' => 'title', 'label' => 'Question', 'column' => 'title', 'input' => 'text', 'required' => true, 'primary' => true],
                        ['name' => 'content', 'label' => 'Answer', 'column' => 'content', 'input' => 'textarea', 'rows' => 4, 'secondary' => true],
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
                'key' => 'contact', 'label' => 'Closing CTA & Contact',
                'note' => 'The headline sentence, support description, and affiliate email address at the foot of the page.',
                'parts' => [[
                    'kind' => 'single', 'type' => 'contact', 'label' => 'Closing Call-to-Action',
                    'fields' => [
                        ['name' => 'title', 'label' => 'Closing Heading / Sentence', 'column' => 'title', 'input' => 'textarea', 'rows' => 2, 'required' => true, 'primary' => true, 'placeholder' => 'To become our partner, please contact our Affiliate Team.'],
                        ['name' => 'content', 'label' => 'Subtitle / Support Note', 'column' => 'content', 'input' => 'textarea', 'rows' => 2, 'secondary' => true, 'placeholder' => 'Our affiliate managers will help you get set up with custom links, banners, and tailored coupon codes.'],
                        ['name' => 'extra', 'label' => 'Affiliate Support Email', 'column' => 'extra', 'input' => 'text', 'placeholder' => 'support@printmont.com'],
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
        'note'    => 'Manage banners, carousels, split rows, dynamic custom sections (add/remove), image left/right alignment, vision & mission, accolades, team, and stats.',
        'preview' => '/about',
        'source'  => [
            'table'   => 'about_us',
            'key'     => null,
            'columns' => [
                'id'      => 'id',
                'title'   => 'section_title',
                'content' => 'section_content',
                'extra'   => 'extra',
                'image_path' => 'image_path',
                'type'    => 'section_type',
                'order'   => 'display_order',
                'active'  => 'is_active',
            ],
        ],
        'bands' => [
            // ============================================================
            // TAB 1: SECTION EXCHANGE & LAYOUT MANAGEMENT
            // ============================================================
            [
                'key' => 'layout_sections',
                'label' => '📐 Section Exchange & Layout',
                'note' => 'Add new sections, remove sections, toggle visibility, and drag to exchange / reorder their position on the page.',
                'parts' => [
                    [
                        'kind' => 'list', 'type' => null, 'label' => 'All Page Sections (Drag to Exchange Order)', 'addLabel' => 'Add New Section',
                        'fields' => [
                            ['name' => 'title', 'label' => 'Section Heading / Title', 'column' => 'title', 'input' => 'text', 'required' => true, 'primary' => true,
                             'placeholder' => 'How it all started'],
                            ['name' => 'type', 'label' => '📑 Section Type / Layout', 'column' => 'type', 'input' => 'select',
                             'choices' => [
                                 ['value' => 'story', 'label' => '📖 Story Split Row 1 (How it all started)'],
                                 ['value' => 'slogan', 'label' => '💡 Highlights Slogan Block (Split Row 2)'],
                                 ['value' => 'highlight', 'label' => '🌐 Global Fulfillment Feature Card (Split Row 3)'],
                                 ['value' => 'story_2', 'label' => '📖 Craft & Delivery Story Block (Split Row 4)'],
                                 ['value' => 'banner', 'label' => '🖼️ Side Banner Block'],
                                 ['value' => 'normal_text', 'label' => '📄 Long Story / Rich Article Text'],
                                 ['value' => 'feature', 'label' => '🌟 Value Pillar Card'],
                             ],
                             'help' => 'Choose how this section should render on the website.'],
                            ['name' => 'extra', 'label' => '🖼️ Desktop Layout Direction', 'column' => 'extra', 'input' => 'select',
                             'choices' => [
                                 ['value' => 'right', 'label' => '👉 Text on Left · Image / Feature on Right (Default)'],
                                 ['value' => 'left', 'label' => '👈 Image / Feature on Left · Text on Right (Cross / Flipped)'],
                             ],
                             'help' => 'Choose whether the image appears on the left or right side on desktop.'],
                            ['name' => 'content', 'label' => 'Section Content / Slogan Text / HTML', 'column' => 'content', 'input' => 'textarea', 'rows' => 4, 'secondary' => true,
                             'placeholder' => 'What began as a single print outlet fuelled by passion has grown into a nationwide network delivering custom prints, corporate gifts and promotional merchandise across India and beyond.'],
                            ['name' => 'image', 'label' => 'Section Image / Banner (Optional)', 'column' => 'image_path', 'input' => 'image',
                             'size' => '960 × 600 px', 'ratio' => '16:10'],
                        ],
                    ],
                ],
            ],

            // ============================================================
            // TAB 2: STORY & CONTENT BLOCKS (TEXT & LEFT/RIGHT ALIGNMENT)
            // ============================================================
            [
                'key' => 'story_content',
                'label' => '✍️ Story & Content Blocks',
                'note' => 'Edit Hero banner, split stories, slogans, global fulfillment card, vision & mission, and long article text.',
                'parts' => [
                    [
                        'kind' => 'single', 'type' => 'hero', 'label' => 'Top Hero Headline & Banner',
                        'fields' => [
                            ['name' => 'title', 'label' => 'Hero Headline', 'column' => 'title', 'input' => 'text', 'required' => true, 'primary' => true,
                             'placeholder' => 'Delivering Excellence in Custom Printing & Corporate Gifting'],
                            ['name' => 'content', 'label' => 'Intro Sub-line', 'column' => 'content', 'input' => 'textarea', 'rows' => 3,
                             'placeholder' => 'Discover how Printmont empowers businesses and individuals across India.'],
                            ['name' => 'image', 'label' => 'Hero Banner Image (Fallback)', 'column' => 'image_path', 'input' => 'image',
                             'size' => '1920 × 640 px', 'ratio' => '3:1'],
                        ],
                    ],
                    [
                        'kind' => 'single', 'type' => 'story', 'label' => 'Split Row 1: How It All Started',
                        'fields' => [
                            ['name' => 'title', 'label' => 'Heading', 'column' => 'title', 'input' => 'text', 'required' => true, 'primary' => true,
                             'placeholder' => 'How it all started'],
                            ['name' => 'content', 'label' => 'Slogan / Text', 'column' => 'content', 'input' => 'textarea', 'rows' => 4,
                             'placeholder' => 'What began as a single print outlet fuelled by passion has grown into a nationwide network delivering custom prints, corporate gifts and promotional merchandise across India and beyond.'],
                            ['name' => 'extra', 'label' => '🖼️ Desktop Layout Direction', 'column' => 'extra', 'input' => 'select',
                             'choices' => [
                                 ['value' => 'left', 'label' => '👈 Image on Left · Text on Right (Cross / Flipped)'],
                                 ['value' => 'right', 'label' => '👉 Text on Left · Image on Right (Default)'],
                             ]],
                            ['name' => 'image', 'label' => 'Side Banner Image (Optional)', 'column' => 'image_path', 'input' => 'image',
                             'size' => '960 × 600 px', 'ratio' => '16:10'],
                        ],
                    ],
                    [
                        'kind' => 'single', 'type' => 'slogan', 'label' => 'Split Row 2: Highlights Slogan Block',
                        'fields' => [
                            ['name' => 'title', 'label' => 'Heading', 'column' => 'title', 'input' => 'text', 'required' => true, 'primary' => true,
                             'placeholder' => 'Highlights Slogn Text.'],
                            ['name' => 'content', 'label' => 'Slogan Text', 'column' => 'content', 'input' => 'textarea', 'rows' => 4,
                             'placeholder' => 'What began as a single print outlet fuelled by passion has grown into a nationwide network delivering custom prints, corporate gifts and promotional merchandise across India and beyond.'],
                            ['name' => 'extra', 'label' => '🖼️ Desktop Layout Direction', 'column' => 'extra', 'input' => 'select',
                             'choices' => [
                                 ['value' => 'right', 'label' => '👉 Text on Left · Carousel / Banner on Right (Default)'],
                                 ['value' => 'left', 'label' => '👈 Carousel / Banner on Left · Text on Right (Cross / Flipped)'],
                             ]],
                            ['name' => 'image', 'label' => 'Side Banner Image (Optional)', 'column' => 'image_path', 'input' => 'image',
                             'size' => '960 × 600 px', 'ratio' => '16:10'],
                        ],
                    ],
                    [
                        'kind' => 'single', 'type' => 'highlight', 'label' => 'Split Row 3: Global Fulfillment Feature Card',
                        'fields' => [
                            ['name' => 'title', 'label' => 'Card Title', 'column' => 'title', 'input' => 'text', 'required' => true, 'primary' => true,
                             'placeholder' => 'Delivering Love to 100+ Countries'],
                            ['name' => 'content', 'label' => 'Description', 'column' => 'content', 'input' => 'textarea', 'rows' => 4,
                             'placeholder' => 'From bulk corporate orders to a single personalised gift, we print, pack and deliver worldwide with a zero-defect promise.'],
                            ['name' => 'extra', 'label' => '🖼️ Desktop Layout Direction', 'column' => 'extra', 'input' => 'select',
                             'choices' => [
                                 ['value' => 'right', 'label' => '👉 Text on Left · Feature Card on Right (Default)'],
                                 ['value' => 'left', 'label' => '👈 Feature Card on Left · Text on Right (Cross / Flipped)'],
                             ]],
                            ['name' => 'image', 'label' => 'Card Graphic / Image', 'column' => 'image_path', 'input' => 'image',
                             'size' => '800 × 800 px', 'ratio' => 'square'],
                        ],
                    ],
                    [
                        'kind' => 'single', 'type' => 'story_2', 'label' => 'Split Row 4: Craft & Delivery Story Block',
                        'fields' => [
                            ['name' => 'title', 'label' => 'Heading', 'column' => 'title', 'input' => 'text', 'required' => true, 'primary' => true,
                             'placeholder' => 'How We Craft & Deliver'],
                            ['name' => 'content', 'label' => 'Slogan / Story Text', 'column' => 'content', 'input' => 'textarea', 'rows' => 4,
                             'placeholder' => 'Every item is carefully printed and packed by our team of print artisans using top-grade equipment, ensuring vivid colors and durable finishes.'],
                            ['name' => 'extra', 'label' => '🖼️ Desktop Layout Direction', 'column' => 'extra', 'input' => 'select',
                             'choices' => [
                                 ['value' => 'right', 'label' => '👉 Text on Left · Carousel / Image on Right (Default)'],
                                 ['value' => 'left', 'label' => '👈 Carousel / Image on Left · Text on Right (Cross / Flipped)'],
                             ]],
                            ['name' => 'image', 'label' => 'Side Banner Image (Optional)', 'column' => 'image_path', 'input' => 'image',
                             'size' => '960 × 600 px', 'ratio' => '16:10'],
                        ],
                    ],
                    [
                        'kind' => 'single', 'type' => 'history', 'label' => 'Our Vision',
                        'fields' => [
                            ['name' => 'title', 'label' => 'Vision Heading', 'column' => 'title', 'input' => 'text', 'required' => true, 'primary' => true,
                             'placeholder' => 'Our Vision'],
                            ['name' => 'content', 'label' => 'Vision Statement', 'column' => 'content', 'input' => 'textarea', 'rows' => 5],
                            ['name' => 'image', 'label' => 'Vision Cover Image', 'column' => 'image_path', 'input' => 'image',
                             'size' => '800 × 500 px', 'ratio' => '16:10'],
                        ],
                    ],
                    [
                        'kind' => 'single', 'type' => 'mission', 'label' => 'Our Mission',
                        'fields' => [
                            ['name' => 'title', 'label' => 'Mission Heading', 'column' => 'title', 'input' => 'text', 'required' => true, 'primary' => true,
                             'placeholder' => 'Our Mission'],
                            ['name' => 'content', 'label' => 'Mission Statement', 'column' => 'content', 'input' => 'textarea', 'rows' => 5],
                            ['name' => 'image', 'label' => 'Mission Cover Image', 'column' => 'image_path', 'input' => 'image',
                             'size' => '800 × 500 px', 'ratio' => '16:10'],
                        ],
                    ],
                    [
                        'kind' => 'single', 'type' => 'normal_text', 'label' => 'Long Brand Story / Article Text',
                        'fields' => [
                            ['name' => 'title', 'label' => 'Heading', 'column' => 'title', 'input' => 'text', 'required' => true, 'primary' => true,
                             'placeholder' => 'Highlight Text (Normal Text Pages.)'],
                            ['name' => 'content', 'label' => 'Full Article / Text (HTML or Paragraphs allowed)', 'column' => 'content', 'input' => 'textarea', 'rows' => 8],
                        ],
                    ],
                ],
            ],

            // ============================================================
            // TAB 3: BANNERS & CAROUSELS (SLIDERS)
            // ============================================================
            [
                'key' => 'carousels',
                'label' => '🎠 Banners & Carousels',
                'note' => 'Add, remove, or reorder slides for Top Hero Carousel, Mid Carousel 1, Mid Carousel 2, and Accolades.',
                'parts' => [
                    [
                        'kind' => 'list', 'type' => null, 'label' => 'Top Hero Carousel Slides', 'addLabel' => 'Add Hero Slide',
                        'source' => [
                            'table' => 'banners',
                            'key' => 'section_id',
                            'section_key' => 'about_hero',
                            'columns' => [
                                'id' => 'id',
                                'title' => 'title',
                                'content' => 'subtitle',
                                'extra' => 'target_url',
                                'image_path' => 'image_url',
                                'order' => 'display_order',
                                'active' => 'status',
                                'type' => null,
                            ],
                            'defaults' => [
                                'position' => 'about',
                                'banner_type' => 'carousel',
                                'status' => 'active',
                            ],
                            'active_on' => 'active',
                            'active_off' => 'inactive',
                        ],
                        'fields' => [
                            ['name' => 'title', 'label' => 'Slide Title', 'column' => 'title', 'input' => 'text', 'required' => true, 'primary' => true,
                             'placeholder' => 'About Printmont'],
                            ['name' => 'content', 'label' => 'Slide Subtitle / Tagline', 'column' => 'content', 'input' => 'text', 'secondary' => true,
                             'placeholder' => 'Empowering Creative Businesses Nationwide'],
                            ['name' => 'extra', 'label' => 'Target URL / Link', 'column' => 'extra', 'input' => 'text',
                             'placeholder' => '/about'],
                            ['name' => 'image', 'label' => 'Slide Image', 'column' => 'image_path', 'input' => 'image',
                             'size' => '1920 × 640 px', 'ratio' => '3:1', 'required' => true],
                        ],
                    ],
                    [
                        'kind' => 'list', 'type' => null, 'label' => 'Mid Banner Carousel 1 Slides', 'addLabel' => 'Add Slide to Carousel 1',
                        'source' => [
                            'table' => 'banners',
                            'key' => 'section_id',
                            'section_key' => 'about_mid_1',
                            'columns' => [
                                'id' => 'id',
                                'title' => 'title',
                                'content' => 'subtitle',
                                'extra' => 'target_url',
                                'image_path' => 'image_url',
                                'order' => 'display_order',
                                'active' => 'status',
                                'type' => null,
                            ],
                            'defaults' => [
                                'position' => 'about',
                                'banner_type' => 'carousel',
                                'status' => 'active',
                            ],
                            'active_on' => 'active',
                            'active_off' => 'inactive',
                        ],
                        'fields' => [
                            ['name' => 'title', 'label' => 'Slide Title', 'column' => 'title', 'input' => 'text', 'required' => true, 'primary' => true,
                             'placeholder' => 'State of the Art Printing Facility'],
                            ['name' => 'content', 'label' => 'Caption / Tagline', 'column' => 'content', 'input' => 'text', 'secondary' => true,
                             'placeholder' => 'High speed digital printing machines'],
                            ['name' => 'extra', 'label' => 'Target URL', 'column' => 'extra', 'input' => 'text',
                             'placeholder' => '/about'],
                            ['name' => 'image', 'label' => 'Slide Image', 'column' => 'image_path', 'input' => 'image',
                             'size' => '960 × 600 px', 'ratio' => '16:10', 'required' => true],
                        ],
                    ],
                    [
                        'kind' => 'list', 'type' => null, 'label' => 'Mid Banner Carousel 2 Slides', 'addLabel' => 'Add Slide to Carousel 2',
                        'source' => [
                            'table' => 'banners',
                            'key' => 'section_id',
                            'section_key' => 'about_mid_2',
                            'columns' => [
                                'id' => 'id',
                                'title' => 'title',
                                'content' => 'subtitle',
                                'extra' => 'target_url',
                                'image_path' => 'image_url',
                                'order' => 'display_order',
                                'active' => 'status',
                                'type' => null,
                            ],
                            'defaults' => [
                                'position' => 'about',
                                'banner_type' => 'carousel',
                                'status' => 'active',
                            ],
                            'active_on' => 'active',
                            'active_off' => 'inactive',
                        ],
                        'fields' => [
                            ['name' => 'title', 'label' => 'Slide Title', 'column' => 'title', 'input' => 'text', 'required' => true, 'primary' => true,
                             'placeholder' => 'Custom Packaging Hub'],
                            ['name' => 'content', 'label' => 'Caption / Tagline', 'column' => 'content', 'input' => 'text', 'secondary' => true,
                             'placeholder' => 'Premium boxes, bags and merchandise'],
                            ['name' => 'extra', 'label' => 'Target URL', 'column' => 'extra', 'input' => 'text',
                             'placeholder' => '/about'],
                            ['name' => 'image', 'label' => 'Slide Image', 'column' => 'image_path', 'input' => 'image',
                             'size' => '960 × 600 px', 'ratio' => '16:10', 'required' => true],
                        ],
                    ],
                    [
                        'kind' => 'list', 'type' => null, 'label' => 'Accolades Awards & Badges', 'addLabel' => 'Add Accolade Award',
                        'source' => [
                            'table' => 'banners',
                            'key' => 'section_id',
                            'section_key' => 'about_accolades',
                            'columns' => [
                                'id' => 'id',
                                'title' => 'title',
                                'content' => 'subtitle',
                                'extra' => 'target_url',
                                'image_path' => 'image_url',
                                'order' => 'display_order',
                                'active' => 'status',
                                'type' => null,
                            ],
                            'defaults' => [
                                'position' => 'about',
                                'banner_type' => 'carousel',
                                'status' => 'active',
                            ],
                            'active_on' => 'active',
                            'active_off' => 'inactive',
                        ],
                        'fields' => [
                            ['name' => 'title', 'label' => 'Award Title', 'column' => 'title', 'input' => 'text', 'required' => true, 'primary' => true,
                             'placeholder' => 'Top 50 Brands India'],
                            ['name' => 'content', 'label' => 'Award Year / Category', 'column' => 'content', 'input' => 'text', 'secondary' => true,
                             'placeholder' => '2025 Pitch Award'],
                            ['name' => 'extra', 'label' => 'Link (Optional)', 'column' => 'extra', 'input' => 'text'],
                            ['name' => 'image', 'label' => 'Award Badge / Banner Image', 'column' => 'image_path', 'input' => 'image',
                             'size' => '600 × 400 px', 'ratio' => '3:2'],
                        ],
                    ],
                ],
            ],

            // ============================================================
            // TAB 4: PILLARS, TEAM & STATS
            // ============================================================
            [
                'key' => 'pillars_team_stats',
                'label' => '🌟 Pillars, Team & Stats',
                'note' => 'Manage Value Pillars (The Perfect Surprise), Team members, and proposition counters.',
                'parts' => [
                    [
                        'kind' => 'list', 'type' => 'feature', 'label' => 'The Perfect Surprise (3 Value Pillars)', 'addLabel' => 'Add Pillar',
                        'fields' => [
                            ['name' => 'title', 'label' => 'Title', 'column' => 'title', 'input' => 'text', 'required' => true, 'primary' => true],
                            ['name' => 'content', 'label' => 'Description', 'column' => 'content', 'input' => 'textarea', 'rows' => 3, 'secondary' => true],
                            ['name' => 'image', 'label' => 'Icon / Image', 'column' => 'image_path', 'input' => 'image',
                             'size' => '96 × 96 px', 'ratio' => 'square'],
                        ],
                    ],
                    [
                        'kind' => 'list', 'type' => 'team', 'label' => 'The Minds Behind the Magic (Team)', 'addLabel' => 'Add Team Member',
                        'source' => [
                            'table' => 'team_members',
                            'key' => null,
                            'columns' => [
                                'title' => 'name',
                                'content' => 'position',
                                'extra' => 'bio',
                                'image_path' => 'image_path',
                                'order' => 'display_order',
                                'active' => 'is_active',
                                'type' => null,
                            ],
                        ],
                        'fields' => [
                            ['name' => 'title', 'label' => 'Full Name', 'column' => 'title', 'input' => 'text', 'required' => true, 'primary' => true,
                             'placeholder' => 'Sarah Johnson'],
                            ['name' => 'content', 'label' => 'Position / Role', 'column' => 'content', 'input' => 'text', 'required' => true, 'secondary' => true,
                             'placeholder' => 'CEO & Founder'],
                            ['name' => 'extra', 'label' => 'Short Bio (Optional)', 'column' => 'extra', 'input' => 'textarea', 'rows' => 2],
                            ['name' => 'image', 'label' => 'Photo', 'column' => 'image_path', 'input' => 'image',
                             'size' => '400 × 400 px', 'ratio' => 'square'],
                        ],
                    ],
                    [
                        'kind' => 'list', 'type' => 'stat', 'label' => 'Numbers Backing Up Our Proposition (Stats)', 'addLabel' => 'Add Stat',
                        'fields' => [
                            ['name' => 'title', 'label' => 'Stat Number', 'column' => 'title', 'input' => 'text', 'required' => true, 'primary' => true,
                             'placeholder' => '1 Million+'],
                            ['name' => 'content', 'label' => 'Stat Description', 'column' => 'content', 'input' => 'text', 'required' => true, 'secondary' => true,
                             'placeholder' => 'Custom Gifts Delivered'],
                        ],
                    ],
                ],
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

    'blog' => [
        'label'   => 'Blog Page',
        'note'    => 'Manage breadcrumb background image, blog hero sliders, category icons, small banner strips, recent carousel, category blocks, and ad promotions.',
        'preview' => '/blog',
        'admin'   => 'blog-page.php',
        'bands'   => [
            [
                'key' => 'breadcrumb_banner',
                'label' => '🖼️ Breadcrumb & Header Background',
                'note' => 'Set or change the dynamic background image for the top Breadcrumb & Header section on the Blog and Blog Detail pages.',
                'parts' => [
                    [
                        'kind' => 'list', 'type' => null, 'label' => 'Breadcrumb Header Background Image', 'addLabel' => 'Add Breadcrumb Banner',
                        'source' => [
                            'table' => 'banners',
                            'key' => 'section_id',
                            'section_key' => 'blog_page_banner',
                            'columns' => [
                                'id' => 'id',
                                'title' => 'title',
                                'content' => 'description',
                                'extra' => 'target_url',
                                'image_path' => 'image_url_desktop',
                                'order' => 'display_order',
                                'active' => 'status',
                            ],
                            'active_on' => 'active',
                            'active_off' => 'inactive',
                        ],
                        'fields' => [
                            ['name' => 'title', 'label' => 'Main Heading (Title)', 'column' => 'title', 'input' => 'text', 'required' => true, 'primary' => true, 'placeholder' => 'Gifting Guides, Design Tips & Brand Inspiration', 'help' => 'Main large headline shown in the blog breadcrumb/hero header.'],
                            ['name' => 'content', 'label' => 'Subheading / Description', 'column' => 'content', 'input' => 'textarea', 'rows' => 3, 'secondary' => true, 'placeholder' => 'Discover expert articles on customized printing, corporate employee kits, personalized occasion gifts, and branding solutions.', 'help' => 'Subtitle text displayed directly below the main heading.'],
                            ['name' => 'extra', 'label' => 'Badge / Eyebrow Tag Text', 'column' => 'extra', 'input' => 'text', 'placeholder' => 'PRINTMONT INSIGHTS', 'help' => 'Small pill badge displayed above the title (e.g. PRINTMONT INSIGHTS).'],
                            ['name' => 'image', 'label' => 'Header Background Image', 'column' => 'image_path', 'input' => 'image', 'size' => '1920 × 450 px', 'ratio' => '4:1', 'help' => 'High-resolution wide banner background image for blog header & breadcrumb area.'],
                        ],
                    ],
                ],
            ],
            [
                'key' => 'banners_sliders',
                'label' => '🎠 Banners & Sliders',
                'note' => 'Manage Top Small Banner, Main Hero Slider, Middle Promo Slider, and Bottom Google Ads Banner.',
                'parts' => [
                    [
                        'kind' => 'list', 'type' => null, 'label' => 'Top Small Banner Strip / Slider', 'addLabel' => 'Add Top Banner',
                        'source' => [
                            'table' => 'banners',
                            'key' => 'section_id',
                            'section_key' => 'blog_top_strip',
                            'columns' => [
                                'id' => 'id',
                                'title' => 'title',
                                'content' => 'description',
                                'extra' => 'target_url',
                                'image_path' => 'image_url_desktop',
                                'order' => 'display_order',
                                'active' => 'status',
                            ],
                            'active_on' => 'active',
                            'active_off' => 'inactive',
                        ],
                        'fields' => [
                            ['name' => 'title', 'label' => 'Banner Title / Headline', 'column' => 'title', 'input' => 'text', 'required' => true, 'primary' => true],
                            ['name' => 'content', 'label' => 'Sub-title / Note', 'column' => 'content', 'input' => 'textarea', 'rows' => 2, 'secondary' => true],
                            ['name' => 'extra', 'label' => 'Target URL / Link', 'column' => 'extra', 'input' => 'text', 'placeholder' => '/bulk-orders'],
                            ['name' => 'image', 'label' => 'Banner Image', 'column' => 'image_path', 'input' => 'image', 'size' => '1200 × 280 px', 'ratio' => '4:1'],
                        ],
                    ],
                    [
                        'kind' => 'list', 'type' => null, 'label' => 'Main Hero Slider & Banners', 'addLabel' => 'Add Hero Slide',
                        'source' => [
                            'table' => 'banners',
                            'key' => 'section_id',
                            'section_key' => 'blog_hero',
                            'columns' => [
                                'id' => 'id',
                                'title' => 'title',
                                'content' => 'description',
                                'extra' => 'target_url',
                                'image_path' => 'image_url_desktop',
                                'order' => 'display_order',
                                'active' => 'status',
                            ],
                            'active_on' => 'active',
                            'active_off' => 'inactive',
                        ],
                        'fields' => [
                            ['name' => 'title', 'label' => 'Hero Slide Title', 'column' => 'title', 'input' => 'text', 'required' => true, 'primary' => true],
                            ['name' => 'content', 'label' => 'Slide Subtitle / Description', 'column' => 'content', 'input' => 'textarea', 'rows' => 3, 'secondary' => true],
                            ['name' => 'extra', 'label' => 'Target URL / Link', 'column' => 'extra', 'input' => 'text', 'placeholder' => '/blog'],
                            ['name' => 'image', 'label' => 'Hero Slide Image', 'column' => 'image_path', 'input' => 'image', 'size' => '1920 × 600 px', 'ratio' => '3:1'],
                        ],
                    ],
                    [
                        'kind' => 'list', 'type' => null, 'label' => 'Middle Small Banner Strip / Slider', 'addLabel' => 'Add Mid Banner',
                        'source' => [
                            'table' => 'banners',
                            'key' => 'section_id',
                            'section_key' => 'blog_mid_strip',
                            'columns' => [
                                'id' => 'id',
                                'title' => 'title',
                                'content' => 'description',
                                'extra' => 'target_url',
                                'image_path' => 'image_url_desktop',
                                'order' => 'display_order',
                                'active' => 'status',
                            ],
                            'active_on' => 'active',
                            'active_off' => 'inactive',
                        ],
                        'fields' => [
                            ['name' => 'title', 'label' => 'Mid Banner Title', 'column' => 'title', 'input' => 'text', 'required' => true, 'primary' => true],
                            ['name' => 'content', 'label' => 'Sub-title', 'column' => 'content', 'input' => 'textarea', 'rows' => 2, 'secondary' => true],
                            ['name' => 'extra', 'label' => 'Target URL', 'column' => 'extra', 'input' => 'text', 'placeholder' => '/products'],
                            ['name' => 'image', 'label' => 'Banner Image', 'column' => 'image_path', 'input' => 'image', 'size' => '1200 × 280 px', 'ratio' => '4:1'],
                        ],
                    ],
                    [
                        'kind' => 'list', 'type' => null, 'label' => 'Bottom Ad & Promo Banner Slot', 'addLabel' => 'Add Ad Banner',
                        'source' => [
                            'table' => 'banners',
                            'key' => 'section_id',
                            'section_key' => 'blog_ad_banner',
                            'columns' => [
                                'id' => 'id',
                                'title' => 'title',
                                'content' => 'description',
                                'extra' => 'target_url',
                                'image_path' => 'image_url_desktop',
                                'order' => 'display_order',
                                'active' => 'status',
                            ],
                            'active_on' => 'active',
                            'active_off' => 'inactive',
                        ],
                        'fields' => [
                            ['name' => 'title', 'label' => 'Ad Banner Title', 'column' => 'title', 'input' => 'text', 'required' => true, 'primary' => true],
                            ['name' => 'content', 'label' => 'Ad Text / Description', 'column' => 'content', 'input' => 'textarea', 'rows' => 2, 'secondary' => true],
                            ['name' => 'extra', 'label' => 'Target URL', 'column' => 'extra', 'input' => 'text', 'placeholder' => '/become-a-seller'],
                            ['name' => 'image', 'label' => 'Ad Banner Image', 'column' => 'image_path', 'input' => 'image', 'size' => '1200 × 300 px', 'ratio' => '4:1'],
                        ],
                    ],
                ],
            ],
            [
                'key' => 'blog_categories',
                'label' => '📑 Blog Categories & Topics',
                'note' => 'Manage category pills and topics shown on the blog header and filters.',
                'parts' => [
                    [
                        'kind' => 'list', 'type' => null, 'label' => 'Categories & Gift Ideas', 'addLabel' => 'Add Category',
                        'source' => [
                            'table' => 'blog_categories',
                            'key' => null,
                            'columns' => [
                                'id' => 'id',
                                'title' => 'name',
                                'content' => 'description',
                                'extra' => 'slug',
                                'image_path' => null,
                                'type' => null,
                                'order' => null,
                                'active' => 'status',
                            ],
                            'active_on' => 'active',
                            'active_off' => 'inactive',
                        ],
                        'fields' => [
                            ['name' => 'title', 'label' => 'Category Name', 'column' => 'title', 'input' => 'text', 'required' => true, 'primary' => true, 'placeholder' => 'Personalized Gifts'],
                            ['name' => 'content', 'label' => 'Category Description', 'column' => 'content', 'input' => 'textarea', 'rows' => 3, 'secondary' => true],
                            ['name' => 'extra', 'label' => 'URL Slug', 'column' => 'extra', 'input' => 'text', 'placeholder' => 'personalized-gifts'],
                        ],
                    ],
                ],
            ],
        ],
    ],

    // ------------------------------------------------------------------
    'bulk-orders' => [
        'label'   => 'Bulk Orders & Wholesale Printing',
        'note'    => 'Corporate merchandise, wholesale catalog products, partner benefits, global fulfillment, and bulk inquiry settings.',
        'preview' => '/bulk-orders',
        'bands'   => [

            [
                'key' => 'hero', 'label' => 'Hero & Title Section',
                'note' => 'Main headline, sub-headline, and corporate eyebrow badge.',
                'parts' => [[
                    'kind' => 'single', 'type' => 'hero', 'label' => 'Hero Intro',
                    'fields' => [
                        ['name' => 'title', 'label' => 'Main Heading', 'column' => 'title', 'input' => 'text', 'required' => true, 'primary' => true,
                         'placeholder' => 'Bulk Orders & Wholesale Printing'],
                        ['name' => 'content', 'label' => 'Description', 'column' => 'content', 'input' => 'textarea', 'rows' => 3,
                         'placeholder' => 'PrintMont provides end-to-end custom printing, corporate merchandise, and bulk promotional product solutions for companies, events, startups, and institutions across India.'],
                        ['name' => 'extra', 'label' => 'Eyebrow Badge', 'column' => 'extra', 'input' => 'text',
                         'placeholder' => 'CORPORATE & ENTERPRISE SUPPLY'],
                        ['name' => 'image', 'label' => 'Top Banner Image (Optional)', 'column' => 'image_path', 'input' => 'image',
                         'size' => '1920 × 480 px', 'ratio' => '4:1'],
                    ],
                ]],
            ],

            [
                'key' => 'products', 'label' => 'Bulk Product Catalog',
                'note' => 'The grid of wholesale products shown on the page (Polo T-shirts, Visiting Cards, Mugs, Bags, etc.). You can link any existing store product or enter a custom item.',
                'parts' => [[
                    'kind' => 'list', 'type' => 'product', 'label' => 'Bulk Products', 'addLabel' => 'Add Bulk Product',
                    'fields' => [
                        ['name' => 'store_product_picker', 'label' => '⚡ Quick Pick Existing Store Product (Auto-Fills Details)', 'input' => 'product_picker',
                         'options' => ['table' => 'products', 'value' => 'id', 'label' => 'name', 'where' => "status = 'active'", 'order' => 'name',
                                       'extra_cols' => 'offer_price, regular_price, thumbnail_image, product_slug'],
                         'help' => 'Choose any existing store product to instantly auto-fill its title, image, bulk pricing suggestion, and slug link.'],
                        ['name' => 'title', 'label' => 'Product Name / Title', 'column' => 'title', 'input' => 'text', 'required' => true, 'primary' => true,
                         'placeholder' => 'Bulk Polo T-shirts'],
                        ['name' => 'content', 'label' => 'Pricing / Minimum Order', 'column' => 'content', 'input' => 'text', 'secondary' => true,
                         'placeholder' => '₹599 each for 100 pieces'],
                        ['name' => 'extra', 'label' => 'Category Tag or Product Slug', 'column' => 'extra', 'input' => 'text',
                         'placeholder' => 'Apparel | t-shirts-bulk-polo'],
                        ['name' => 'image', 'label' => 'Product Image', 'column' => 'image_path', 'input' => 'image',
                         'size' => '400 × 400 px', 'ratio' => '1:1', 'help' => 'Clean square image of the bulk item.'],
                    ],
                ]],
            ],

            [
                'key' => 'benefits', 'label' => 'Why Partner with Printmont',
                'note' => 'The numbered feature cards (Tiered Pricing, Multiple Tech, Account Manager, etc.).',
                'parts' => [[
                    'kind' => 'list', 'type' => 'benefit', 'label' => 'Benefits', 'addLabel' => 'Add Benefit',
                    'fields' => [
                        ['name' => 'title', 'label' => 'Benefit Title', 'column' => 'title', 'input' => 'text', 'required' => true, 'primary' => true,
                         'placeholder' => 'Tiered Wholesale Pricing'],
                        ['name' => 'content', 'label' => 'Description', 'column' => 'content', 'input' => 'textarea', 'rows' => 3, 'secondary' => true,
                         'placeholder' => 'Enjoy steep volume discounts starting at just 25 units. The larger your order quantity, the lower your unit cost.'],
                        ['name' => 'extra', 'label' => 'Number / Step (1, 2, 3...)', 'column' => 'extra', 'input' => 'text',
                         'placeholder' => '1'],
                    ],
                ]],
            ],

            [
                'key' => 'fulfillment', 'label' => 'Global Fulfillment Network',
                'note' => 'Worldwide delivery banner with badges (100+ Countries, Express Delivery, Zero-Defect Promise).',
                'parts' => [[
                    'kind' => 'single', 'type' => 'fulfillment', 'label' => 'Global Network Block',
                    'fields' => [
                        ['name' => 'title', 'label' => 'Headline', 'column' => 'title', 'input' => 'text', 'required' => true, 'primary' => true,
                         'placeholder' => 'Delivering Love to 100+ Countries'],
                        ['name' => 'content', 'label' => 'Description', 'column' => 'content', 'input' => 'textarea', 'rows' => 3,
                         'placeholder' => 'From bulk corporate orders to a single personalised gift, we print, pack and deliver worldwide with a zero-defect promise.'],
                        ['name' => 'extra', 'label' => 'Badges (Separated by |)', 'column' => 'extra', 'input' => 'text',
                         'placeholder' => 'Global Fulfillment Network|100+ Countries|Express Delivery|Zero-Defect Promise',
                         'help' => 'Format: Eyebrow | Badge 1 | Badge 2 | Badge 3'],
                        ['name' => 'image', 'label' => 'Illustration / Graphic Image', 'column' => 'image_path', 'input' => 'image',
                         'size' => '800 × 500 px', 'ratio' => '16:10'],
                    ],
                ]],
            ],

            [
                'key' => 'sample_cta', 'label' => 'Sample Request Callout',
                'note' => 'The "Touch it. Test it. Trust it. - Order Samples Now" banner.',
                'parts' => [[
                    'kind' => 'single', 'type' => 'sample_cta', 'label' => 'Sample Box',
                    'fields' => [
                        ['name' => 'title', 'label' => 'Catchy Heading', 'column' => 'title', 'input' => 'text', 'required' => true, 'primary' => true,
                         'placeholder' => 'Touch it. Test it. Trust it.'],
                        ['name' => 'content', 'label' => 'Subtitle / Hint', 'column' => 'content', 'input' => 'text',
                         'placeholder' => 'Order pre-production sample kit before confirming bulk order'],
                        ['name' => 'extra', 'label' => 'Button Text', 'column' => 'extra', 'input' => 'text',
                         'placeholder' => 'Order Samples Now'],
                    ],
                ]],
            ],

            [
                'key' => 'faqs', 'label' => 'Frequently Asked Questions',
                'note' => 'Accordion questions & answers about bulk ordering.',
                'parts' => [[
                    'kind' => 'list', 'type' => 'faq', 'label' => 'Questions', 'addLabel' => 'Add Question',
                    'fields' => [
                        ['name' => 'title', 'label' => 'Question', 'column' => 'title', 'input' => 'text', 'required' => true, 'primary' => true,
                         'placeholder' => 'What are the benefits of ordering in bulk?'],
                        ['name' => 'content', 'label' => 'Answer', 'column' => 'content', 'input' => 'textarea', 'rows' => 4, 'secondary' => true,
                         'placeholder' => 'Ordering in bulk gives you access to tiered wholesale discounts...'],
                    ],
                ]],
            ],

            [
                'key' => 'contact', 'label' => 'Bulk Inquiry & Contact',
                'note' => 'Support contact details for corporate clients.',
                'parts' => [[
                    'kind' => 'single', 'type' => 'contact', 'label' => 'Contact Box',
                    'fields' => [
                        ['name' => 'title', 'label' => 'Headline', 'column' => 'title', 'input' => 'text', 'required' => true, 'primary' => true,
                         'placeholder' => 'Ready to Discuss Your Bulk Requirement?'],
                        ['name' => 'content', 'label' => 'Sub-line', 'column' => 'content', 'input' => 'textarea', 'rows' => 2,
                         'placeholder' => 'Our corporate bulk experts are standing by to assist with instant price quotes and mockups.'],
                        ['name' => 'extra', 'label' => 'Contact Info (Email | Phone)', 'column' => 'extra', 'input' => 'text',
                         'placeholder' => 'bulk@printmont.com | +91-9818532463'],
                    ],
                ]],
            ],

        ],
    ],

    // ------------------------------------------------------------------
    'franchise' => [
        'label'   => 'Franchise Partner',
        'note'    => 'The franchise landing page — hero text, numbers, models, journey, store openings, steps, support, stories, FAQs and contact details. The hero background banner is managed in Page Banners → Franchise Page.',
        'preview' => '/franchise',
        'bands'   => [

            [
                'key' => 'hero', 'label' => 'Hero',
                'note' => 'Headline, sub-line and quick highlights over the top banner. Change the banner picture itself from Page Banners → Franchise Top Banner.',
                'parts' => [
                    [
                        'kind' => 'single', 'type' => 'hero', 'label' => 'Hero text',
                        'fields' => [
                            ['name' => 'title', 'label' => 'Headline', 'column' => 'title', 'input' => 'text', 'required' => true, 'primary' => true,
                             'placeholder' => 'Start Your Entrepreneurial Journey With'],
                            ['name' => 'highlight', 'label' => 'Highlighted word', 'column' => 'extra', 'part' => 1, 'input' => 'text',
                             'placeholder' => 'Printmont', 'help' => 'Shown in the brand colour at the end of the headline.'],
                            ['name' => 'badge', 'label' => 'Top badge', 'column' => 'extra', 'part' => 0, 'input' => 'text',
                             'placeholder' => "India's Fastest Growing Customized Printing & Gifting Franchise"],
                            ['name' => 'content', 'label' => 'Sub-line', 'column' => 'content', 'input' => 'textarea', 'rows' => 4],
                        ],
                    ],
                    [
                        'kind' => 'list', 'type' => 'hero_point', 'label' => 'Quick highlights', 'addLabel' => 'Add highlight',
                        'fields' => [
                            ['name' => 'title', 'label' => 'Highlight', 'column' => 'title', 'input' => 'text', 'required' => true, 'primary' => true,
                             'placeholder' => '35% - 50% Profit Margins'],
                        ],
                    ],
                ],
            ],

            [
                'key' => 'stats', 'label' => 'Numbers strip',
                'note' => 'The blue strip of figures under the hero (Stores, Cities, Customers…).',
                'parts' => [[
                    'kind' => 'list', 'type' => 'stat', 'label' => 'Figures', 'addLabel' => 'Add figure',
                    'fields' => [
                        ['name' => 'title', 'label' => 'Figure', 'column' => 'title', 'input' => 'text', 'required' => true, 'primary' => true, 'placeholder' => '100+'],
                        ['name' => 'content', 'label' => 'What it counts', 'column' => 'content', 'input' => 'text', 'secondary' => true, 'placeholder' => 'Stores'],
                    ],
                ]],
            ],

            [
                'key' => 'why', 'label' => 'Why partner with us',
                'note' => 'The six pillar cards under "Why Partner With Printmont?".',
                'parts' => [[
                    'kind' => 'list', 'type' => 'pillar', 'label' => 'Pillars', 'addLabel' => 'Add pillar',
                    'fields' => [
                        ['name' => 'title', 'label' => 'Title', 'column' => 'title', 'input' => 'text', 'required' => true, 'primary' => true],
                        ['name' => 'content', 'label' => 'Description', 'column' => 'content', 'input' => 'textarea', 'rows' => 2, 'secondary' => true],
                    ],
                ]],
            ],

            [
                'key' => 'models', 'label' => 'Franchise models',
                'note' => 'The store formats shown as cards (Kiosk, Studio, Regional Hub). Do not use the | character inside any field.',
                'parts' => [[
                    'kind' => 'list', 'type' => 'model', 'label' => 'Models', 'addLabel' => 'Add model',
                    'fields' => [
                        ['name' => 'title', 'label' => 'Model name', 'column' => 'title', 'input' => 'text', 'required' => true, 'primary' => true,
                         'placeholder' => 'STUDIO & EXPERIENCE CENTER'],
                        ['name' => 'tag', 'label' => 'Ribbon text', 'column' => 'extra', 'part' => 0, 'input' => 'text', 'placeholder' => 'Most Popular'],
                        ['name' => 'popular', 'label' => 'Highlight this model?', 'column' => 'extra', 'part' => 1, 'input' => 'select',
                         'choices' => [['value' => 'yes', 'label' => 'Yes — show ribbon and highlight'], ['value' => 'no', 'label' => 'No']],
                         'required' => true],
                        ['name' => 'area', 'label' => 'Area required', 'column' => 'extra', 'part' => 2, 'input' => 'text', 'placeholder' => '350 - 700 sq.ft.'],
                        ['name' => 'investment', 'label' => 'Investment', 'column' => 'extra', 'part' => 3, 'input' => 'text', 'secondary' => true, 'placeholder' => '₹ 8 - 14 Lakh* + GST'],
                        ['name' => 'profit', 'label' => 'Profit / month', 'column' => 'extra', 'part' => 4, 'input' => 'text', 'placeholder' => 'Upto ₹ 2.5 Lakh*'],
                        ['name' => 'roi', 'label' => 'ROI', 'column' => 'extra', 'part' => 5, 'input' => 'text', 'placeholder' => '9 - 14 Months'],
                        ['name' => 'ideal', 'label' => 'Ideal for', 'column' => 'content', 'part' => 0, 'input' => 'text'],
                        ['name' => 'features', 'label' => 'Features (one per line)', 'column' => 'content', 'part' => 1, 'input' => 'textarea', 'rows' => 6],
                        ['name' => 'image', 'label' => 'Storefront image', 'column' => 'image_path', 'input' => 'image', 'size' => '800 × 500 px', 'ratio' => '16:10'],
                    ],
                ]],
            ],

            [
                'key' => 'journey', 'label' => 'Journey of growth',
                'note' => 'The year-by-year milestone cards.',
                'parts' => [[
                    'kind' => 'list', 'type' => 'milestone', 'label' => 'Milestones', 'addLabel' => 'Add milestone',
                    'fields' => [
                        ['name' => 'extra', 'label' => 'Year', 'column' => 'extra', 'input' => 'text', 'required' => true, 'primary' => true, 'placeholder' => '2024'],
                        ['name' => 'title', 'label' => 'Title', 'column' => 'title', 'input' => 'text', 'required' => true, 'secondary' => true],
                        ['name' => 'content', 'label' => 'Description', 'column' => 'content', 'input' => 'textarea', 'rows' => 3],
                    ],
                ]],
            ],

            [
                'key' => 'stores', 'label' => 'Recent store openings',
                'note' => 'The store photo cards beside the network panel.',
                'parts' => [[
                    'kind' => 'list', 'type' => 'store', 'label' => 'Stores', 'addLabel' => 'Add store',
                    'fields' => [
                        ['name' => 'title', 'label' => 'City', 'column' => 'title', 'input' => 'text', 'required' => true, 'primary' => true],
                        ['name' => 'content', 'label' => 'Store name', 'column' => 'content', 'input' => 'text', 'secondary' => true],
                        ['name' => 'extra', 'label' => 'Tag', 'column' => 'extra', 'input' => 'text', 'placeholder' => 'Experience Studio'],
                        ['name' => 'image', 'label' => 'Store photo', 'column' => 'image_path', 'input' => 'image', 'size' => '600 × 400 px', 'ratio' => '3:2'],
                    ],
                ]],
            ],

            [
                'key' => 'steps', 'label' => 'How it works',
                'note' => 'The numbered steps, in order.',
                'parts' => [[
                    'kind' => 'list', 'type' => 'step', 'label' => 'Steps', 'addLabel' => 'Add step',
                    'fields' => [
                        ['name' => 'title', 'label' => 'Step title', 'column' => 'title', 'input' => 'text', 'required' => true, 'primary' => true],
                        ['name' => 'content', 'label' => 'Short description', 'column' => 'content', 'input' => 'text', 'secondary' => true],
                    ],
                ]],
            ],

            [
                'key' => 'support', 'label' => 'Support after launch',
                'note' => 'The "Beyond Store Launch, We\'re With You" cards.',
                'parts' => [[
                    'kind' => 'list', 'type' => 'support', 'label' => 'Support points', 'addLabel' => 'Add point',
                    'fields' => [
                        ['name' => 'title', 'label' => 'Title', 'column' => 'title', 'input' => 'text', 'required' => true, 'primary' => true],
                        ['name' => 'content', 'label' => 'Description', 'column' => 'content', 'input' => 'textarea', 'rows' => 2, 'secondary' => true],
                    ],
                ]],
            ],

            [
                'key' => 'ecosystem', 'label' => 'Products ecosystem',
                'note' => 'The grid of product categories a franchise store sells.',
                'parts' => [[
                    'kind' => 'list', 'type' => 'ecosystem', 'label' => 'Product categories', 'addLabel' => 'Add category',
                    'fields' => [
                        ['name' => 'title', 'label' => 'Category name', 'column' => 'title', 'input' => 'text', 'required' => true, 'primary' => true],
                        ['name' => 'extra', 'label' => 'Emoji icon', 'column' => 'extra', 'input' => 'text', 'secondary' => true, 'placeholder' => '👕'],
                        ['name' => 'image', 'label' => 'Photo (optional)', 'column' => 'image_path', 'input' => 'image', 'size' => '200 × 200 px', 'ratio' => 'square',
                         'help' => 'Shown instead of the emoji when uploaded.'],
                    ],
                ]],
            ],

            [
                'key' => 'stories', 'label' => 'Franchisee success stories',
                'note' => 'The testimonial carousel.',
                'parts' => [[
                    'kind' => 'list', 'type' => 'story', 'label' => 'Stories', 'addLabel' => 'Add story',
                    'fields' => [
                        ['name' => 'title', 'label' => 'Partner name', 'column' => 'title', 'input' => 'text', 'required' => true, 'primary' => true],
                        ['name' => 'content', 'label' => 'Quote', 'column' => 'content', 'input' => 'textarea', 'rows' => 4, 'secondary' => true],
                        ['name' => 'role', 'label' => 'Role', 'column' => 'extra', 'part' => 0, 'input' => 'text', 'placeholder' => 'Studio Owner'],
                        ['name' => 'city', 'label' => 'City', 'column' => 'extra', 'part' => 1, 'input' => 'text', 'placeholder' => 'FC Road, Pune'],
                        ['name' => 'revenue', 'label' => 'Verified revenue', 'column' => 'extra', 'part' => 2, 'input' => 'text', 'placeholder' => '₹4.2 Lakh / Month'],
                    ],
                ]],
            ],

            [
                'key' => 'faqs', 'label' => 'Frequently asked questions',
                'note' => 'Questions and answers about the franchise.',
                'parts' => [[
                    'kind' => 'list', 'type' => 'faq', 'label' => 'Questions', 'addLabel' => 'Add question',
                    'fields' => [
                        ['name' => 'title', 'label' => 'Question', 'column' => 'title', 'input' => 'text', 'required' => true, 'primary' => true],
                        ['name' => 'content', 'label' => 'Answer', 'column' => 'content', 'input' => 'textarea', 'rows' => 4, 'secondary' => true],
                    ],
                ]],
            ],

            [
                'key' => 'contact', 'label' => 'Contact details',
                'note' => 'Shown beside the application form.',
                'parts' => [[
                    'kind' => 'single', 'type' => 'contact', 'label' => 'Contact box',
                    'fields' => [
                        ['name' => 'title', 'label' => 'Heading', 'column' => 'title', 'input' => 'text', 'required' => true, 'primary' => true,
                         'placeholder' => 'Talk to Franchise Expansion Director'],
                        ['name' => 'phone', 'label' => 'Phone', 'column' => 'extra', 'part' => 0, 'input' => 'text', 'placeholder' => '+91 98185 32463'],
                        ['name' => 'email', 'label' => 'Email', 'column' => 'extra', 'part' => 1, 'input' => 'text', 'placeholder' => 'franchise@printmont.com'],
                        ['name' => 'hq', 'label' => 'Headquarters', 'column' => 'extra', 'part' => 2, 'input' => 'text', 'placeholder' => 'New Delhi, India'],
                        ['name' => 'image', 'label' => 'Showcase image', 'column' => 'image_path', 'input' => 'image', 'size' => '800 × 500 px', 'ratio' => '16:10'],
                    ],
                ]],
            ],

        ],
    ],

    // ------------------------------------------------------------------
    'advertising' => [
        'label'   => 'Advertising (Printmont Ads)',
        'note'    => 'The "Advertise with Printmont" page linked from the footer — hero, benefits, ad solutions, steps, stories, FAQs and the enquiry form. The picture above the hero is managed in Page Banners → Advertising.',
        'preview' => '/advertising',
        'bands'   => [

            [
                'key' => 'hero', 'label' => 'Hero',
                'note' => 'Headline and sub-line beside the enquiry card.',
                'parts' => [
                    [
                        'kind' => 'single', 'type' => 'hero', 'label' => 'Hero text',
                        'fields' => [
                            ['name' => 'title', 'label' => 'Headline', 'column' => 'title', 'input' => 'text', 'required' => true, 'primary' => true,
                             'placeholder' => 'Grow your business with Printmont Ads'],
                            ['name' => 'content', 'label' => 'Sub-line', 'column' => 'content', 'input' => 'textarea', 'rows' => 3],
                            ['name' => 'cta', 'label' => 'Main button', 'column' => 'extra', 'part' => 0, 'input' => 'text', 'placeholder' => 'Start Advertising'],
                            ['name' => 'cta2', 'label' => 'Second button', 'column' => 'extra', 'part' => 1, 'input' => 'text', 'placeholder' => 'Talk to our team'],
                            ['name' => 'image', 'label' => 'Image (shown when no banner is set)', 'column' => 'image_path', 'input' => 'image', 'size' => '1920 × 480 px', 'ratio' => '4:1'],
                        ],
                    ],
                    [
                        'kind' => 'list', 'type' => 'hero_point', 'label' => 'Quick highlights', 'addLabel' => 'Add highlight',
                        'fields' => [
                            ['name' => 'title', 'label' => 'Highlight', 'column' => 'title', 'input' => 'text', 'required' => true, 'primary' => true],
                        ],
                    ],
                ],
            ],

            [
                'key' => 'stats', 'label' => 'Numbers strip',
                'note' => 'The figures under the hero.',
                'parts' => [[
                    'kind' => 'list', 'type' => 'stat', 'label' => 'Figures', 'addLabel' => 'Add figure',
                    'fields' => [
                        ['name' => 'title', 'label' => 'Figure', 'column' => 'title', 'input' => 'text', 'required' => true, 'primary' => true, 'placeholder' => '50L+'],
                        ['name' => 'content', 'label' => 'What it counts', 'column' => 'content', 'input' => 'text', 'secondary' => true, 'placeholder' => 'Active buyers'],
                    ],
                ]],
            ],

            [
                'key' => 'why', 'label' => 'Why advertise',
                'note' => 'Benefit cards.',
                'parts' => [
                    ['kind' => 'single', 'type' => 'heading', 'label' => 'Band heading',
                     'match' => ['extra' => 'why'], 'fields' => pe_heading_fields()],
                    ['kind' => 'list', 'type' => 'benefit', 'label' => 'Benefits', 'addLabel' => 'Add benefit',
                     'fields' => [
                        ['name' => 'title', 'label' => 'Title', 'column' => 'title', 'input' => 'text', 'required' => true, 'primary' => true],
                        ['name' => 'content', 'label' => 'Description', 'column' => 'content', 'input' => 'textarea', 'rows' => 3, 'secondary' => true],
                     ]],
                ],
            ],

            [
                'key' => 'solutions', 'label' => 'Ad solutions',
                'note' => 'The ad formats you offer. Features are one per line. Do not use the | character.',
                'parts' => [
                    ['kind' => 'single', 'type' => 'heading', 'label' => 'Band heading',
                     'match' => ['extra' => 'solutions'], 'fields' => pe_heading_fields()],
                    ['kind' => 'list', 'type' => 'solution', 'label' => 'Ad formats', 'addLabel' => 'Add ad format',
                     'fields' => [
                        ['name' => 'title', 'label' => 'Name', 'column' => 'title', 'input' => 'text', 'required' => true, 'primary' => true],
                        ['name' => 'tag', 'label' => 'Badge (optional)', 'column' => 'extra', 'input' => 'text', 'placeholder' => 'Most popular', 'secondary' => true],
                        ['name' => 'desc', 'label' => 'Short description', 'column' => 'content', 'part' => 0, 'input' => 'textarea', 'rows' => 2],
                        ['name' => 'features', 'label' => 'Features (one per line)', 'column' => 'content', 'part' => 1, 'input' => 'textarea', 'rows' => 5],
                        ['name' => 'image', 'label' => 'Preview image (optional)', 'column' => 'image_path', 'input' => 'image', 'size' => '800 × 500 px', 'ratio' => '16:10'],
                     ]],
                ],
            ],

            [
                'key' => 'steps', 'label' => 'How it works',
                'note' => 'Numbered steps from sign-up to results.',
                'parts' => [
                    ['kind' => 'single', 'type' => 'heading', 'label' => 'Band heading',
                     'match' => ['extra' => 'steps'], 'fields' => pe_heading_fields()],
                    ['kind' => 'list', 'type' => 'step', 'label' => 'Steps', 'addLabel' => 'Add step',
                     'fields' => [
                        ['name' => 'title', 'label' => 'Step title', 'column' => 'title', 'input' => 'text', 'required' => true, 'primary' => true],
                        ['name' => 'content', 'label' => 'Description', 'column' => 'content', 'input' => 'textarea', 'rows' => 2, 'secondary' => true],
                     ]],
                ],
            ],

            [
                'key' => 'stories', 'label' => 'Advertiser stories',
                'note' => 'Optional. The section is hidden until you add at least one story.',
                'parts' => [
                    ['kind' => 'single', 'type' => 'heading', 'label' => 'Band heading',
                     'match' => ['extra' => 'stories'], 'fields' => pe_heading_fields()],
                    ['kind' => 'list', 'type' => 'story', 'label' => 'Stories', 'addLabel' => 'Add story',
                     'fields' => [
                        ['name' => 'title', 'label' => 'Advertiser name', 'column' => 'title', 'input' => 'text', 'required' => true, 'primary' => true],
                        ['name' => 'content', 'label' => 'Quote', 'column' => 'content', 'input' => 'textarea', 'rows' => 4, 'secondary' => true],
                        ['name' => 'business', 'label' => 'Business', 'column' => 'extra', 'part' => 0, 'input' => 'text'],
                        ['name' => 'result', 'label' => 'Headline result', 'column' => 'extra', 'part' => 1, 'input' => 'text', 'placeholder' => '3x return on ad spend'],
                        ['name' => 'image', 'label' => 'Photo / logo', 'column' => 'image_path', 'input' => 'image', 'size' => '200 × 200 px', 'ratio' => 'square'],
                     ]],
                ],
            ],

            [
                'key' => 'faqs', 'label' => 'Frequently asked questions',
                'note' => 'Accordion questions and answers.',
                'parts' => [
                    ['kind' => 'single', 'type' => 'heading', 'label' => 'Band heading',
                     'match' => ['extra' => 'faqs'], 'fields' => pe_heading_fields()],
                    ['kind' => 'list', 'type' => 'faq', 'label' => 'Questions', 'addLabel' => 'Add question',
                     'fields' => [
                        ['name' => 'title', 'label' => 'Question', 'column' => 'title', 'input' => 'text', 'required' => true, 'primary' => true],
                        ['name' => 'content', 'label' => 'Answer', 'column' => 'content', 'input' => 'textarea', 'rows' => 4, 'secondary' => true],
                     ]],
                ],
            ],

            [
                'key' => 'form', 'label' => 'Enquiry form',
                'note' => 'The "talk to us" form. Enquiries arrive in Lead Inquiries (channel: Advertising).',
                'parts' => [
                    ['kind' => 'single', 'type' => 'heading', 'label' => 'Band heading',
                     'match' => ['extra' => 'form'], 'fields' => pe_heading_fields()],
                    ['kind' => 'list', 'type' => 'budget', 'label' => 'Monthly budget options', 'addLabel' => 'Add option',
                     'fields' => [
                        ['name' => 'title', 'label' => 'Option', 'column' => 'title', 'input' => 'text', 'required' => true, 'primary' => true, 'placeholder' => '₹25,000 – ₹1 lakh'],
                     ]],
                    ['kind' => 'single', 'type' => 'label', 'label' => 'Submit button',
                     'match' => ['extra' => 'form_submit'],
                     'fields' => [
                        ['name' => 'title', 'label' => 'Button label', 'column' => 'title', 'input' => 'text', 'required' => true, 'primary' => true, 'placeholder' => 'Request a callback'],
                     ]],
                    ['kind' => 'single', 'type' => 'contact', 'label' => 'Contact details',
                     'fields' => [
                        ['name' => 'title', 'label' => 'Heading', 'column' => 'title', 'input' => 'text', 'required' => true, 'primary' => true, 'placeholder' => 'Prefer to talk?'],
                        ['name' => 'phone', 'label' => 'Phone', 'column' => 'extra', 'part' => 0, 'input' => 'text'],
                        ['name' => 'email', 'label' => 'Email', 'column' => 'extra', 'part' => 1, 'input' => 'text'],
                     ]],
                ],
            ],

        ],
    ],
];
