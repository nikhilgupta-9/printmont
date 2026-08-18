-- ============================================================
-- Migration 007: Security page CMS
--
-- The storefront Security page (components/security/SecurityInfo.jsx) was
-- entirely hardcoded — five payment-security Q&As plus two footer blocks,
-- editable only by a developer. This gives it the same treatment policies
-- and FAQ already have: a table, an API, and an admin editor.
--
-- Modelled on `policies`: a stable text key per section so the frontend can
-- reference one without depending on an auto-increment id.
--
-- Safe to re-run. CREATE TABLE IF NOT EXISTS and INSERT IGNORE mean a
-- database that already has this ends up recorded as auto-applied rather
-- than failing.
-- ============================================================

CREATE TABLE IF NOT EXISTS `security_sections` (
    `id`          INT(11)      NOT NULL AUTO_INCREMENT,
    `section_key` VARCHAR(60)  NOT NULL,
    `heading`     VARCHAR(255) NOT NULL,
    `content`     TEXT         DEFAULT NULL COMMENT 'Rich text (HTML) from CKEditor',
    `sort_order`  INT(11)      NOT NULL DEFAULT 0,
    `status`      ENUM('active','inactive') NOT NULL DEFAULT 'active',
    `created_at`  TIMESTAMP    NOT NULL DEFAULT current_timestamp(),
    `updated_at`  TIMESTAMP    NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_security_section_key` (`section_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Seed with exactly the copy that was hardcoded, so switching the page over
-- to the API changes nothing the customer sees.
INSERT IGNORE INTO `security_sections` (`section_key`, `heading`, `content`, `sort_order`, `status`) VALUES
('online-payment-secure',
 'Is making online payment secure on Printmont?',
 '<p>Yes, making the online payment is secure on Printmont.</p>',
 1, 'active'),

('card-storage',
 'Does Printmont store my credit/debit card information?',
 '<p>No. Printmont only stores the last 4 digits of your card number for the purpose of card identification.</p>',
 2, 'active'),

('cards-accepted',
 'What credit/debit cards are accepted on Printmont?',
 '<p>We accept VISA, MasterCard, Maestro, Rupay, American Express, Diner&rsquo;s Club and Discover credit/debit cards.</p>',
 3, 'active'),

('international-cards',
 'Do you accept payment made by credit/debit cards issued in other countries?',
 '<p>Yes! We accept VISA, MasterCard, Maestro, American Express credit/debit cards issued by banks in India and in the following countries: Australia, Austria, Belgium, Canada, Cyprus, Denmark, Finland, France, Germany, Ireland, Italy, Luxembourg, the Netherlands, New Zealand, Norway, Portugal, Singapore, Spain, Sweden, the UK and the US. Please note that we do not accept internationally issued credit/debit cards for EGV payments/top-ups.</p>',
 4, 'active'),

('other-payment-options',
 'What other payment options are available on Printmont?',
 '<p>Apart from Credit and Debit Cards, we accept payments via Internet Banking (covering 44 banks), Cash on Delivery, Equated Monthly Installments (EMI), E-Gift Vouchers, Printmont Pay Later, UPI, Wallet, and Paytm Postpaid.</p>',
 5, 'active');
