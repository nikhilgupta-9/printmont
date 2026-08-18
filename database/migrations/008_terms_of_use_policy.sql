-- ============================================================
-- Migration 008: Restore "Terms of Use" as its own policy
--
-- Terms of Use and Terms & Conditions are two different documents:
--
--   Terms & Conditions -> the commercial/sales contract (pricing, GST,
--                         order verification, jurisdiction)
--   Terms of Use       -> website and platform access rules (account
--                         security, IP, prohibited conduct, uploads)
--
-- The storefront had both as separate tabs, but only `terms` ever existed
-- in the `policies` table — Terms of Use was hardcoded in the React page.
-- When the policy page was switched over to the API, that tab lost its
-- source and /terms-of-use started resolving to Terms & Conditions.
--
-- This restores it as a real, admin-editable row carrying the copy that
-- was previously hardcoded, so nothing the customer read is lost.
--
-- Safe to re-run: INSERT IGNORE, and policy_key is UNIQUE.
-- ============================================================

INSERT IGNORE INTO `policies` (`policy_key`, `heading`, `description`, `points`, `status`) VALUES
('terms-of-use',
 'Terms of Use',
 '<p>These Terms of Use specify the rules, guidelines, and acceptable conduct for accessing, browsing, interacting with design portals, and creating user accounts on PrintMont.</p><p><strong>Disclaimer:</strong> In case of any discrepancy between translations, the official English version of the Terms of Use shall take precedence.</p>',
 '["1. Platform Access & Account Security — Users are responsible for maintaining the confidentiality of their login credentials and all activities occurring under their account.","2. Intellectual Property Rights — All website code, UI designs, trademarks, brand logos, graphics, and product templates are owned exclusively by Printmont Corporation Pvt. Ltd.","3. Prohibited User Conduct — Users must not engage in automated scraping, uploading copyrighted graphics without permission, injecting malicious code, or hacking attempts.","4. Custom Design Upload Responsibilities — You represent that any graphic, trademark, or artwork uploaded for custom printing is owned by you or licensed with full authorization."]',
 'active');
