-- ============================================================
-- Migration 031: Restore Categories Document Specification Fields
-- Matches Google Doc: Manage Categories (Main, Sub, Sub-Sub)
-- Adds Desktop & Mobile Home Page display, Tag background color,
-- Sidebar status, and format controls
-- ============================================================

ALTER TABLE `categories`
  ADD COLUMN IF NOT EXISTS `desktop_menu_tag_bg`   VARCHAR(20)         NOT NULL DEFAULT ''   COMMENT 'Desktop top menu tag background color (#hex)',
  ADD COLUMN IF NOT EXISTS `desktop_home_show`     ENUM('yes','no')    NOT NULL DEFAULT 'no' COMMENT 'Show on desktop home page?',
  ADD COLUMN IF NOT EXISTS `desktop_home_design`   VARCHAR(50)         NOT NULL DEFAULT ''   COMMENT 'Desktop home design type (design1/design2/design3/design4)',
  ADD COLUMN IF NOT EXISTS `desktop_home_order`    INT(11)             NOT NULL DEFAULT 0    COMMENT 'Desktop home page sort order',
  ADD COLUMN IF NOT EXISTS `desktop_home_tag`      VARCHAR(255)        NOT NULL DEFAULT ''   COMMENT 'Show on home Category Text slug/slogan',
  ADD COLUMN IF NOT EXISTS `desktop_bg_color`      VARCHAR(20)         NOT NULL DEFAULT ''   COMMENT 'Desktop category background color (#hex)',
  ADD COLUMN IF NOT EXISTS `desktop_bg_image`      VARCHAR(255)        NOT NULL DEFAULT ''   COMMENT 'Desktop category background image path',
  ADD COLUMN IF NOT EXISTS `desktop_image`         VARCHAR(255)        NOT NULL DEFAULT ''   COMMENT 'Desktop category image (design-dependent)',

  ADD COLUMN IF NOT EXISTS `mobile_sidebar_status` ENUM('yes','no')    NOT NULL DEFAULT 'yes' COMMENT 'Mobile sidebar/drawer category show/hide',
  ADD COLUMN IF NOT EXISTS `mobile_home_show`      ENUM('yes','no')    NOT NULL DEFAULT 'no'  COMMENT 'Show on mobile home page?',
  ADD COLUMN IF NOT EXISTS `mobile_home_design`    VARCHAR(50)         NOT NULL DEFAULT ''    COMMENT 'Mobile home design type (design1/design2/design3/design4)',
  ADD COLUMN IF NOT EXISTS `mobile_home_format`    ENUM('4','6','8')   NOT NULL DEFAULT '4'   COMMENT 'Mobile home: 4/6/8 image product boxes',
  ADD COLUMN IF NOT EXISTS `mobile_home_order`     INT(11)             NOT NULL DEFAULT 0     COMMENT 'Mobile home page sort order',
  ADD COLUMN IF NOT EXISTS `mobile_bg_color`       VARCHAR(20)         NOT NULL DEFAULT ''    COMMENT 'Mobile category background color (#hex)',
  ADD COLUMN IF NOT EXISTS `mobile_bg_image`       VARCHAR(255)        NOT NULL DEFAULT ''    COMMENT 'Mobile category background image path',
  ADD COLUMN IF NOT EXISTS `mobile_image`          VARCHAR(255)        NOT NULL DEFAULT ''    COMMENT 'Mobile category image (design-dependent)';
