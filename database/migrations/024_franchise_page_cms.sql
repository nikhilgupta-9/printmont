-- Migration 024: Franchise page — banner sections + CMS seed content (idempotent)
INSERT INTO banner_sections (page, section_key, label, columns_per_row, is_slider, status, display_order)
SELECT 'franchise', 'franchise_top_banner', 'Franchise – Top Hero Banner (Desktop + Mobile)', 1, 1, 'active', 1
WHERE NOT EXISTS (SELECT 1 FROM banner_sections WHERE section_key = 'franchise_top_banner');

INSERT INTO banner_sections (page, section_key, label, columns_per_row, is_slider, status, display_order)
SELECT 'franchise', 'franchise_mid_banner', 'Franchise – Mid Page Banner / Carousel', 1, 1, 'active', 2
WHERE NOT EXISTS (SELECT 1 FROM banner_sections WHERE section_key = 'franchise_mid_banner');

INSERT INTO page_sections (page_key, section_type, title, content, extra, image_path, display_order, is_active)
SELECT 'franchise', 'hero', 'Start Your Entrepreneurial Journey With', 'Be an Entrepreneur, A Business Owner. Join 100+ high-growth retail stores across 50+ cities. Leverage our automated online order routing from printmont.com, 350+ personalized products, and earn healthy 35%–50% operating profit margins.', 'India''s Fastest Growing Customized Printing & Gifting Franchise|Printmont', '', 1, 1
WHERE NOT EXISTS (SELECT 1 FROM page_sections WHERE page_key='franchise' AND section_type='hero' AND title='Start Your Entrepreneurial Journey With');
INSERT INTO page_sections (page_key, section_type, title, content, extra, image_path, display_order, is_active)
SELECT 'franchise', 'hero_point', 'Omnichannel Web Order Routing', '', '', '', 1, 1
WHERE NOT EXISTS (SELECT 1 FROM page_sections WHERE page_key='franchise' AND section_type='hero_point' AND title='Omnichannel Web Order Routing');
INSERT INTO page_sections (page_key, section_type, title, content, extra, image_path, display_order, is_active)
SELECT 'franchise', 'hero_point', '35% - 50% Profit Margins', '', '', '', 2, 1
WHERE NOT EXISTS (SELECT 1 FROM page_sections WHERE page_key='franchise' AND section_type='hero_point' AND title='35% - 50% Profit Margins');
INSERT INTO page_sections (page_key, section_type, title, content, extra, image_path, display_order, is_active)
SELECT 'franchise', 'hero_point', 'Fast 9 - 14 Months ROI', '', '', '', 3, 1
WHERE NOT EXISTS (SELECT 1 FROM page_sections WHERE page_key='franchise' AND section_type='hero_point' AND title='Fast 9 - 14 Months ROI');
INSERT INTO page_sections (page_key, section_type, title, content, extra, image_path, display_order, is_active)
SELECT 'franchise', 'hero_point', 'Turnkey Setup & Machinery', '', '', '', 4, 1
WHERE NOT EXISTS (SELECT 1 FROM page_sections WHERE page_key='franchise' AND section_type='hero_point' AND title='Turnkey Setup & Machinery');
INSERT INTO page_sections (page_key, section_type, title, content, extra, image_path, display_order, is_active)
SELECT 'franchise', 'stat', '100+', 'Stores', '', '', 1, 1
WHERE NOT EXISTS (SELECT 1 FROM page_sections WHERE page_key='franchise' AND section_type='stat' AND title='100+');
INSERT INTO page_sections (page_key, section_type, title, content, extra, image_path, display_order, is_active)
SELECT 'franchise', 'stat', '50+', 'Cities', '', '', 2, 1
WHERE NOT EXISTS (SELECT 1 FROM page_sections WHERE page_key='franchise' AND section_type='stat' AND title='50+');
INSERT INTO page_sections (page_key, section_type, title, content, extra, image_path, display_order, is_active)
SELECT 'franchise', 'stat', '1.5L+', 'Happy Customers', '', '', 3, 1
WHERE NOT EXISTS (SELECT 1 FROM page_sections WHERE page_key='franchise' AND section_type='stat' AND title='1.5L+');
INSERT INTO page_sections (page_key, section_type, title, content, extra, image_path, display_order, is_active)
SELECT 'franchise', 'stat', '35-50%', 'Gross Margin', '', '', 4, 1
WHERE NOT EXISTS (SELECT 1 FROM page_sections WHERE page_key='franchise' AND section_type='stat' AND title='35-50%');
INSERT INTO page_sections (page_key, section_type, title, content, extra, image_path, display_order, is_active)
SELECT 'franchise', 'stat', '4.8/5', 'Partner Rating', '', '', 5, 1
WHERE NOT EXISTS (SELECT 1 FROM page_sections WHERE page_key='franchise' AND section_type='stat' AND title='4.8/5');
INSERT INTO page_sections (page_key, section_type, title, content, extra, image_path, display_order, is_active)
SELECT 'franchise', 'pillar', 'Strong Brand', 'Recognized and trusted by thousands across India', '', '', 1, 1
WHERE NOT EXISTS (SELECT 1 FROM page_sections WHERE page_key='franchise' AND section_type='pillar' AND title='Strong Brand');
INSERT INTO page_sections (page_key, section_type, title, content, extra, image_path, display_order, is_active)
SELECT 'franchise', 'pillar', 'Wide Product Range', '350+ SKUs: Custom apparel, mugs, frames, neon signs & hampers', '', '', 2, 1
WHERE NOT EXISTS (SELECT 1 FROM page_sections WHERE page_key='franchise' AND section_type='pillar' AND title='Wide Product Range');
INSERT INTO page_sections (page_key, section_type, title, content, extra, image_path, display_order, is_active)
SELECT 'franchise', 'pillar', 'Omnichannel Model', 'Strong Online + Offline Demands with local order routing', '', '', 3, 1
WHERE NOT EXISTS (SELECT 1 FROM page_sections WHERE page_key='franchise' AND section_type='pillar' AND title='Omnichannel Model');
INSERT INTO page_sections (page_key, section_type, title, content, extra, image_path, display_order, is_active)
SELECT 'franchise', 'pillar', 'Profitable Business Model', 'Low Risk, High Return with 35% to 50% profit margins', '', '', 4, 1
WHERE NOT EXISTS (SELECT 1 FROM page_sections WHERE page_key='franchise' AND section_type='pillar' AND title='Profitable Business Model');
INSERT INTO page_sections (page_key, section_type, title, content, extra, image_path, display_order, is_active)
SELECT 'franchise', 'pillar', 'Marketing Support', 'Pan-India digital campaigns, local store SEO & festive blitz', '', '', 5, 1
WHERE NOT EXISTS (SELECT 1 FROM page_sections WHERE page_key='franchise' AND section_type='pillar' AND title='Marketing Support');
INSERT INTO page_sections (page_key, section_type, title, content, extra, image_path, display_order, is_active)
SELECT 'franchise', 'pillar', 'End-to-End Support', 'From 3D store fit-out and machinery setup to daily operations', '', '', 6, 1
WHERE NOT EXISTS (SELECT 1 FROM page_sections WHERE page_key='franchise' AND section_type='pillar' AND title='End-to-End Support');
INSERT INTO page_sections (page_key, section_type, title, content, extra, image_path, display_order, is_active)
SELECT 'franchise', 'model', 'QSR / KIOSK MODEL', 'Heavy Footfall Areas, Near Colleges, Metro Stations & Shopping Malls|Instant mug, phone cover & keychain printing
Personalized keychains, acrylic desk frames & quick gifts
Compact footprint with high walk-in impulse sales
Minimal staff requirement (1-2 members)
Turnkey setup with certified compact machinery', 'Low Investment|no|100 - 250 sq.ft.|₹ 4 - 7 Lakh* + GST|Upto ₹ 1.2 Lakh*|6 - 9 Months', '/franchise/model-kiosk.jpg', 1, 1
WHERE NOT EXISTS (SELECT 1 FROM page_sections WHERE page_key='franchise' AND section_type='model' AND title='QSR / KIOSK MODEL');
INSERT INTO page_sections (page_key, section_type, title, content, extra, image_path, display_order, is_active)
SELECT 'franchise', 'model', 'STUDIO & EXPERIENCE CENTER', 'Market Places, Commercial Areas & Prime High Streets|Live customized apparel lounge (T-Shirts, Hoodies, Caps)
On-site customer digital design & touch preview stations
Automatic routing of local Printmont.com online orders
Acrylic photo frames, laser-engraved gifts & hampers
Corporate merchandising & consultation desk
Integrated POS, ERP billing & digital catalog system', 'Most Popular|yes|350 - 700 sq.ft.|₹ 8 - 14 Lakh* + GST|Upto ₹ 2.5 Lakh*|9 - 14 Months', '/franchise/model-studio.jpg', 2, 1
WHERE NOT EXISTS (SELECT 1 FROM page_sections WHERE page_key='franchise' AND section_type='model' AND title='STUDIO & EXPERIENCE CENTER');
INSERT INTO page_sections (page_key, section_type, title, content, extra, image_path, display_order, is_active)
SELECT 'franchise', 'model', 'REGIONAL PRODUCTION HUB', 'Main Roads, Commercial Corridors & District Hubs|Large-format industrial digital printing setup
Territory-wide B2B corporate fulfillment rights
Master inventory hub supplying district kiosk partners
High-volume institutional client contracts
Exclusive territory protection with maximum yield', 'Master Partner|no|1000 - 2000 sq.ft.|₹ 20 - 35 Lakh* + GST|Upto ₹ 5.5 Lakh*|14 - 18 Months', '/franchise/model-hub.jpg', 3, 1
WHERE NOT EXISTS (SELECT 1 FROM page_sections WHERE page_key='franchise' AND section_type='model' AND title='REGIONAL PRODUCTION HUB');
INSERT INTO page_sections (page_key, section_type, title, content, extra, image_path, display_order, is_active)
SELECT 'franchise', 'milestone', 'Brand Inception', 'Founded in New Delhi with custom apparel and precision direct-to-garment digital printing.', '2019', '', 1, 1
WHERE NOT EXISTS (SELECT 1 FROM page_sections WHERE page_key='franchise' AND section_type='milestone' AND title='Brand Inception');
INSERT INTO page_sections (page_key, section_type, title, content, extra, image_path, display_order, is_active)
SELECT 'franchise', 'milestone', '50K+ Milestone', 'Crossed 50,000+ orders nationwide. Expanded catalogue to personalized mugs, canvas & acrylic photo gifts.', '2021', '', 2, 1
WHERE NOT EXISTS (SELECT 1 FROM page_sections WHERE page_key='franchise' AND section_type='milestone' AND title='50K+ Milestone');
INSERT INTO page_sections (page_key, section_type, title, content, extra, image_path, display_order, is_active)
SELECT 'franchise', 'milestone', 'Central Automated Hub', 'Commissioned 15,000 sq.ft state-of-the-art production facility for factory-direct corporate merchandise.', '2023', '', 3, 1
WHERE NOT EXISTS (SELECT 1 FROM page_sections WHERE page_key='franchise' AND section_type='milestone' AND title='Central Automated Hub');
INSERT INTO page_sections (page_key, section_type, title, content, extra, image_path, display_order, is_active)
SELECT 'franchise', 'milestone', 'Omnichannel Model', 'Launched proprietary cloud order-routing engine, connecting physical studios with printmont.com orders.', '2024', '', 4, 1
WHERE NOT EXISTS (SELECT 1 FROM page_sections WHERE page_key='franchise' AND section_type='milestone' AND title='Omnichannel Model');
INSERT INTO page_sections (page_key, section_type, title, content, extra, image_path, display_order, is_active)
SELECT 'franchise', 'milestone', '100+ Stores Nationwide', 'Pan-India retail franchise network across 50+ cities, delivering high margins to proud franchise partners.', '2026', '', 5, 1
WHERE NOT EXISTS (SELECT 1 FROM page_sections WHERE page_key='franchise' AND section_type='milestone' AND title='100+ Stores Nationwide');
INSERT INTO page_sections (page_key, section_type, title, content, extra, image_path, display_order, is_active)
SELECT 'franchise', 'store', 'New Delhi', 'South Extension Flagship Studio', 'Experience Studio', '/franchise/store-delhi.jpg', 1, 1
WHERE NOT EXISTS (SELECT 1 FROM page_sections WHERE page_key='franchise' AND section_type='store' AND title='New Delhi');
INSERT INTO page_sections (page_key, section_type, title, content, extra, image_path, display_order, is_active)
SELECT 'franchise', 'store', 'Mumbai', 'Bandra West Custom Lounge', 'Flagship Store', '/franchise/store-mumbai.jpg', 2, 1
WHERE NOT EXISTS (SELECT 1 FROM page_sections WHERE page_key='franchise' AND section_type='store' AND title='Mumbai');
INSERT INTO page_sections (page_key, section_type, title, content, extra, image_path, display_order, is_active)
SELECT 'franchise', 'store', 'Bengaluru', 'Indiranagar 100ft Rd Studio', 'Experience Center', '/franchise/store-bangalore.jpg', 3, 1
WHERE NOT EXISTS (SELECT 1 FROM page_sections WHERE page_key='franchise' AND section_type='store' AND title='Bengaluru');
INSERT INTO page_sections (page_key, section_type, title, content, extra, image_path, display_order, is_active)
SELECT 'franchise', 'step', 'Apply', 'Share your details and preferred city', '', '', 1, 1
WHERE NOT EXISTS (SELECT 1 FROM page_sections WHERE page_key='franchise' AND section_type='step' AND title='Apply');
INSERT INTO page_sections (page_key, section_type, title, content, extra, image_path, display_order, is_active)
SELECT 'franchise', 'step', 'Discussion', 'We connect & understand your business plan', '', '', 2, 1
WHERE NOT EXISTS (SELECT 1 FROM page_sections WHERE page_key='franchise' AND section_type='step' AND title='Discussion');
INSERT INTO page_sections (page_key, section_type, title, content, extra, image_path, display_order, is_active)
SELECT 'franchise', 'step', 'Location Review', 'We evaluate & help you finalize prime spot', '', '', 3, 1
WHERE NOT EXISTS (SELECT 1 FROM page_sections WHERE page_key='franchise' AND section_type='step' AND title='Location Review');
INSERT INTO page_sections (page_key, section_type, title, content, extra, image_path, display_order, is_active)
SELECT 'franchise', 'step', 'Agreement', 'Sign franchise agreement with territory exclusivity', '', '', 4, 1
WHERE NOT EXISTS (SELECT 1 FROM page_sections WHERE page_key='franchise' AND section_type='step' AND title='Agreement');
INSERT INTO page_sections (page_key, section_type, title, content, extra, image_path, display_order, is_active)
SELECT 'franchise', 'step', 'Store Setup', 'We help you with 3D design & machine installation', '', '', 5, 1
WHERE NOT EXISTS (SELECT 1 FROM page_sections WHERE page_key='franchise' AND section_type='step' AND title='Store Setup');
INSERT INTO page_sections (page_key, section_type, title, content, extra, image_path, display_order, is_active)
SELECT 'franchise', 'step', 'Launch', 'Start your business with marketing ads & web order routing', '', '', 6, 1
WHERE NOT EXISTS (SELECT 1 FROM page_sections WHERE page_key='franchise' AND section_type='step' AND title='Launch');
INSERT INTO page_sections (page_key, section_type, title, content, extra, image_path, display_order, is_active)
SELECT 'franchise', 'support', '1:1 Relationship Manager', 'Get a dedicated business manager to guide and support you at every stage of growth.', '', '', 1, 1
WHERE NOT EXISTS (SELECT 1 FROM page_sections WHERE page_key='franchise' AND section_type='support' AND title='1:1 Relationship Manager');
INSERT INTO page_sections (page_key, section_type, title, content, extra, image_path, display_order, is_active)
SELECT 'franchise', 'support', 'Digital Marketing Support', 'Drive walk-in customers with expert digital marketing, Meta ads, and festive campaigns.', '', '', 2, 1
WHERE NOT EXISTS (SELECT 1 FROM page_sections WHERE page_key='franchise' AND section_type='support' AND title='Digital Marketing Support');
INSERT INTO page_sections (page_key, section_type, title, content, extra, image_path, display_order, is_active)
SELECT 'franchise', 'support', 'Continuous New Products', 'Stay ahead with regular new apparel blanks, trendy gift collections, and festive items.', '', '', 3, 1
WHERE NOT EXISTS (SELECT 1 FROM page_sections WHERE page_key='franchise' AND section_type='support' AND title='Continuous New Products');
INSERT INTO page_sections (page_key, section_type, title, content, extra, image_path, display_order, is_active)
SELECT 'franchise', 'support', 'Manpower & Training Support', 'Hands-on assistance with hiring, machine operator certification, and customer service SOPs.', '', '', 4, 1
WHERE NOT EXISTS (SELECT 1 FROM page_sections WHERE page_key='franchise' AND section_type='support' AND title='Manpower & Training Support');
INSERT INTO page_sections (page_key, section_type, title, content, extra, image_path, display_order, is_active)
SELECT 'franchise', 'support', 'Offline Media Support', 'Boost local visibility with premium shopfront branding, acrylic displays, and standees.', '', '', 5, 1
WHERE NOT EXISTS (SELECT 1 FROM page_sections WHERE page_key='franchise' AND section_type='support' AND title='Offline Media Support');
INSERT INTO page_sections (page_key, section_type, title, content, extra, image_path, display_order, is_active)
SELECT 'franchise', 'ecosystem', 'Custom T-Shirts', '', '👕', '', 1, 1
WHERE NOT EXISTS (SELECT 1 FROM page_sections WHERE page_key='franchise' AND section_type='ecosystem' AND title='Custom T-Shirts');
INSERT INTO page_sections (page_key, section_type, title, content, extra, image_path, display_order, is_active)
SELECT 'franchise', 'ecosystem', 'Hoodies & Jackets', '', '🧥', '', 2, 1
WHERE NOT EXISTS (SELECT 1 FROM page_sections WHERE page_key='franchise' AND section_type='ecosystem' AND title='Hoodies & Jackets');
INSERT INTO page_sections (page_key, section_type, title, content, extra, image_path, display_order, is_active)
SELECT 'franchise', 'ecosystem', 'Ceramic Coffee Mugs', '', '☕', '', 3, 1
WHERE NOT EXISTS (SELECT 1 FROM page_sections WHERE page_key='franchise' AND section_type='ecosystem' AND title='Ceramic Coffee Mugs');
INSERT INTO page_sections (page_key, section_type, title, content, extra, image_path, display_order, is_active)
SELECT 'franchise', 'ecosystem', 'Magic Color Mugs', '', '✨', '', 4, 1
WHERE NOT EXISTS (SELECT 1 FROM page_sections WHERE page_key='franchise' AND section_type='ecosystem' AND title='Magic Color Mugs');
INSERT INTO page_sections (page_key, section_type, title, content, extra, image_path, display_order, is_active)
SELECT 'franchise', 'ecosystem', '3D Illusion Lamps', '', '💡', '', 5, 1
WHERE NOT EXISTS (SELECT 1 FROM page_sections WHERE page_key='franchise' AND section_type='ecosystem' AND title='3D Illusion Lamps');
INSERT INTO page_sections (page_key, section_type, title, content, extra, image_path, display_order, is_active)
SELECT 'franchise', 'ecosystem', 'Custom Neon Signs', '', '⚡', '', 6, 1
WHERE NOT EXISTS (SELECT 1 FROM page_sections WHERE page_key='franchise' AND section_type='ecosystem' AND title='Custom Neon Signs');
INSERT INTO page_sections (page_key, section_type, title, content, extra, image_path, display_order, is_active)
SELECT 'franchise', 'ecosystem', 'Framed Canvas Art', '', '🖼️', '', 7, 1
WHERE NOT EXISTS (SELECT 1 FROM page_sections WHERE page_key='franchise' AND section_type='ecosystem' AND title='Framed Canvas Art');
INSERT INTO page_sections (page_key, section_type, title, content, extra, image_path, display_order, is_active)
SELECT 'franchise', 'ecosystem', 'Engraved Wooden Plaques', '', '🪵', '', 8, 1
WHERE NOT EXISTS (SELECT 1 FROM page_sections WHERE page_key='franchise' AND section_type='ecosystem' AND title='Engraved Wooden Plaques');
INSERT INTO page_sections (page_key, section_type, title, content, extra, image_path, display_order, is_active)
SELECT 'franchise', 'ecosystem', 'Corporate Gift Kits', '', '🎁', '', 9, 1
WHERE NOT EXISTS (SELECT 1 FROM page_sections WHERE page_key='franchise' AND section_type='ecosystem' AND title='Corporate Gift Kits');
INSERT INTO page_sections (page_key, section_type, title, content, extra, image_path, display_order, is_active)
SELECT 'franchise', 'ecosystem', 'Insulated Steel Sippers', '', '🥤', '', 10, 1
WHERE NOT EXISTS (SELECT 1 FROM page_sections WHERE page_key='franchise' AND section_type='ecosystem' AND title='Insulated Steel Sippers');
INSERT INTO page_sections (page_key, section_type, title, content, extra, image_path, display_order, is_active)
SELECT 'franchise', 'ecosystem', 'Custom Phone Covers', '', '📱', '', 11, 1
WHERE NOT EXISTS (SELECT 1 FROM page_sections WHERE page_key='franchise' AND section_type='ecosystem' AND title='Custom Phone Covers');
INSERT INTO page_sections (page_key, section_type, title, content, extra, image_path, display_order, is_active)
SELECT 'franchise', 'ecosystem', 'Pop Sockets & Grips', '', '🔘', '', 12, 1
WHERE NOT EXISTS (SELECT 1 FROM page_sections WHERE page_key='franchise' AND section_type='ecosystem' AND title='Pop Sockets & Grips');
INSERT INTO page_sections (page_key, section_type, title, content, extra, image_path, display_order, is_active)
SELECT 'franchise', 'ecosystem', 'Badges & ID Lanyards', '', '🏷️', '', 13, 1
WHERE NOT EXISTS (SELECT 1 FROM page_sections WHERE page_key='franchise' AND section_type='ecosystem' AND title='Badges & ID Lanyards');
INSERT INTO page_sections (page_key, section_type, title, content, extra, image_path, display_order, is_active)
SELECT 'franchise', 'ecosystem', 'Canvas Tote Bags', '', '🛍️', '', 14, 1
WHERE NOT EXISTS (SELECT 1 FROM page_sections WHERE page_key='franchise' AND section_type='ecosystem' AND title='Canvas Tote Bags');
INSERT INTO page_sections (page_key, section_type, title, content, extra, image_path, display_order, is_active)
SELECT 'franchise', 'ecosystem', 'Personalized Diaries', '', '📓', '', 15, 1
WHERE NOT EXISTS (SELECT 1 FROM page_sections WHERE page_key='franchise' AND section_type='ecosystem' AND title='Personalized Diaries');
INSERT INTO page_sections (page_key, section_type, title, content, extra, image_path, display_order, is_active)
SELECT 'franchise', 'ecosystem', 'Laser-Engraved Pens', '', '✒️', '', 16, 1
WHERE NOT EXISTS (SELECT 1 FROM page_sections WHERE page_key='franchise' AND section_type='ecosystem' AND title='Laser-Engraved Pens');
INSERT INTO page_sections (page_key, section_type, title, content, extra, image_path, display_order, is_active)
SELECT 'franchise', 'ecosystem', 'Acrylic Desk Clocks', '', '⏰', '', 17, 1
WHERE NOT EXISTS (SELECT 1 FROM page_sections WHERE page_key='franchise' AND section_type='ecosystem' AND title='Acrylic Desk Clocks');
INSERT INTO page_sections (page_key, section_type, title, content, extra, image_path, display_order, is_active)
SELECT 'franchise', 'ecosystem', 'Metal Keychains', '', '🔑', '', 18, 1
WHERE NOT EXISTS (SELECT 1 FROM page_sections WHERE page_key='franchise' AND section_type='ecosystem' AND title='Metal Keychains');
INSERT INTO page_sections (page_key, section_type, title, content, extra, image_path, display_order, is_active)
SELECT 'franchise', 'ecosystem', 'Custom Water Bottles', '', '🍶', '', 19, 1
WHERE NOT EXISTS (SELECT 1 FROM page_sections WHERE page_key='franchise' AND section_type='ecosystem' AND title='Custom Water Bottles');
INSERT INTO page_sections (page_key, section_type, title, content, extra, image_path, display_order, is_active)
SELECT 'franchise', 'ecosystem', 'Trophies & Mementos', '', '🏆', '', 20, 1
WHERE NOT EXISTS (SELECT 1 FROM page_sections WHERE page_key='franchise' AND section_type='ecosystem' AND title='Trophies & Mementos');
INSERT INTO page_sections (page_key, section_type, title, content, extra, image_path, display_order, is_active)
SELECT 'franchise', 'ecosystem', 'Luxury Gift Boxes', '', '📦', '', 21, 1
WHERE NOT EXISTS (SELECT 1 FROM page_sections WHERE page_key='franchise' AND section_type='ecosystem' AND title='Luxury Gift Boxes');
INSERT INTO page_sections (page_key, section_type, title, content, extra, image_path, display_order, is_active)
SELECT 'franchise', 'story', 'Amit Verma', 'Opening a Printmont store was our best business decision. The omnichannel web order routing brought customers from day one, and we achieved operational break-even within 45 days!', 'Franchise Partner|South Extension, New Delhi|₹3.8 Lakh / Month', '', 1, 1
WHERE NOT EXISTS (SELECT 1 FROM page_sections WHERE page_key='franchise' AND section_type='story' AND title='Amit Verma');
INSERT INTO page_sections (page_key, section_type, title, content, extra, image_path, display_order, is_active)
SELECT 'franchise', 'story', 'Pooja Kulkarni', 'The hands-on machinery training and turnkey setup support is incredible. Our store caters to college students for instant custom gifts and local IT companies for bulk uniforms.', 'Studio Owner|FC Road, Pune|₹4.2 Lakh / Month', '', 2, 1
WHERE NOT EXISTS (SELECT 1 FROM page_sections WHERE page_key='franchise' AND section_type='story' AND title='Pooja Kulkarni');
INSERT INTO page_sections (page_key, section_type, title, content, extra, image_path, display_order, is_active)
SELECT 'franchise', 'story', 'Karthik Rao', 'Direct factory pricing on apparel blanks and mugs allows us to maintain 45%+ gross margins. The Printmont team''s tech support and territory protection makes it a winning model.', 'Master Hub Partner|Indiranagar, Bengaluru|₹5.6 Lakh / Month', '', 3, 1
WHERE NOT EXISTS (SELECT 1 FROM page_sections WHERE page_key='franchise' AND section_type='story' AND title='Karthik Rao');
INSERT INTO page_sections (page_key, section_type, title, content, extra, image_path, display_order, is_active)
SELECT 'franchise', 'story', 'Vikramaditya Shekhawat', 'The live sublimation printing station attracts college students and corporate clients daily. We recovered our initial machinery setup costs within 8 months!', 'Studio Partner|MI Road, Jaipur|₹3.4 Lakh / Month', '', 4, 1
WHERE NOT EXISTS (SELECT 1 FROM page_sections WHERE page_key='franchise' AND section_type='story' AND title='Vikramaditya Shekhawat');
INSERT INTO page_sections (page_key, section_type, title, content, extra, image_path, display_order, is_active)
SELECT 'franchise', 'story', 'Sneha Patel', 'Even with a compact 200 sq.ft kiosk, we process 40+ personalized gifts daily. The Printmont central supply chain delivers blanks super fast with consistent quality.', 'Express Kiosk Owner|SG Highway, Ahmedabad|₹2.9 Lakh / Month', '', 5, 1
WHERE NOT EXISTS (SELECT 1 FROM page_sections WHERE page_key='franchise' AND section_type='story' AND title='Sneha Patel');
INSERT INTO page_sections (page_key, section_type, title, content, extra, image_path, display_order, is_active)
SELECT 'franchise', 'faq', 'What is the minimum investment required to open a Printmont franchise?', 'Our franchise investment models start at ₹4 Lakhs to ₹7 Lakhs for a QSR / Express Kiosk (100–250 sq.ft) and ₹8 to ₹14 Lakhs for a standard Studio Experience Center (350–700 sq.ft). This includes 3D interior design guidance, certified machinery, initial blank inventory, POS software setup, and comprehensive staff training.', '', '', 1, 1
WHERE NOT EXISTS (SELECT 1 FROM page_sections WHERE page_key='franchise' AND section_type='faq' AND title='What is the minimum investment required to open a Printmont franchise?');
INSERT INTO page_sections (page_key, section_type, title, content, extra, image_path, display_order, is_active)
SELECT 'franchise', 'faq', 'Do I need prior technical experience in printing or gifting to run this business?', 'No prior technical experience in printing is needed! Printmont provides complete operational, machinery, and customer management training. Modern digital print equipment is automated, user-friendly, and simple to operate after our 14-day hands-on training program.', '', '', 2, 1
WHERE NOT EXISTS (SELECT 1 FROM page_sections WHERE page_key='franchise' AND section_type='faq' AND title='Do I need prior technical experience in printing or gifting to run this business?');
INSERT INTO page_sections (page_key, section_type, title, content, extra, image_path, display_order, is_active)
SELECT 'franchise', 'faq', 'How does Printmont''s Omnichannel Online Order Routing benefit my store?', 'Unlike traditional franchises where online e-commerce competes with physical stores, Printmont actively routes online orders placed on Printmont.com from your assigned territory directly to your store for local fulfillment and pickup, providing an immediate recurring revenue stream from day one.', '', '', 3, 1
WHERE NOT EXISTS (SELECT 1 FROM page_sections WHERE page_key='franchise' AND section_type='faq' AND title='How does Printmont''s Omnichannel Online Order Routing benefit my store?');
INSERT INTO page_sections (page_key, section_type, title, content, extra, image_path, display_order, is_active)
SELECT 'franchise', 'faq', 'What is the expected ROI and break-even timeline?', 'Most Printmont franchise stores achieve monthly operational break-even within the first 45–60 days and full return on capital investment (ROI) within 9 to 14 months, driven by healthy 35% to 50% gross profit margins across personalized merchandise.', '', '', 4, 1
WHERE NOT EXISTS (SELECT 1 FROM page_sections WHERE page_key='franchise' AND section_type='faq' AND title='What is the expected ROI and break-even timeline?');
INSERT INTO page_sections (page_key, section_type, title, content, extra, image_path, display_order, is_active)
SELECT 'franchise', 'faq', 'Does Printmont supply the printing machines, blanks, and raw consumables?', 'Yes! Printmont manages end-to-end supply chain logistics. All certified industrial heat presses, sublimation printers, apparel blanks, ceramic mugs, photo frames, and branded gift boxes are supplied directly from our central warehouses at factory wholesale rates.', '', '', 5, 1
WHERE NOT EXISTS (SELECT 1 FROM page_sections WHERE page_key='franchise' AND section_type='faq' AND title='Does Printmont supply the printing machines, blanks, and raw consumables?');
INSERT INTO page_sections (page_key, section_type, title, content, extra, image_path, display_order, is_active)
SELECT 'franchise', 'faq', 'Can my franchise store handle large corporate bulk orders?', 'Absolutely! While retail customers enjoy instant customized gifts in-store, franchise owners are encouraged to secure corporate and institutional orders in their city. For very large runs, our centralized mega factory manufactures the products on your behalf while you earn profit margins.', '', '', 6, 1
WHERE NOT EXISTS (SELECT 1 FROM page_sections WHERE page_key='franchise' AND section_type='faq' AND title='Can my franchise store handle large corporate bulk orders?');
INSERT INTO page_sections (page_key, section_type, title, content, extra, image_path, display_order, is_active)
SELECT 'franchise', 'contact', 'Talk to Franchise Expansion Director', '', '+91 98185 32463|franchise@printmont.com|New Delhi, India', '/franchise/store-delhi.jpg', 1, 1
WHERE NOT EXISTS (SELECT 1 FROM page_sections WHERE page_key='franchise' AND section_type='contact' AND title='Talk to Franchise Expansion Director');

-- Show the inner-page category navbar on the Franchise and Bulk Orders pages (desktop)
UPDATE header_page_settings SET show_category_bar = 1 WHERE page_key IN ('franchise', 'bulk_orders');
