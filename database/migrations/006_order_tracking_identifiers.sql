-- ============================================================
-- Migration 006: Generated order and tracking identifiers
--
-- Orders used to get a sequential order_number (ORD-1001, ORD-1002 ...).
-- Sequential numbers are guessable, which matters because the public
-- Track Order lookup takes an order number, and they leak how many
-- orders the shop has taken.
--
-- New orders now get:
--   order_number    12 characters, letters + digits, no look-alikes
--                   (0/O and 1/I/L are excluded so customers can type
--                   them off an invoice without ambiguity)
--   tracking_number 10 digits, generated at order time and never changed
--
-- Because tracking_number is now customer-facing and permanent, the
-- courier's own AWB needs its own home -- overwriting tracking_number
-- with it would invalidate the number the customer was given at
-- checkout.
--
-- This migration:
--   1. Adds courier_tracking_number for the courier's AWB.
--   2. Puts a UNIQUE index on tracking_number so a generated collision
--      fails loudly at insert instead of silently pointing two orders
--      at the same tracking id. NULLs are exempt from UNIQUE in MySQL,
--      so existing untracked orders are unaffected.
--
-- Existing ORD-xxxx orders keep their numbers: they have already been
-- sent to customers, and order_number stays varchar(50) so both formats
-- coexist.
-- ============================================================

-- Both statements are safe to re-run: the runner treats "duplicate column" and
-- "duplicate key name" as already-applied, so a live database that already has
-- them is recorded as auto-applied rather than failing the migration.

ALTER TABLE `orders`
    ADD COLUMN `courier_tracking_number` VARCHAR(100) NULL DEFAULT NULL
        COMMENT 'Courier AWB / consignment number, entered by admin'
        AFTER `courier_name`;

ALTER TABLE `orders`
    ADD UNIQUE INDEX `uniq_orders_tracking_number` (`tracking_number`);
