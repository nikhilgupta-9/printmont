-- Shorten storefront labels for the current main categories.
-- Existing slugs, category IDs, and product relationships remain unchanged.
UPDATE `categories`
SET `name` = CASE `slug`
  WHEN 'corporate-gifts-hampers' THEN 'Corporate Gifts'
  WHEN 'custom-drinkware-sippers' THEN 'Drinkware'
  WHEN 'custom-apparel-wearables' THEN 'Apparel'
  WHEN 'stationery-office-gifts' THEN 'Stationery'
  WHEN 'tech-smart-electronics' THEN 'Electronics'
  WHEN 'bags-wallets-travel' THEN 'Bags & Travel'
  WHEN 'plants-eco-friendly-gifts' THEN 'Plants & Eco Gifts'
  WHEN 'gourmet-chocolate-hampers' THEN 'Gourmet Gifts'
  WHEN 'awards-trophies-mementos' THEN 'Awards & Trophies'
  WHEN 'promotional-pen-s' THEN 'Pens & Writing'
  ELSE `name`
END
WHERE `level` = 1
  AND `slug` IN (
    'corporate-gifts-hampers',
    'custom-drinkware-sippers',
    'custom-apparel-wearables',
    'stationery-office-gifts',
    'tech-smart-electronics',
    'bags-wallets-travel',
    'plants-eco-friendly-gifts',
    'gourmet-chocolate-hampers',
    'awards-trophies-mementos',
    'promotional-pen-s'
  );