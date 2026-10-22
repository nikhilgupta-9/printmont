-- Migration 028: Advertising page — banner sections, CMS seed, header rule, footer link (idempotent)
INSERT INTO banner_sections (page, section_key, label, columns_per_row, is_slider, status, display_order)
SELECT 'advertising', 'advertising_top_banner', 'Advertising – Top Banner (Desktop + Mobile)', 1, 1, 'active', 1
WHERE NOT EXISTS (SELECT 1 FROM banner_sections WHERE section_key = 'advertising_top_banner');

INSERT INTO banner_sections (page, section_key, label, columns_per_row, is_slider, status, display_order)
SELECT 'advertising', 'advertising_mid_banner', 'Advertising – Mid Page Banner / Carousel', 1, 1, 'active', 2
WHERE NOT EXISTS (SELECT 1 FROM banner_sections WHERE section_key = 'advertising_mid_banner');

INSERT INTO page_sections (page_key, section_type, title, content, extra, image_path, display_order, is_active)
SELECT 'advertising', 'hero', 'Grow your business with Printmont Ads', 'Put your brand in front of shoppers who are ready to buy custom prints, gifts and corporate merchandise. Choose your budget, launch in minutes and track every result.', 'Start Advertising|Talk to our team', '', 1, 1
WHERE NOT EXISTS (SELECT 1 FROM page_sections WHERE page_key='advertising' AND section_type='hero' AND title='Grow your business with Printmont Ads');
INSERT INTO page_sections (page_key, section_type, title, content, extra, image_path, display_order, is_active)
SELECT 'advertising', 'hero_point', 'Reach intent-driven shoppers', '', '', '', 1, 1
WHERE NOT EXISTS (SELECT 1 FROM page_sections WHERE page_key='advertising' AND section_type='hero_point' AND title='Reach intent-driven shoppers');
INSERT INTO page_sections (page_key, section_type, title, content, extra, image_path, display_order, is_active)
SELECT 'advertising', 'hero_point', 'Flexible budgets, full control', '', '', '', 2, 1
WHERE NOT EXISTS (SELECT 1 FROM page_sections WHERE page_key='advertising' AND section_type='hero_point' AND title='Flexible budgets, full control');
INSERT INTO page_sections (page_key, section_type, title, content, extra, image_path, display_order, is_active)
SELECT 'advertising', 'hero_point', 'Dedicated campaign support', '', '', '', 3, 1
WHERE NOT EXISTS (SELECT 1 FROM page_sections WHERE page_key='advertising' AND section_type='hero_point' AND title='Dedicated campaign support');
INSERT INTO page_sections (page_key, section_type, title, content, extra, image_path, display_order, is_active)
SELECT 'advertising', 'hero_point', 'Clear performance reports', '', '', '', 4, 1
WHERE NOT EXISTS (SELECT 1 FROM page_sections WHERE page_key='advertising' AND section_type='hero_point' AND title='Clear performance reports');
INSERT INTO page_sections (page_key, section_type, title, content, extra, image_path, display_order, is_active)
SELECT 'advertising', 'stat', '50L+', 'Active buyers', '', '', 1, 1
WHERE NOT EXISTS (SELECT 1 FROM page_sections WHERE page_key='advertising' AND section_type='stat' AND title='50L+');
INSERT INTO page_sections (page_key, section_type, title, content, extra, image_path, display_order, is_active)
SELECT 'advertising', 'stat', '19,000+', 'Pincodes served', '', '', 2, 1
WHERE NOT EXISTS (SELECT 1 FROM page_sections WHERE page_key='advertising' AND section_type='stat' AND title='19,000+');
INSERT INTO page_sections (page_key, section_type, title, content, extra, image_path, display_order, is_active)
SELECT 'advertising', 'stat', '350+', 'Customizable products', '', '', 3, 1
WHERE NOT EXISTS (SELECT 1 FROM page_sections WHERE page_key='advertising' AND section_type='stat' AND title='350+');
INSERT INTO page_sections (page_key, section_type, title, content, extra, image_path, display_order, is_active)
SELECT 'advertising', 'stat', '24x7', 'Campaign support', '', '', 4, 1
WHERE NOT EXISTS (SELECT 1 FROM page_sections WHERE page_key='advertising' AND section_type='stat' AND title='24x7');
INSERT INTO page_sections (page_key, section_type, title, content, extra, image_path, display_order, is_active)
SELECT 'advertising', 'heading', 'Why advertise on', 'Printmont?|Reach customers at the moment they are looking for custom products, gifts and merchandise.', 'why', '', 1, 1
WHERE NOT EXISTS (SELECT 1 FROM page_sections WHERE page_key='advertising' AND section_type='heading' AND title='Why advertise on');
INSERT INTO page_sections (page_key, section_type, title, content, extra, image_path, display_order, is_active)
SELECT 'advertising', 'benefit', 'Reach buyers who are ready to buy', 'Your ads appear where shoppers search, browse categories and compare products — so every impression counts.', '', '', 1, 1
WHERE NOT EXISTS (SELECT 1 FROM page_sections WHERE page_key='advertising' AND section_type='benefit' AND title='Reach buyers who are ready to buy');
INSERT INTO page_sections (page_key, section_type, title, content, extra, image_path, display_order, is_active)
SELECT 'advertising', 'benefit', 'Flexible budgets', 'Start small and scale up. You decide how much to spend and can pause or change a campaign at any time.', '', '', 2, 1
WHERE NOT EXISTS (SELECT 1 FROM page_sections WHERE page_key='advertising' AND section_type='benefit' AND title='Flexible budgets');
INSERT INTO page_sections (page_key, section_type, title, content, extra, image_path, display_order, is_active)
SELECT 'advertising', 'benefit', 'Measurable results', 'See impressions, clicks and orders for every campaign so you know exactly what is working.', '', '', 3, 1
WHERE NOT EXISTS (SELECT 1 FROM page_sections WHERE page_key='advertising' AND section_type='benefit' AND title='Measurable results');
INSERT INTO page_sections (page_key, section_type, title, content, extra, image_path, display_order, is_active)
SELECT 'advertising', 'benefit', 'Easy to get started', 'Share your goals and our team sets up your campaign, creatives and targeting with you.', '', '', 4, 1
WHERE NOT EXISTS (SELECT 1 FROM page_sections WHERE page_key='advertising' AND section_type='benefit' AND title='Easy to get started');
INSERT INTO page_sections (page_key, section_type, title, content, extra, image_path, display_order, is_active)
SELECT 'advertising', 'heading', 'Advertising solutions for', 'every goal|Pick the format that fits your products and your budget.', 'solutions', '', 2, 1
WHERE NOT EXISTS (SELECT 1 FROM page_sections WHERE page_key='advertising' AND section_type='heading' AND title='Advertising solutions for');
INSERT INTO page_sections (page_key, section_type, title, content, extra, image_path, display_order, is_active)
SELECT 'advertising', 'solution', 'Sponsored Products', 'Put your products at the top of search results and category pages.|Appear above regular results for relevant searches
Target by keyword or category
Set a daily budget and stop any time
Track clicks and orders per product', 'Most popular', '', 1, 1
WHERE NOT EXISTS (SELECT 1 FROM page_sections WHERE page_key='advertising' AND section_type='solution' AND title='Sponsored Products');
INSERT INTO page_sections (page_key, section_type, title, content, extra, image_path, display_order, is_active)
SELECT 'advertising', 'solution', 'Display Banners', 'Premium banner placements on the home page and category pages.|Desktop and mobile creatives
Home page and category placements
Festive season takeovers
Link to any product, category or offer', '', '', 2, 1
WHERE NOT EXISTS (SELECT 1 FROM page_sections WHERE page_key='advertising' AND section_type='solution' AND title='Display Banners');
INSERT INTO page_sections (page_key, section_type, title, content, extra, image_path, display_order, is_active)
SELECT 'advertising', 'solution', 'Brand Spotlight', 'A dedicated spot for your brand or collection across our campaigns.|Featured collection on a spotlight page
Inclusion in campaign emails and notifications
Co-branded festive promotions
Dedicated account manager', '', '', 3, 1
WHERE NOT EXISTS (SELECT 1 FROM page_sections WHERE page_key='advertising' AND section_type='solution' AND title='Brand Spotlight');
INSERT INTO page_sections (page_key, section_type, title, content, extra, image_path, display_order, is_active)
SELECT 'advertising', 'heading', 'How advertising works', 'on Printmont|Go live in four simple steps.', 'steps', '', 3, 1
WHERE NOT EXISTS (SELECT 1 FROM page_sections WHERE page_key='advertising' AND section_type='heading' AND title='How advertising works');
INSERT INTO page_sections (page_key, section_type, title, content, extra, image_path, display_order, is_active)
SELECT 'advertising', 'step', 'Tell us your goals', 'Share your products, target audience and monthly budget through the enquiry form.', '', '', 1, 1
WHERE NOT EXISTS (SELECT 1 FROM page_sections WHERE page_key='advertising' AND section_type='step' AND title='Tell us your goals');
INSERT INTO page_sections (page_key, section_type, title, content, extra, image_path, display_order, is_active)
SELECT 'advertising', 'step', 'Choose your ad format', 'Our team recommends the right mix of sponsored products, banners or brand placements.', '', '', 2, 1
WHERE NOT EXISTS (SELECT 1 FROM page_sections WHERE page_key='advertising' AND section_type='step' AND title='Choose your ad format');
INSERT INTO page_sections (page_key, section_type, title, content, extra, image_path, display_order, is_active)
SELECT 'advertising', 'step', 'Launch your campaign', 'We set up creatives and targeting with you and take your campaign live.', '', '', 3, 1
WHERE NOT EXISTS (SELECT 1 FROM page_sections WHERE page_key='advertising' AND section_type='step' AND title='Launch your campaign');
INSERT INTO page_sections (page_key, section_type, title, content, extra, image_path, display_order, is_active)
SELECT 'advertising', 'step', 'Track and optimise', 'Review performance reports and fine-tune your campaigns for better results.', '', '', 4, 1
WHERE NOT EXISTS (SELECT 1 FROM page_sections WHERE page_key='advertising' AND section_type='step' AND title='Track and optimise');
INSERT INTO page_sections (page_key, section_type, title, content, extra, image_path, display_order, is_active)
SELECT 'advertising', 'heading', 'Advertiser', 'stories|See how brands grow with Printmont Ads.', 'stories', '', 4, 1
WHERE NOT EXISTS (SELECT 1 FROM page_sections WHERE page_key='advertising' AND section_type='heading' AND title='Advertiser');
INSERT INTO page_sections (page_key, section_type, title, content, extra, image_path, display_order, is_active)
SELECT 'advertising', 'heading', 'Frequently asked', 'questions|Everything you need to know before you start advertising.', 'faqs', '', 5, 1
WHERE NOT EXISTS (SELECT 1 FROM page_sections WHERE page_key='advertising' AND section_type='heading' AND title='Frequently asked');
INSERT INTO page_sections (page_key, section_type, title, content, extra, image_path, display_order, is_active)
SELECT 'advertising', 'faq', 'Who can advertise on Printmont?', 'Any registered seller or brand with products listed on Printmont can advertise. If you are not a seller yet, you can register and start advertising together.', '', '', 1, 1
WHERE NOT EXISTS (SELECT 1 FROM page_sections WHERE page_key='advertising' AND section_type='faq' AND title='Who can advertise on Printmont?');
INSERT INTO page_sections (page_key, section_type, title, content, extra, image_path, display_order, is_active)
SELECT 'advertising', 'faq', 'How much does it cost to get started?', 'Budgets are flexible — you decide how much to spend each month. Share your goals in the enquiry form and our team will suggest a plan that fits your budget.', '', '', 2, 1
WHERE NOT EXISTS (SELECT 1 FROM page_sections WHERE page_key='advertising' AND section_type='faq' AND title='How much does it cost to get started?');
INSERT INTO page_sections (page_key, section_type, title, content, extra, image_path, display_order, is_active)
SELECT 'advertising', 'faq', 'How do I pay for my campaigns?', 'Our team will confirm the payment options with you when your campaign is set up. You always know the budget before anything goes live.', '', '', 3, 1
WHERE NOT EXISTS (SELECT 1 FROM page_sections WHERE page_key='advertising' AND section_type='faq' AND title='How do I pay for my campaigns?');
INSERT INTO page_sections (page_key, section_type, title, content, extra, image_path, display_order, is_active)
SELECT 'advertising', 'faq', 'How do I see how my ads are performing?', 'You will receive regular performance reports covering impressions, clicks and orders so you can compare campaigns and decide where to invest more.', '', '', 4, 1
WHERE NOT EXISTS (SELECT 1 FROM page_sections WHERE page_key='advertising' AND section_type='faq' AND title='How do I see how my ads are performing?');
INSERT INTO page_sections (page_key, section_type, title, content, extra, image_path, display_order, is_active)
SELECT 'advertising', 'faq', 'How long does it take to launch a campaign?', 'Once your creatives and budget are confirmed, most campaigns can go live quickly. Your campaign manager will share the exact timeline for your plan.', '', '', 5, 1
WHERE NOT EXISTS (SELECT 1 FROM page_sections WHERE page_key='advertising' AND section_type='faq' AND title='How long does it take to launch a campaign?');
INSERT INTO page_sections (page_key, section_type, title, content, extra, image_path, display_order, is_active)
SELECT 'advertising', 'heading', 'Let''s grow your business', 'together|Tell us about your business and we will get back to you with a plan.', 'form', '', 6, 1
WHERE NOT EXISTS (SELECT 1 FROM page_sections WHERE page_key='advertising' AND section_type='heading' AND title='Let''s grow your business');
INSERT INTO page_sections (page_key, section_type, title, content, extra, image_path, display_order, is_active)
SELECT 'advertising', 'budget', 'Under ₹25,000 / month', '', '', '', 1, 1
WHERE NOT EXISTS (SELECT 1 FROM page_sections WHERE page_key='advertising' AND section_type='budget' AND title='Under ₹25,000 / month');
INSERT INTO page_sections (page_key, section_type, title, content, extra, image_path, display_order, is_active)
SELECT 'advertising', 'budget', '₹25,000 – ₹1 lakh / month', '', '', '', 2, 1
WHERE NOT EXISTS (SELECT 1 FROM page_sections WHERE page_key='advertising' AND section_type='budget' AND title='₹25,000 – ₹1 lakh / month');
INSERT INTO page_sections (page_key, section_type, title, content, extra, image_path, display_order, is_active)
SELECT 'advertising', 'budget', '₹1 lakh – ₹5 lakh / month', '', '', '', 3, 1
WHERE NOT EXISTS (SELECT 1 FROM page_sections WHERE page_key='advertising' AND section_type='budget' AND title='₹1 lakh – ₹5 lakh / month');
INSERT INTO page_sections (page_key, section_type, title, content, extra, image_path, display_order, is_active)
SELECT 'advertising', 'budget', '₹5 lakh+ / month', '', '', '', 4, 1
WHERE NOT EXISTS (SELECT 1 FROM page_sections WHERE page_key='advertising' AND section_type='budget' AND title='₹5 lakh+ / month');
INSERT INTO page_sections (page_key, section_type, title, content, extra, image_path, display_order, is_active)
SELECT 'advertising', 'budget', 'Not sure yet', '', '', '', 5, 1
WHERE NOT EXISTS (SELECT 1 FROM page_sections WHERE page_key='advertising' AND section_type='budget' AND title='Not sure yet');
INSERT INTO page_sections (page_key, section_type, title, content, extra, image_path, display_order, is_active)
SELECT 'advertising', 'label', 'Request a callback', '', 'form_submit', '', 1, 1
WHERE NOT EXISTS (SELECT 1 FROM page_sections WHERE page_key='advertising' AND section_type='label' AND title='Request a callback');
INSERT INTO page_sections (page_key, section_type, title, content, extra, image_path, display_order, is_active)
SELECT 'advertising', 'contact', 'Prefer to talk?', '', '+91 98185 32463|ads@printmont.com', '', 1, 1
WHERE NOT EXISTS (SELECT 1 FROM page_sections WHERE page_key='advertising' AND section_type='contact' AND title='Prefer to talk?');

-- Inner-page navbar rule for /advertising
INSERT INTO header_page_settings (page_key, page_name, page_group, route_patterns, header_type, show_category_bar, category_bar_mode, sort_order, is_active)
SELECT 'advertising', 'Advertising', 'corporate', '/advertising', 'inner', 1, 'text_only', 30, 1
WHERE NOT EXISTS (SELECT 1 FROM header_page_settings WHERE page_key = 'advertising');

-- Footer "Advertising" link now opens the Advertising page
UPDATE footer_bottom_links SET url = '/advertising' WHERE title = 'Advertising';
