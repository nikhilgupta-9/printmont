-- ============================================================
-- Migration 002: Extended Category Fields
-- Run on: u950539402_print_bkend_db
-- Adds desktop/mobile menu, home page display, SEO, design fields
-- Safe to run multiple times (uses IF NOT EXISTS / ADD COLUMN IF NOT EXISTS)
-- ============================================================

ALTER TABLE `categories`

  -- ── DESKTOP TOP MENU ──────────────────────────────────────
  ADD COLUMN IF NOT EXISTS `desktop_menu_status`      ENUM('show','hide') NOT NULL DEFAULT 'show'    COMMENT 'Desktop top menu: show/hide',
  ADD COLUMN IF NOT EXISTS `desktop_menu_order`       INT(11)             NOT NULL DEFAULT 0          COMMENT 'Desktop top menu sort order',
  ADD COLUMN IF NOT EXISTS `desktop_menu_view`        ENUM('yes','no')    NOT NULL DEFAULT 'no'       COMMENT 'Show design view in desktop top menu?',
  ADD COLUMN IF NOT EXISTS `desktop_menu_design`      VARCHAR(50)         NOT NULL DEFAULT ''         COMMENT 'Desktop top menu design type key',
  ADD COLUMN IF NOT EXISTS `desktop_menu_tag`         VARCHAR(100)        NOT NULL DEFAULT ''         COMMENT 'Tag label shown in desktop menu (sub/sub-sub only)',
  ADD COLUMN IF NOT EXISTS `desktop_menu_image`       VARCHAR(255)        NOT NULL DEFAULT ''         COMMENT 'Image shown in desktop top menu dropdown',

  -- ── DESKTOP HOME PAGE ─────────────────────────────────────
  ADD COLUMN IF NOT EXISTS `desktop_home_show`        ENUM('yes','no')    NOT NULL DEFAULT 'no'       COMMENT 'Show on desktop home page?',
  ADD COLUMN IF NOT EXISTS `desktop_home_design`      VARCHAR(50)         NOT NULL DEFAULT ''         COMMENT 'Desktop home design type (design1/design2/design3/design4)',
  ADD COLUMN IF NOT EXISTS `desktop_home_order`       INT(11)             NOT NULL DEFAULT 0          COMMENT 'Desktop home page sort order',
  ADD COLUMN IF NOT EXISTS `desktop_bg_color`         VARCHAR(20)         NOT NULL DEFAULT ''         COMMENT 'Desktop category background color (#hex)',
  ADD COLUMN IF NOT EXISTS `desktop_bg_image`         VARCHAR(255)        NOT NULL DEFAULT ''         COMMENT 'Desktop category background image path',
  ADD COLUMN IF NOT EXISTS `desktop_image`            VARCHAR(255)        NOT NULL DEFAULT ''         COMMENT 'Desktop category image (design-dependent)',

  -- ── MOBILE TOP BAR ────────────────────────────────────────
  ADD COLUMN IF NOT EXISTS `mobile_topbar_status`     ENUM('show','hide') NOT NULL DEFAULT 'show'    COMMENT 'Mobile top bar: show/hide',
  ADD COLUMN IF NOT EXISTS `mobile_topbar_order`      INT(11)             NOT NULL DEFAULT 0          COMMENT 'Mobile top bar sort order',
  ADD COLUMN IF NOT EXISTS `mobile_menu_view`         ENUM('yes','no')    NOT NULL DEFAULT 'no'       COMMENT 'Show design view in mobile menu?',
  ADD COLUMN IF NOT EXISTS `mobile_menu_design`       VARCHAR(50)         NOT NULL DEFAULT ''         COMMENT 'Mobile menu design type (2 types)',
  ADD COLUMN IF NOT EXISTS `mobile_sidebar_order`     INT(11)             NOT NULL DEFAULT 0          COMMENT 'Mobile sidebar sort order',

  -- ── MOBILE HOME PAGE ──────────────────────────────────────
  ADD COLUMN IF NOT EXISTS `mobile_home_show`         ENUM('yes','no')    NOT NULL DEFAULT 'no'       COMMENT 'Show on mobile home page?',
  ADD COLUMN IF NOT EXISTS `mobile_home_design`       VARCHAR(50)         NOT NULL DEFAULT ''         COMMENT 'Mobile home design type (design1/design2/design3/design4)',
  ADD COLUMN IF NOT EXISTS `mobile_home_format`       ENUM('4','6','8')   NOT NULL DEFAULT '4'        COMMENT 'Mobile home: 4/6/8 image product boxes',
  ADD COLUMN IF NOT EXISTS `mobile_home_order`        INT(11)             NOT NULL DEFAULT 0          COMMENT 'Mobile home page sort order',
  ADD COLUMN IF NOT EXISTS `mobile_bg_color`          VARCHAR(20)         NOT NULL DEFAULT ''         COMMENT 'Mobile category background color (#hex)',
  ADD COLUMN IF NOT EXISTS `mobile_bg_image`          VARCHAR(255)        NOT NULL DEFAULT ''         COMMENT 'Mobile category background image path',
  ADD COLUMN IF NOT EXISTS `mobile_image`             VARCHAR(255)        NOT NULL DEFAULT ''         COMMENT 'Mobile category image (design-dependent)',

  -- ── SEO ───────────────────────────────────────────────────
  ADD COLUMN IF NOT EXISTS `meta_title`               VARCHAR(255)        NOT NULL DEFAULT ''         COMMENT 'SEO meta title',
  ADD COLUMN IF NOT EXISTS `meta_keywords`            TEXT                                            COMMENT 'SEO meta keywords',
  ADD COLUMN IF NOT EXISTS `meta_description`         TEXT                                            COMMENT 'SEO meta description';


-- ── Rename old `image` column to `category_image` if desired (optional)
-- The existing `image` column stays for backward compat.
-- New uploads will use desktop_image / mobile_image columns.

-- VERIFY
-- DESCRIBE categories;
