-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: printmont_db
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `parent_id` int(11) DEFAULT 0,
  `image` varchar(500) DEFAULT NULL,
  `icon` varchar(100) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `display_order` int(11) DEFAULT 0,
  `is_featured` tinyint(1) DEFAULT 0,
  `level` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `desktop_menu_status` enum('show','hide') DEFAULT 'show',
  `desktop_menu_order` int(11) DEFAULT 0,
  `desktop_menu_view` enum('yes','no') DEFAULT 'no',
  `desktop_menu_design` varchar(50) DEFAULT '',
  `desktop_menu_tag` varchar(100) DEFAULT '',
  `desktop_menu_image` varchar(255) DEFAULT '',
  `desktop_home_show` enum('yes','no') DEFAULT 'no',
  `desktop_home_design` varchar(50) DEFAULT '',
  `desktop_home_order` int(11) DEFAULT 0,
  `desktop_bg_color` varchar(20) DEFAULT '',
  `desktop_bg_image` varchar(255) DEFAULT '',
  `desktop_image` varchar(255) DEFAULT '',
  `mobile_topbar_status` enum('show','hide') DEFAULT 'show',
  `mobile_topbar_order` int(11) DEFAULT 0,
  `mobile_menu_view` enum('yes','no') DEFAULT 'no',
  `mobile_menu_design` varchar(50) DEFAULT '',
  `mobile_sidebar_order` int(11) DEFAULT 0,
  `mobile_home_show` enum('yes','no') DEFAULT 'no',
  `mobile_home_design` varchar(50) DEFAULT '',
  `mobile_home_format` enum('4','6','8') DEFAULT '4',
  `mobile_home_order` int(11) DEFAULT 0,
  `mobile_bg_color` varchar(20) DEFAULT '',
  `mobile_bg_image` varchar(255) DEFAULT '',
  `mobile_image` varchar(255) DEFAULT '',
  `meta_title` varchar(255) DEFAULT '',
  `meta_keywords` text DEFAULT NULL,
  `meta_description` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `parent_id` (`parent_id`),
  KEY `status` (`status`),
  KEY `display_order` (`display_order`),
  FULLTEXT KEY `ft_categories_search` (`name`,`description`)
) ENGINE=InnoDB AUTO_INCREMENT=40 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categories`
--

LOCK TABLES `categories` WRITE;
/*!40000 ALTER TABLE `categories` DISABLE KEYS */;
INSERT INTO `categories` VALUES (2,'nike tom21`121211','book-2002','this is second testing12121',3,'uploads/category/category_691c1a1ca09e16.04677975.jpeg','','active',5,1,1,'2025-11-14 10:23:53','2025-11-18 07:02:52','show',0,'no','','','','no','',0,'','','','show',0,'no','',0,'no','','4',0,'','','','',NULL,NULL),(3,'Men Cloths','men-cloths','Men Cloths this category',0,'uploads/category/category_691709a278ee47.26679373.webp','','active',4,0,0,'2025-11-14 10:51:14','2026-07-22 10:43:39','show',0,'no','','','','no','',0,'','','','show',0,'no','',0,'no','','4',0,'','','','',NULL,NULL),(4,'Jeans','jeans','this is jeans',0,'uploads/category/category_691709f25accc2.04192294.webp','','active',14,0,0,'2025-11-14 10:52:34','2025-11-22 01:34:03','show',0,'no','','','','no','',0,'','','','show',0,'no','',0,'no','','4',0,'','','','',NULL,NULL),(5,'T shirt','t-shirt','t shirt sub category',3,'uploads/category/category_69170a29316805.15496560.webp','','active',4,0,1,'2025-11-14 10:53:29','2025-11-14 10:53:29','show',0,'no','','','','no','',0,'','','','show',0,'no','',0,'no','','4',0,'','','','',NULL,NULL),(6,'Gift','gift','',0,'uploads/category/category_6920396018ce25.11021956.jpg','','active',2,1,0,'2025-11-17 05:27:45','2025-11-21 10:05:20','show',0,'no','','','','no','',0,'','','','show',0,'no','',0,'no','','4',0,'','','','',NULL,NULL),(7,'Mobiles','mobiles','<br />\r\n<b>Deprecated</b>:  htmlspecialchars(): Passing null to parameter #1 ($string) of type string is deprecated in <b>/home/u427250797/domains/printmont.com/public_html/edit-category.php</b> on line <b>206</b><br />',1,'uploads/category/category_691ae97eaf90c6.44940904.jpg','<br /><b>Deprecated</b>:  htmlspecialchars(): Passing null to parameter #1 ($string) of type string ','active',1,1,1,'2025-11-17 05:27:45','2025-11-17 09:23:10','show',0,'no','','','','no','',0,'','','','show',0,'no','',0,'no','','4',0,'','','','',NULL,NULL),(8,'Laptops','laptops','this is laptopt description',1,'uploads/category/category_691ae99e36a613.46859017.jpg','<br /><b>Deprecated</b>:  htmlspecialchars(): Passing null to parameter #1 ($string) of type string ','active',2,1,1,'2025-11-17 05:27:45','2025-11-17 09:23:42','show',0,'no','','','','no','',0,'','','','show',0,'no','',0,'no','','4',0,'','','','',NULL,NULL),(9,'Stationary','stationary','this is level 1',0,'uploads/category/category_692039861e3608.28684900.jpg','','active',3,1,0,'2025-11-17 05:27:45','2025-11-21 10:05:58','show',0,'no','','','','no','',0,'','','','show',0,'no','',0,'no','','4',0,'','','','',NULL,NULL),(10,'Men','men-fashion','men jeans',4,'uploads/category/category_692109eee1a7f6.24021212.jpg','','active',4,1,1,'2025-11-17 05:27:45','2025-11-22 01:04:57','show',0,'no','','','','no','',0,'','','','show',0,'no','',0,'no','','4',0,'','','','',NULL,NULL),(11,'Women','women-fashion','',4,'uploads/category/category_69210c1f543726.41869893.jpg','','active',2,1,1,'2025-11-17 05:27:45','2025-11-22 01:04:31','show',0,'no','','','','no','',0,'','','','show',0,'no','',0,'no','','4',0,'','','','',NULL,NULL),(13,'Electronic','electronic','this is electronics',0,'uploads/category/category_691ae4f2c5fe44.99532330.jpeg','','inactive',1,1,0,'2025-11-17 09:03:46','2026-07-22 10:46:28','show',0,'no','','','','no','',0,'','','','show',0,'no','',0,'no','','4',0,'','','','',NULL,NULL),(14,'Electronics','electronics','Latest electronic gadgets',0,'uploads/category/category_692108b2730e16.39792862.png','','active',1,1,0,'2025-11-22 00:25:48','2025-11-22 00:49:54','show',0,'no','','','','no','',0,'','','','show',0,'no','',0,'no','','4',0,'','','','',NULL,NULL),(15,'Fashion','fashion','Trending fashion items',0,'uploads/category/category_6921099b5e60f2.09811957.jpg','','active',6,1,0,'2025-11-22 00:25:48','2025-11-22 00:53:47','show',0,'no','','','','no','',0,'','','','show',0,'no','',0,'no','','4',0,'','','','',NULL,NULL),(16,'Home & Decor','home-decor','Home decoration items',0,'uploads/category/category_69210b266e5f42.23358794.jpg','','active',7,1,0,'2025-11-22 00:25:48','2025-11-22 01:00:22','show',0,'no','','','','no','',0,'','','','show',0,'no','',0,'no','','4',0,'','','','',NULL,NULL),(17,'Mobile Phones','mobile','Smartphones and accessories',14,'uploads/category/category_691ae97eaf90c6.44940904.jpg',NULL,'active',1,0,1,'2025-11-22 00:25:48','2026-06-06 19:16:42','show',0,'no','','','','no','',0,'','','','show',0,'no','',0,'no','','4',0,'','','','',NULL,NULL),(18,'Laptops','laptop','Laptops and notebooks',14,'uploads/category/category_691ae99e36a613.46859017.jpg',NULL,'active',2,0,1,'2025-11-22 00:25:48','2026-06-06 19:16:42','show',0,'no','','','','no','',0,'','','','show',0,'no','',0,'no','','4',0,'','','','',NULL,NULL),(19,'Earbuds','buds','Wireless earbuds',14,'uploads/category/category_692110c7cc1605.86841159.jpg',NULL,'active',3,0,1,'2025-11-22 00:25:48','2026-06-06 19:16:42','show',0,'no','','','','no','',0,'','','','show',0,'no','',0,'no','','4',0,'','','','',NULL,NULL),(20,'Men Clothing','men-clothing','Men fashion clothing',0,'uploads/category/category_692107f3773712.18082784.jpg','','active',1,0,0,'2025-11-22 00:25:48','2026-07-22 10:48:06','hide',0,'no','','','','no','',0,'','','','show',0,'no','',0,'no','','4',0,'','','','',NULL,NULL),(21,'Women Clothing','women-clothing','Women fashion clothing',0,'uploads/category/category_692109237a85a4.09523885.webp','','active',1,0,0,'2025-11-22 00:25:48','2026-07-22 10:47:54','hide',0,'no','','','','no','',0,'','','','show',0,'no','',0,'no','','4',0,'','','','',NULL,NULL),(22,'Kids','kids','Kids clothing and accessories',15,'uploads/category/category_69210d1433a814.11058416.webp','','active',10,0,1,'2025-11-22 00:25:48','2025-11-22 01:21:06','show',0,'no','','','','no','',0,'','','','show',0,'no','',0,'no','','4',0,'','','','',NULL,NULL),(23,'Men Shirts','men-shirts','Formal and casual shirts',7,'uploads/category/category_69210ca43143f1.87369824.jpg',NULL,'active',1,0,2,'2025-11-22 00:25:48','2026-06-06 19:16:42','show',0,'no','','','','no','',0,'','','','show',0,'no','',0,'no','','4',0,'','','','',NULL,NULL),(24,'Men Pants','men-pants','Jeans and trousers',7,'uploads/category/category_691709f25accc2.04192294.webp',NULL,'active',2,0,2,'2025-11-22 00:25:48','2026-06-06 19:16:42','show',0,'no','','','','no','',0,'','','','show',0,'no','',0,'no','','4',0,'','','','',NULL,NULL),(25,'Women Dresses','women-dresses','Women dresses and gowns',8,'uploads/category/category_692109237a85a4.09523885.webp',NULL,'active',1,0,2,'2025-11-22 00:25:48','2026-06-06 19:16:42','show',0,'no','','','','no','',0,'','','','show',0,'no','',0,'no','','4',0,'','','','',NULL,NULL),(26,'Women Tops','women-tops','Women tops and blouses',8,'uploads/category/category_692109237a85a4.09523885.webp',NULL,'active',2,0,2,'2025-11-22 00:25:48','2026-06-06 19:16:42','show',0,'no','','','','no','',0,'','','','show',0,'no','',0,'no','','4',0,'','','','',NULL,NULL),(27,'Table & Dinnerware','table-dinnerware','Table setting items',0,'uploads/category/category_692109675ae001.88627283.jpg','','active',5,0,0,'2025-11-22 00:25:48','2025-11-22 00:52:55','show',0,'no','','','','no','',0,'','','','show',0,'no','',0,'no','','4',0,'','','','',NULL,NULL),(28,'Home Decor Items','home-decor-items','Home decoration pieces',3,'uploads/category/category_69210b266e5f42.23358794.jpg',NULL,'active',2,0,1,'2025-11-22 00:25:48','2026-06-06 19:16:42','show',0,'no','','','','no','',0,'','','','show',0,'no','',0,'no','','4',0,'','','','',NULL,NULL),(29,'Women Outfit','women-outfit','Complete women outfits',0,'uploads/category/category_692109237a85a4.09523885.webp',NULL,'active',3,1,2,'2025-11-22 00:25:48','2026-07-22 10:43:37','show',0,'no','','','','no','',0,'','','','show',0,'no','',0,'no','','4',0,'','','','',NULL,NULL),(33,'Men Jeans2','men-jeans2','this men jeans 2',4,'uploads/category/category_69210bacdaf6f0.37502848.webp','','active',0,0,1,'2025-11-22 01:02:36','2025-11-22 01:03:55','show',0,'no','','','','no','',0,'','','','show',0,'no','',0,'no','','4',0,'','','','',NULL,NULL),(34,'T Shirts','t-shirts','this is t shirts',15,'uploads/category/category_69210ca43143f1.87369824.jpg','','active',0,0,1,'2025-11-22 01:06:44','2025-11-22 01:06:44','show',0,'no','','','','no','',0,'','','','show',0,'no','',0,'no','','4',0,'','','','',NULL,NULL),(36,'Mobile','mobile-1','this is mobile',0,'uploads/category/category_6921106de31c78.22006466.webp','','active',11,0,0,'2025-11-22 01:22:53','2025-11-22 01:22:53','show',0,'no','','','','no','',0,'','','','show',0,'no','',0,'no','','4',0,'','','','',NULL,NULL),(37,'Laptop','laptop1','this is laptop',0,'uploads/category/category_692110a7e575d4.89162286.jpg','','active',12,0,0,'2025-11-22 01:23:51','2025-11-22 01:23:51','show',0,'no','','','','no','',0,'','','','show',0,'no','',0,'no','','4',0,'','','','',NULL,NULL),(38,'Buds','buds1','this is buds',0,'uploads/category/category_692110c7cc1605.86841159.jpg','','active',13,0,0,'2025-11-22 01:24:23','2025-11-22 01:24:23','show',0,'no','','','','no','',0,'','','','show',0,'no','',0,'no','','4',0,'','','','',NULL,NULL);
/*!40000 ALTER TABLE `categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `brand` varchar(100) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `discount_price` decimal(10,2) DEFAULT NULL,
  `stock_quantity` int(11) DEFAULT 0,
  `sku` varchar(100) DEFAULT NULL,
  `status` enum('active','inactive','draft','out_of_stock') NOT NULL DEFAULT 'active',
  `featured` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `top_selection` tinyint(1) DEFAULT 0,
  `our_bestseller` tinyint(1) DEFAULT 0,
  `top_rated` tinyint(11) DEFAULT 0,
  `top_deal_by_categories` tinyint(1) DEFAULT NULL,
  `view_count` int(11) DEFAULT 0,
  `product_slug` varchar(255) NOT NULL,
  `product_quantity` int(11) DEFAULT 0,
  `regular_price` decimal(10,2) NOT NULL,
  `offer_price` decimal(10,2) DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `out_of_stock_status` enum('in_stock','out_of_stock','pre_order') DEFAULT 'in_stock',
  `minimum_quantity` int(11) DEFAULT 1,
  `bulk_price_variance` longtext DEFAULT NULL,
  `long_description` longtext DEFAULT NULL,
  `instructions` longtext DEFAULT NULL,
  `delivery_info` longtext DEFAULT NULL,
  `show_quantity` tinyint(1) DEFAULT 0,
  `show_help_button` tinyint(1) DEFAULT 0,
  `show_bulk_form` tinyint(1) DEFAULT 0,
  `show_customization_label` tinyint(1) DEFAULT 0,
  `customization_fields` longtext DEFAULT NULL,
  `addon_product_ids` longtext DEFAULT NULL,
  `show_sizes` tinyint(1) DEFAULT 0,
  `color_attributes` longtext DEFAULT NULL,
  `color_images` longtext DEFAULT NULL,
  `size_attributes` longtext DEFAULT NULL,
  `material_attributes` longtext DEFAULT NULL,
  `lamination_attributes` longtext DEFAULT NULL,
  `orientation_attributes` longtext DEFAULT NULL,
  `printing_location` longtext DEFAULT NULL,
  `quantity_price_breaks` longtext DEFAULT NULL,
  `shipping_method_status` tinyint(1) DEFAULT 0,
  `regional_shipping_charge` decimal(10,2) DEFAULT 0.00,
  `national_shipping_charge` decimal(10,2) DEFAULT 0.00,
  `local_shipping_charge` decimal(10,2) DEFAULT 0.00,
  `regional_shipping_message` varchar(255) DEFAULT '',
  `national_shipping_message` varchar(255) DEFAULT '',
  `cancel_status` enum('yes','no') DEFAULT 'yes',
  `cancel_type` varchar(10) NOT NULL DEFAULT 'hours',
  `cancel_time` int(11) DEFAULT 24,
  `cod_status` enum('yes','no') DEFAULT 'yes',
  `meta_title` varchar(255) DEFAULT '',
  `meta_keywords` text DEFAULT NULL,
  `meta_description` text DEFAULT NULL,
  `alt_tag` varchar(255) DEFAULT '',
  `top_deal` tinyint(1) DEFAULT 0,
  `sub_category_id` int(11) DEFAULT NULL COMMENT 'FK categories.id, level 2',
  `sub_sub_category_id` int(11) DEFAULT NULL COMMENT 'FK categories.id, level 3',
  `homepage_category_id` int(11) DEFAULT NULL COMMENT 'FK categories.id, homepage grouping',
  `homepage_carousel_id` int(11) DEFAULT NULL,
  `additional_category_ids` text DEFAULT NULL COMMENT 'JSON array of extra category ids',
  `additional_sub_category_ids` text DEFAULT NULL COMMENT 'JSON array of extra sub-category ids',
  `additional_sub_sub_category_ids` text DEFAULT NULL COMMENT 'JSON array of extra sub-sub-category ids',
  `thumbnail_image` varchar(255) DEFAULT NULL,
  `gallery_images` longtext DEFAULT NULL COMMENT 'JSON array of gallery image paths',
  `color` varchar(100) DEFAULT NULL COMMENT 'Filter/tag value, distinct from color_attributes',
  `type` varchar(100) DEFAULT NULL,
  `material` varchar(100) DEFAULT NULL COMMENT 'Filter/tag value, distinct from material_attributes',
  `occasion` varchar(100) DEFAULT NULL,
  `discount_type` varchar(50) DEFAULT NULL,
  `shape` varchar(100) DEFAULT NULL,
  `gender` varchar(20) DEFAULT NULL,
  `gift_type` varchar(100) DEFAULT NULL,
  `ideal_for` varchar(100) DEFAULT NULL,
  `customization_tech` varchar(100) DEFAULT NULL,
  `customization_location` varchar(100) DEFAULT NULL,
  `capacity` varchar(100) DEFAULT NULL,
  `ink_color` varchar(100) DEFAULT NULL,
  `features` text DEFAULT NULL,
  `help_button_text` varchar(255) DEFAULT 'Need Help?',
  `help_button_link` varchar(255) DEFAULT '/contact-us',
  `customization_label` varchar(255) DEFAULT NULL,
  `show_colors` tinyint(1) NOT NULL DEFAULT 0,
  `local_shipping_message` varchar(255) DEFAULT '',
  `cancel_available` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Replaces cancel_status enum',
  `cod_available` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Replaces cod_status enum',
  PRIMARY KEY (`id`),
  UNIQUE KEY `sku` (`sku`),
  KEY `products_ibfk_1` (`category_id`),
  FULLTEXT KEY `ft_products_search` (`name`,`sku`,`brand`,`description`),
  CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=229 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `products`
--

LOCK TABLES `products` WRITE;
/*!40000 ALTER TABLE `products` DISABLE KEYS */;
INSERT INTO `products` VALUES (2,'T shirt','this is testing 2',20,'NIKE 1001',599.00,799.00,100,'t45ssdf','active',1,'2025-11-08 06:43:49','2026-08-08 02:49:07',1,0,NULL,NULL,0,'',0,0.00,NULL,0,'in_stock',1,NULL,'<p>Crafted from 100% combed cotton, this custom printed polo t-shirt is designed for maximum comfort and style. Upload your logo or add custom text to personalize for your team, event, or brand.</p>','<p>Machine wash cold with like colors. Do not iron directly on print. Tumble dry low.</p>','<p>Custom printed products ship within 2-3 business days. Delivery in 3-5 days across India.</p>',0,0,0,1,'[{\"label\":\"Chest Logo Upload\",\"type\":\"file\",\"required\":true},{\"label\":\"Custom Name \\/ Text\",\"type\":\"text\",\"required\":false}]','[101]',1,'[\"White\",\"Black\",\"Navy Blue\",\"Rust Brown\"]',NULL,'[\"S\",\"M\",\"L\",\"XL\",\"XXL\"]','[\"100% Cotton 180gsm\",\"Cotton Blend 200gsm\",\"Polyester 160gsm\"]',NULL,NULL,NULL,NULL,1,50.00,99.00,0.00,'','','yes','hours',24,'yes','Custom Printed Men Polo T-Shirt - Printmont',NULL,'Design your own custom printed t-shirt with premium cotton fabric.','',0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Need Help?','/contact-us',NULL,0,'',1,1),(4,'Levis Jeans','this is jeans of levis',20,'NIKE22',1299.00,1599.00,100,'WER45WE33','active',1,'2025-11-08 06:51:28','2026-08-08 02:49:07',0,0,0,0,0,'',0,0.00,NULL,0,'in_stock',1,NULL,NULL,NULL,NULL,0,0,0,0,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0.00,0.00,0.00,'','','yes','hours',24,'yes','',NULL,NULL,'',0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Need Help?','/contact-us',NULL,0,'',1,1),(101,'Wireless Bluetooth Headphones','High-quality wireless headphones with noise cancellation',13,'boat',149.97,NULL,50,'WH-1000XM4','active',0,'2025-11-14 11:38:21','2026-08-08 02:49:07',1,1,NULL,NULL,0,'',0,0.00,NULL,0,'in_stock',1,NULL,'<p>Experience immersive audio with our premium <strong>Wireless Bluetooth Headphones</strong>. Designed for comfort and high-fidelity sound, these headphones feature state-of-the-art active noise cancellation and up to 30 hours of continuous playback.</p><ul><li>40mm Dynamic Drivers for Deep Bass</li><li>Active Noise Cancellation (ANC)</li><li>Bluetooth 5.2 Connectivity</li><li>Multi-point Dual Device Connection</li></ul>','<p><strong>Care & Maintenance:</strong></p><ul><li>Keep away from extreme moisture or liquids.</li><li>Clean earpads with a soft dry microfiber cloth.</li><li>Store in the provided carrying case when not in use.</li></ul>','<p><strong>Express Delivery Available:</strong></p><ul><li>Standard Delivery: 3-5 Business Days</li><li>Express Shipping: 1-2 Business Days</li><li>Easy 24-Hour Cancellation Policy</li><li>Cash on Delivery (COD) Available</li></ul>',0,0,0,0,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,49.00,79.00,0.00,'Free local shipping over ₹499','Standard national delivery in 3-5 days','yes','hours',24,'yes','Premium Wireless Bluetooth Headphones - Active Noise Cancelling','headphones, wireless headphones, bluetooth, noise cancelling, audio','Buy Premium Wireless Bluetooth Headphones at Printmont. Features 30-hour battery life, active noise cancellation, and crystal clear sound.','',0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Need Help?','/contact-us',NULL,0,'',1,1),(102,'Smart Fitness Watch','Advanced fitness tracking watch with heart rate monitor',13,'apple',89.98,NULL,75,'SFW-2024','active',0,'2025-11-14 11:38:21','2026-08-08 02:49:07',0,0,0,1,0,'',0,0.00,NULL,0,'in_stock',1,NULL,NULL,NULL,NULL,0,0,0,0,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0.00,0.00,0.00,'','','yes','hours',24,'yes','',NULL,NULL,'',0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Need Help?','/contact-us',NULL,0,'',1,1),(103,'Professional Camera Lens','Professional portrait lens for DSLR cameras',13,'Nikon',249.50,NULL,20,'PCL-85MM','active',0,'2025-11-14 11:38:21','2026-08-08 02:49:07',0,1,NULL,NULL,0,'',0,0.00,NULL,0,'in_stock',1,NULL,NULL,NULL,NULL,0,0,0,0,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0.00,0.00,0.00,'','','yes','hours',24,'yes','',NULL,NULL,'',0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Need Help?','/contact-us',NULL,0,'',1,1),(104,'Phone Case & Screen Protector','Protective case and screen protector combo',13,'Iphone',45.99,NULL,100,'PCSP-GALAXY','active',0,'2025-11-14 11:38:21','2026-08-08 02:49:07',0,0,1,NULL,0,'',0,0.00,NULL,0,'in_stock',1,NULL,NULL,NULL,NULL,0,0,0,0,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0.00,0.00,0.00,'','','yes','hours',24,'yes','',NULL,NULL,'',0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Need Help?','/contact-us',NULL,0,'',1,1),(105,'Gaming Keyboard Mechanical','RGB mechanical keyboard for gaming enthusiasts',13,'dell',199.99,249.00,30,'GKM-PRO','active',0,'2025-11-14 11:38:21','2026-08-08 02:49:07',1,1,NULL,NULL,0,'',0,0.00,NULL,0,'in_stock',1,NULL,NULL,NULL,NULL,0,0,0,0,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0.00,0.00,0.00,'','','yes','hours',24,'yes','',NULL,NULL,'',0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Need Help?','/contact-us',NULL,0,'',1,1),(106,'Wireless Mouse','Ergonomic wireless mouse with precision tracking',13,'dell',79.97,99.00,60,'WM-ERGONOMIC','active',0,'2025-11-14 11:38:21','2026-08-08 02:49:07',0,1,NULL,NULL,0,'',0,0.00,NULL,0,'in_stock',1,NULL,NULL,NULL,NULL,0,0,0,0,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0.00,0.00,0.00,'','','yes','hours',24,'yes','',NULL,NULL,'',0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Need Help?','/contact-us',NULL,0,'',1,1),(107,'External SSD 1TB','High-speed external solid state drive',13,'sendisk',349.95,499.00,25,'ESSD-1TB','active',0,'2025-11-14 11:38:21','2026-08-08 02:49:07',0,0,NULL,NULL,0,'',0,0.00,NULL,0,'in_stock',1,NULL,NULL,NULL,NULL,0,0,0,0,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0.00,0.00,0.00,'','','yes','hours',24,'yes','',NULL,NULL,'',0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Need Help?','/contact-us',NULL,0,'',1,1),(108,'Noise Cancelling Earbuds','Wireless earbuds with active noise cancellation',19,'boat',129.99,549.00,40,'NCE-BUDSPRO','active',0,'2025-11-14 11:38:21','2026-08-08 02:49:07',1,1,1,NULL,0,'',0,0.00,NULL,0,'in_stock',1,NULL,NULL,NULL,NULL,0,0,0,0,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0.00,0.00,0.00,'','','yes','hours',24,'yes','',NULL,NULL,'',0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Need Help?','/contact-us',NULL,0,'',1,1),(109,'Phone Charging Cable Set','Multi-device charging cable set',17,'lg',29.99,49.00,200,'PCC-3IN1','active',0,'2025-11-14 11:38:21','2026-08-08 02:49:07',1,1,1,NULL,0,'',0,0.00,NULL,0,'in_stock',1,NULL,NULL,NULL,NULL,0,0,0,0,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0.00,0.00,0.00,'','','yes','hours',24,'yes','',NULL,NULL,'',0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Need Help?','/contact-us',NULL,0,'',1,1),(110,'Laptop Stand Aluminum','Adjustable aluminum laptop stand',18,'<br /><b>Deprecated</b>:  htmlspecialchars(): Passing null to parameter #1 ($string) of type string ',179.96,NULL,35,'LSA-PRO','active',0,'2025-11-14 11:38:21','2026-08-08 02:49:07',0,1,NULL,NULL,0,'',0,0.00,NULL,0,'in_stock',1,NULL,NULL,NULL,NULL,0,0,0,0,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0.00,0.00,0.00,'','','yes','hours',24,'yes','',NULL,NULL,'',0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Need Help?','/contact-us',NULL,0,'',1,1),(211,'iPhone 15 Pro','Latest Apple smartphone',17,'Apple',999.99,899.99,50,'IP15PRO-001','active',1,'2025-11-22 00:33:16','2026-08-08 02:49:07',0,0,0,0,120,'',0,0.00,NULL,0,'in_stock',1,NULL,NULL,NULL,NULL,0,0,0,0,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0.00,0.00,0.00,'','','yes','hours',24,'yes','',NULL,NULL,'',0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Need Help?','/contact-us',NULL,0,'',1,1),(212,'Samsung Galaxy S24','Android flagship phone',17,'Samsung',849.99,799.99,75,'SGS24-001','active',1,'2025-11-22 00:33:16','2026-08-08 02:49:07',0,0,0,0,300,'',0,0.00,NULL,0,'in_stock',1,NULL,NULL,NULL,NULL,0,0,0,0,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0.00,0.00,0.00,'','','yes','hours',24,'yes','',NULL,NULL,'',0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Need Help?','/contact-us',NULL,0,'',1,1),(213,'MacBook Air M2','Lightweight powerful laptop',18,'Apple',1199.99,1099.99,30,'MBA-M2-001','active',1,'2025-11-22 00:33:16','2026-08-08 02:49:07',0,0,0,0,95,'',0,0.00,NULL,0,'in_stock',1,NULL,NULL,NULL,NULL,0,0,0,0,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0.00,0.00,0.00,'','','yes','hours',24,'yes','',NULL,NULL,'',0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Need Help?','/contact-us',NULL,0,'',1,1),(214,'Gaming Laptop RTX','High-performance gaming',18,'GamePro',1599.99,NULL,25,'GL-RTX-001','active',0,'2025-11-22 00:33:16','2026-08-08 02:49:07',0,0,0,0,180,'',0,0.00,NULL,0,'in_stock',1,NULL,NULL,NULL,NULL,0,0,0,0,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0.00,0.00,0.00,'','','yes','hours',24,'yes','',NULL,NULL,'',0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Need Help?','/contact-us',NULL,0,'',1,1),(215,'Wireless Earbuds Pro','Noise cancellation buds',19,'SoundMax',199.99,149.99,100,'WEB-PRO-001','active',0,'2025-11-22 00:33:16','2026-08-08 02:49:07',0,0,0,0,200,'',0,0.00,NULL,0,'in_stock',1,NULL,NULL,NULL,NULL,0,0,0,0,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0.00,0.00,0.00,'','','yes','hours',24,'yes','',NULL,NULL,'',0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Need Help?','/contact-us',NULL,0,'',1,1),(216,'Bluetooth Speaker','Portable wireless speaker',19,'Ptronics',79.99,59.99,90,'BS-001','active',0,'2025-11-22 00:33:16','2026-08-08 02:49:07',0,0,0,0,167,'',0,0.00,NULL,0,'in_stock',1,NULL,NULL,NULL,NULL,0,0,0,0,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0.00,0.00,0.00,'','','yes','hours',24,'yes','',NULL,NULL,'',0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Need Help?','/contact-us',NULL,0,'',1,1),(217,'Men Formal Shirt','Premium cotton shirt',20,'Classic Wear',59.99,49.99,200,'MFS-001','active',0,'2025-11-22 00:33:16','2026-08-08 02:49:07',0,0,0,0,150,'',0,0.00,NULL,0,'in_stock',1,NULL,NULL,NULL,NULL,0,0,0,0,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0.00,0.00,0.00,'','','yes','hours',24,'yes','',NULL,NULL,'',0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Need Help?','/contact-us',NULL,0,'',1,1),(218,'Men Jeans','Comfortable denim jeans',20,'Denim Co',79.99,59.99,120,'MJ-001','active',0,'2025-11-22 00:33:16','2026-08-08 02:49:07',0,0,0,0,98,'',0,0.00,NULL,0,'in_stock',1,NULL,NULL,NULL,NULL,0,0,0,0,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0.00,0.00,0.00,'','','yes','hours',24,'yes','',NULL,NULL,'',0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Need Help?','/contact-us',NULL,0,'',1,1),(219,'Designer Evening Gown','Elegant evening dress',21,'Fashion House',299.99,249.99,15,'DEG-001','active',1,'2025-11-22 00:33:16','2026-08-08 02:49:07',1,0,0,1,78,'',0,0.00,NULL,0,'in_stock',1,NULL,NULL,NULL,NULL,0,0,0,0,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0.00,0.00,0.00,'','','yes','hours',24,'yes','',NULL,NULL,'',0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Need Help?','/contact-us',NULL,0,'',1,1),(220,'Women Casual Top','<p>Comfortable daily wear</p>',21,'Fashion Daily',0.00,25.99,300,'WCT-001','active',0,'2025-11-22 00:33:16','2026-08-07 23:26:41',0,1,0,1,134,'women-casual -top',300,0.00,25.99,0,'in_stock',1,'','','','',1,0,0,0,NULL,'\"[]\"',0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0.00,0.00,0.00,'<br /><b>Warning</b>:  Undefined array key','<br /><b>Warning</b>:  Undefined array key','yes','hours',24,'yes','','','','',0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'uploads/products/thumbnail/product_thumbnail_6a769ae91b785.png','[\"uploads\\/products\\/gallery\\/product_gallery_6a769ae91c04c.png\",\"uploads\\/products\\/gallery\\/product_gallery_6a769ae91c85d.png\",\"uploads\\/products\\/gallery\\/product_gallery_6a769ae91d0e2.png\",\"uploads\\/products\\/gallery\\/product_gallery_6a769ae91d942.png\",\"uploads\\/products\\/gallery\\/product_gallery_6a769ae91df67.png\"]','<br /><b>Warning</b>:  Undefined array key','<br /><b>Warning</b>:  Undefined array key','<br /><b>Warning</b>:  Undefined array key','<br /><b>Warning</b>:  Undefined array key','','<br /><b>Warning</b>:  Undefined array key','','<br /><b>Warning</b>:  Undefined array key','<br /><b>Warning</b>:  Undefined array key','<br /><b>Warning</b>:  Undefined array key','<br /><b>Warning</b>:  Undefined array key','<br /><b>Warning</b>:  Undefined array key','<br /><b>Warning</b>:  Undefined array key','<br />\r\n<b>Warning</b>:  Undefined array key \"features\" in <b>D:\\xampp\\htdocs\\printmont\\edit-product.php</b> on line <b>363</b><br />','Need Help?','/contact-us','<br /><b>Warning</b>:  Undefined array key',0,'<br /><b>Warning</b>:  Undefined array key',1,1),(221,'Kids Summer Outfit','Comfortable summer clothes',22,'Kids Comfort',39.99,29.99,150,'KSO-001','active',0,'2025-11-22 00:33:16','2026-08-08 02:49:07',0,0,0,0,67,'',0,0.00,NULL,0,'in_stock',1,NULL,NULL,NULL,NULL,0,0,0,0,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0.00,0.00,0.00,'','','yes','hours',24,'yes','',NULL,NULL,'',0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Need Help?','/contact-us',NULL,0,'',1,1),(222,'Kids Shoes','Comfortable sports shoes',NULL,'Kids Footwear',49.99,NULL,80,'KS-001','active',0,'2025-11-22 00:33:16','2026-08-08 02:49:07',0,0,0,0,33,'',0,0.00,NULL,0,'in_stock',1,NULL,NULL,NULL,NULL,0,0,0,0,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0.00,0.00,0.00,'','','yes','hours',24,'yes','',NULL,NULL,'',0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Need Help?','/contact-us',NULL,0,'',1,1),(223,'Ceramic Dinner Set','Elegant 12-piece set',NULL,'Home Elegance',129.99,99.99,40,'CDS-001','active',1,'2025-11-22 00:33:16','2026-08-08 02:49:07',0,0,1,0,89,'',0,0.00,NULL,0,'in_stock',1,NULL,NULL,NULL,NULL,0,0,0,0,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0.00,0.00,0.00,'','','yes','hours',24,'yes','',NULL,NULL,'',0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Need Help?','/contact-us',NULL,0,'',1,1),(224,'Home Decor Vase','Modern decorative vase',16,'Decor Art',45.99,35.99,60,'HDV-001','active',0,'2025-11-22 00:33:16','2026-08-08 02:49:07',0,0,0,0,76,'',0,0.00,NULL,0,'in_stock',1,NULL,NULL,NULL,NULL,0,0,0,0,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0.00,0.00,0.00,'','','yes','hours',24,'yes','',NULL,NULL,'',0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Need Help?','/contact-us',NULL,0,'',1,1),(225,'Table Lamp','Modern LED table lamp',27,'Light Home',39.99,29.99,70,'TL-001','active',0,'2025-11-22 00:33:16','2026-08-08 02:49:07',0,0,0,0,54,'',0,0.00,NULL,0,'in_stock',1,NULL,NULL,NULL,NULL,0,0,0,0,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0.00,0.00,0.00,'','','yes','hours',24,'yes','',NULL,NULL,'',0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Need Help?','/contact-us',NULL,0,'',1,1),(227,'Top','this is women top',21,'NIKE22',286.00,249.00,100,'WER45WE83','active',0,'2025-11-22 01:12:03','2026-08-08 02:49:07',0,0,0,0,0,'',0,0.00,NULL,0,'in_stock',1,NULL,NULL,NULL,NULL,0,0,0,0,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0.00,0.00,0.00,'','','yes','hours',24,'yes','',NULL,NULL,'',0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Need Help?','/contact-us',NULL,0,'',1,1),(228,'Floral Print Summer Dress','Beautiful lightweight summer dress with floral patterns.',21,NULL,89.99,79.99,50,'FLORAL-DRESS-01','active',1,'2026-06-06 19:37:34','2026-08-08 02:49:07',0,0,0,NULL,0,'',0,0.00,NULL,0,'in_stock',1,NULL,NULL,NULL,NULL,0,0,0,0,NULL,NULL,0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0.00,0.00,0.00,'','','yes','hours',24,'yes','',NULL,NULL,'',0,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Need Help?','/contact-us',NULL,0,'',1,1);
/*!40000 ALTER TABLE `products` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_images`
--

DROP TABLE IF EXISTS `product_images`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `product_images` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) DEFAULT NULL,
  `image_url` varchar(255) NOT NULL,
  `is_primary` tinyint(1) DEFAULT 0,
  `display_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `image_type` enum('thumbnail','main','gallery') DEFAULT 'gallery',
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `product_images_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=50 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_images`
--

LOCK TABLES `product_images` WRITE;
/*!40000 ALTER TABLE `product_images` DISABLE KEYS */;
INSERT INTO `product_images` VALUES (4,2,'uploads/products/690ee7078e3aa_tshirt1.webp',1,0,'2025-11-08 06:45:27','gallery'),(5,4,'uploads/products/690ee87077a8e_jeands1.webp',1,0,'2025-11-08 06:51:28','gallery'),(6,110,'uploads/products/691ae33eb7786_dell-laptops.jpg',1,0,'2025-11-17 08:56:30','gallery'),(7,101,'uploads/products/691ae533c3857_wireless.jpg',1,0,'2025-11-17 09:04:51','gallery'),(8,102,'uploads/products/691ae54facec8_pro24.jpeg',1,0,'2025-11-17 09:05:19','gallery'),(9,103,'uploads/products/691ae57bee05a_cameralense.webp',1,0,'2025-11-17 09:06:03','gallery'),(10,104,'uploads/products/691ae5a2f18f6_phonecase.jpg',1,0,'2025-11-17 09:06:42','gallery'),(11,105,'uploads/products/691ae624b9dc1_gamingkeykoard1.jpg',1,0,'2025-11-17 09:08:52','gallery'),(12,106,'uploads/products/691ae66c8380e_mouse.webp',1,0,'2025-11-17 09:10:04','gallery'),(13,107,'uploads/products/691ae69c83606_-original-imahfh6mxzvhfyts.webp',1,0,'2025-11-17 09:10:52','gallery'),(14,108,'uploads/products/691ae6c9bfc99_earbud.webp',1,0,'2025-11-17 09:11:37','gallery'),(15,109,'uploads/products/691ae6ed1f4a6_61cbTykx9QL._AC_UF10001000_QL80_.jpg',1,0,'2025-11-17 09:12:13','gallery'),(32,227,'uploads/products/69210de354141_A44110013_01_Front_ebf96f7e-fcb9-4a25-84e0-c7f8629431fe.webp',1,0,'2025-11-22 01:12:03','gallery'),(33,221,'uploads/products/69210fc1859e6_WhatsAppImage2025-06-30at3.14.16PM.webp',1,0,'2025-11-22 01:20:01','gallery'),(34,211,'uploads/products/6921122c3488a_iphone-card-40-17pro-202509_FMT_WHH.jpg',1,0,'2025-11-22 01:30:20','gallery'),(35,212,'uploads/products/6921124c8ddfb_717Q2swzhBL._AC_UF10001000_QL80_.jpg',1,0,'2025-11-22 01:30:52','gallery'),(36,213,'uploads/products/6921126b1722a_0-768x768.jpg',1,0,'2025-11-22 01:31:23','gallery'),(37,214,'uploads/products/6921128ab5e5b_1546852826561.jpg',1,0,'2025-11-22 01:31:54','gallery'),(38,215,'uploads/products/692112a3bf9a0_download.jpg',1,0,'2025-11-22 01:32:19','gallery'),(39,216,'uploads/products/692112c48bd43_apollo-one-20w-bluetooth-portable-speaker-with-wireless-karaoke-original-imahgqsar5tbwebj.webp',1,0,'2025-11-22 01:32:52','gallery'),(40,217,'uploads/products/692112e074eba_xxl-mens-formal-casual-daily-wear-plain-shirt-with-colors-original-imah3bze5syrjfbw.webp',1,0,'2025-11-22 01:33:20','gallery'),(41,218,'uploads/products/6921145b02052_download.jpg',1,0,'2025-11-22 01:39:39','gallery'),(42,224,'uploads/products/69211e6fbb9df_71uDBhFuGqL._AC_UF8941000_QL80_.jpg',1,0,'2025-11-22 02:22:39','gallery'),(43,225,'uploads/products/69211e9855489_DEKO0457_1200x.webp',1,0,'2025-11-22 02:23:20','gallery'),(44,219,'uploads/products/69259c114dd7b_blue-dress.png',1,0,'2026-06-06 19:16:42','gallery'),(46,222,'uploads/products/69218946316d9_kid-1.jpeg',1,0,'2026-06-06 19:16:42','gallery'),(47,223,'uploads/products/692353925a769_table-3.jpeg',1,0,'2026-06-06 19:16:42','gallery'),(48,228,'uploads/products/69259c114dd7b_blue-dress.png',1,0,'2026-06-06 19:37:34','gallery'),(49,220,'uploads/products/69215749510c0_635bb553330e525f294c4cf7-plus-size-tops-casual-tops-for-ladies.jpg',1,0,'2026-08-08 02:56:41','gallery');
/*!40000 ALTER TABLE `product_images` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-08-09  8:18:39
