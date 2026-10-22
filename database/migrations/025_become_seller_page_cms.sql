-- Migration 025: Become a Seller page — banner sections, extra CMS content, inner navbar (idempotent)
INSERT INTO banner_sections (page, section_key, label, columns_per_row, is_slider, status, display_order)
SELECT 'become-a-seller', 'become_seller_top_banner', 'Become a Seller – Top Banner (Desktop + Mobile)', 1, 1, 'active', 1
WHERE NOT EXISTS (SELECT 1 FROM banner_sections WHERE section_key = 'become_seller_top_banner');

INSERT INTO banner_sections (page, section_key, label, columns_per_row, is_slider, status, display_order)
SELECT 'become-a-seller', 'become_seller_mid_banner', 'Become a Seller – Mid Page Banner / Carousel', 1, 1, 'active', 2
WHERE NOT EXISTS (SELECT 1 FROM banner_sections WHERE section_key = 'become_seller_mid_banner');

INSERT INTO page_sections (page_key, section_type, title, content, extra, image_path, display_order, is_active)
SELECT 'become-a-seller', 'hero_point', '0% Marketplace Commission', '', '', '', 101, 1
WHERE NOT EXISTS (SELECT 1 FROM page_sections WHERE page_key='become-a-seller' AND section_type='hero_point' AND title='0% Marketplace Commission');
INSERT INTO page_sections (page_key, section_type, title, content, extra, image_path, display_order, is_active)
SELECT 'become-a-seller', 'hero_point', '19,000+ Pincodes Pickup', '', '', '', 102, 1
WHERE NOT EXISTS (SELECT 1 FROM page_sections WHERE page_key='become-a-seller' AND section_type='hero_point' AND title='19,000+ Pincodes Pickup');
INSERT INTO page_sections (page_key, section_type, title, content, extra, image_path, display_order, is_active)
SELECT 'become-a-seller', 'hero_point', 'Fast 7-Day Bank Payouts', '', '', '', 103, 1
WHERE NOT EXISTS (SELECT 1 FROM page_sections WHERE page_key='become-a-seller' AND section_type='hero_point' AND title='Fast 7-Day Bank Payouts');
INSERT INTO page_sections (page_key, section_type, title, content, extra, image_path, display_order, is_active)
SELECT 'become-a-seller', 'hero_point', 'Dedicated Account Manager', '', '', '', 104, 1
WHERE NOT EXISTS (SELECT 1 FROM page_sections WHERE page_key='become-a-seller' AND section_type='hero_point' AND title='Dedicated Account Manager');
INSERT INTO page_sections (page_key, section_type, title, content, extra, image_path, display_order, is_active)
SELECT 'become-a-seller', 'heading', 'Top Selling Categories on', 'Printmont|Join thousands of creators, printers, and manufacturers selling customized merchandise with huge buyer demand.', 'categories', '', 101, 1
WHERE NOT EXISTS (SELECT 1 FROM page_sections WHERE page_key='become-a-seller' AND section_type='heading' AND title='Top Selling Categories on');
INSERT INTO page_sections (page_key, section_type, title, content, extra, image_path, display_order, is_active)
SELECT 'become-a-seller', 'category', 'Custom T-Shirts & Apparel', 'Oversized graphic tees, polo shirts, hoodies, and corporate uniforms. High demand across youth & startups.', '35-50% Margin', '', 101, 1
WHERE NOT EXISTS (SELECT 1 FROM page_sections WHERE page_key='become-a-seller' AND section_type='category' AND title='Custom T-Shirts & Apparel');
INSERT INTO page_sections (page_key, section_type, title, content, extra, image_path, display_order, is_active)
SELECT 'become-a-seller', 'category', 'Ceramic Mugs & Drinkware', 'White gloss mugs, magic color-changing mugs, steel tumblers, and personalized sippers for gifts.', '40-55% Margin', '', 102, 1
WHERE NOT EXISTS (SELECT 1 FROM page_sections WHERE page_key='become-a-seller' AND section_type='category' AND title='Ceramic Mugs & Drinkware');
INSERT INTO page_sections (page_key, section_type, title, content, extra, image_path, display_order, is_active)
SELECT 'become-a-seller', 'category', 'Corporate Gift Sets', 'Executive diaries, engraved pens, power banks, and employee welcome kits for corporate clients.', '45-60% Margin', '', 103, 1
WHERE NOT EXISTS (SELECT 1 FROM page_sections WHERE page_key='become-a-seller' AND section_type='category' AND title='Corporate Gift Sets');
INSERT INTO page_sections (page_key, section_type, title, content, extra, image_path, display_order, is_active)
SELECT 'become-a-seller', 'category', 'Visiting Cards & Stationery', 'Spot UV visiting cards, textured paper cards, letterheads, and customized bill books for businesses.', '30-45% Margin', '', 104, 1
WHERE NOT EXISTS (SELECT 1 FROM page_sections WHERE page_key='become-a-seller' AND section_type='category' AND title='Visiting Cards & Stationery');
INSERT INTO page_sections (page_key, section_type, title, content, extra, image_path, display_order, is_active)
SELECT 'become-a-seller', 'category', 'Custom Boxes & Packaging', 'Rigid gift boxes, corrugated shipping mailers, branded courier tapes, and printed butter paper.', '35-50% Margin', '', 105, 1
WHERE NOT EXISTS (SELECT 1 FROM page_sections WHERE page_key='become-a-seller' AND section_type='category' AND title='Custom Boxes & Packaging');
INSERT INTO page_sections (page_key, section_type, title, content, extra, image_path, display_order, is_active)
SELECT 'become-a-seller', 'category', 'Photo Frames & Acrylic Prints', 'Custom LED song plaques, engraved photo frames, 3D illusion lamps, and customized wall clocks.', '40-60% Margin', '', 106, 1
WHERE NOT EXISTS (SELECT 1 FROM page_sections WHERE page_key='become-a-seller' AND section_type='category' AND title='Photo Frames & Acrylic Prints');
INSERT INTO page_sections (page_key, section_type, title, content, extra, image_path, display_order, is_active)
SELECT 'become-a-seller', 'heading', 'What You Need to', 'Start Selling|Get your seller account activated with just 4 basic details.', 'documents', '', 102, 1
WHERE NOT EXISTS (SELECT 1 FROM page_sections WHERE page_key='become-a-seller' AND section_type='heading' AND title='What You Need to');
INSERT INTO page_sections (page_key, section_type, title, content, extra, image_path, display_order, is_active)
SELECT 'become-a-seller', 'document', 'Active GSTIN / PAN', 'GSTIN for taxable goods or PAN card for composition / non-taxable products.', '', '', 101, 1
WHERE NOT EXISTS (SELECT 1 FROM page_sections WHERE page_key='become-a-seller' AND section_type='document' AND title='Active GSTIN / PAN');
INSERT INTO page_sections (page_key, section_type, title, content, extra, image_path, display_order, is_active)
SELECT 'become-a-seller', 'document', 'Active Bank Account', 'Cancelled cheque or bank statement in your business name for 7-day NEFT settlements.', '', '', 102, 1
WHERE NOT EXISTS (SELECT 1 FROM page_sections WHERE page_key='become-a-seller' AND section_type='document' AND title='Active Bank Account');
INSERT INTO page_sections (page_key, section_type, title, content, extra, image_path, display_order, is_active)
SELECT 'become-a-seller', 'document', 'Mobile & Email', 'Active phone number for instant WhatsApp order alerts and email for invoices.', '', '', 103, 1
WHERE NOT EXISTS (SELECT 1 FROM page_sections WHERE page_key='become-a-seller' AND section_type='document' AND title='Mobile & Email');
INSERT INTO page_sections (page_key, section_type, title, content, extra, image_path, display_order, is_active)
SELECT 'become-a-seller', 'document', '1+ Custom Product', 'At least one unique customized product ready to photograph and list online.', '', '', 104, 1
WHERE NOT EXISTS (SELECT 1 FROM page_sections WHERE page_key='become-a-seller' AND section_type='document' AND title='1+ Custom Product');
INSERT INTO page_sections (page_key, section_type, title, content, extra, image_path, display_order, is_active)
SELECT 'become-a-seller', 'heading', 'Frequently Asked', 'Questions|Everything you need to know about commissions, payments, shipping, and returns.', 'faqs', '', 103, 1
WHERE NOT EXISTS (SELECT 1 FROM page_sections WHERE page_key='become-a-seller' AND section_type='heading' AND title='Frequently Asked');
INSERT INTO page_sections (page_key, section_type, title, content, extra, image_path, display_order, is_active)
SELECT 'become-a-seller', 'faq', 'Is it truly 0% commission on Printmont? Are there hidden fees?', 'Yes, absolutely. Printmont operates on a strict 0% marketplace commission model for all product categories. There are zero listing fees, zero setup fees, and zero monthly subscriptions. Only standard doorstep logistics and nominal 2% payment gateway fees are deducted. You keep 100% of your earnings.', '', '', 101, 1
WHERE NOT EXISTS (SELECT 1 FROM page_sections WHERE page_key='become-a-seller' AND section_type='faq' AND title='Is it truly 0% commission on Printmont? Are there hidden fees?');
INSERT INTO page_sections (page_key, section_type, title, content, extra, image_path, display_order, is_active)
SELECT 'become-a-seller', 'faq', 'How and when do I receive payment for orders?', 'Payments are credited directly to your registered bank account via NEFT/RTGS on a guaranteed 7-day payment cycle from the date of successful order delivery. You can track all cleared, pending, and scheduled payouts in real-time inside your Seller Dashboard.', '', '', 102, 1
WHERE NOT EXISTS (SELECT 1 FROM page_sections WHERE page_key='become-a-seller' AND section_type='faq' AND title='How and when do I receive payment for orders?');
INSERT INTO page_sections (page_key, section_type, title, content, extra, image_path, display_order, is_active)
SELECT 'become-a-seller', 'faq', 'Do I need a GST number to sell on Printmont?', 'As per Indian e-commerce laws, an active GSTIN is required for selling taxable goods across states. However, if you are selling exempt categories or operating under specific composition thresholds, you can initiate registration using your PAN card while applying for GSTIN.', '', '', 103, 1
WHERE NOT EXISTS (SELECT 1 FROM page_sections WHERE page_key='become-a-seller' AND section_type='faq' AND title='Do I need a GST number to sell on Printmont?');
INSERT INTO page_sections (page_key, section_type, title, content, extra, image_path, display_order, is_active)
SELECT 'become-a-seller', 'faq', 'How does shipping and doorstep courier pickup work?', 'You do NOT need to visit any courier office. When an order arrives, generate a shipping label with one click on your Seller Dashboard. Attach the label to your securely packed parcel. Our integrated courier partners (Delhivery, BlueDart, DTDC, Xpressbees) will arrive at your workshop to scan and pick up the package.', '', '', 104, 1
WHERE NOT EXISTS (SELECT 1 FROM page_sections WHERE page_key='become-a-seller' AND section_type='faq' AND title='How does shipping and doorstep courier pickup work?');
INSERT INTO page_sections (page_key, section_type, title, content, extra, image_path, display_order, is_active)
SELECT 'become-a-seller', 'faq', 'Who handles customer returns (RTO) and transit damages?', 'All shipments on Printmont are protected under the Printmont Seller Shield. If a customized product is damaged in transit or lost by the courier, Printmont investigates and reimburses the seller. For personalized items, customer returns for frivolous reasons are restricted.', '', '', 105, 1
WHERE NOT EXISTS (SELECT 1 FROM page_sections WHERE page_key='become-a-seller' AND section_type='faq' AND title='Who handles customer returns (RTO) and transit damages?');

UPDATE header_page_settings SET show_category_bar = 1 WHERE page_key = 'become_seller';
