-- Migration Script for MySQL Fulltext Indexing on PrintMont Database

-- 1. Fulltext Index on Products (Name, SKU, Brand, Description)
ALTER TABLE `products` 
ADD FULLTEXT INDEX `ft_products_search` (`name`, `sku`, `brand`, `description`);

-- 2. Fulltext Index on Categories (Category / Sub Category / Product Type Names)
ALTER TABLE `categories` 
ADD FULLTEXT INDEX `ft_categories_search` (`name`, `description`);
