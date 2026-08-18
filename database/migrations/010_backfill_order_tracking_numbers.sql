-- ============================================================
-- Migration 010: Backfill tracking numbers on existing orders
--
-- Migration 006 added tracking_number and started generating a 10-digit id
-- for every NEW order. Orders placed before that still have NULL, so they
-- cannot be found on the Track Order page by tracking id, and My Orders has
-- nothing to display for them.
--
-- Derives the number from the order id rather than randomising, so it is
-- deterministic, guaranteed unique (id is the primary key), exactly 10
-- digits, and produces the same result if this runs again.
--
-- Safe to re-run: only touches rows that are still empty.
-- ============================================================

UPDATE `orders`
SET `tracking_number` = CAST(2000000000 + `id` AS CHAR)
WHERE `tracking_number` IS NULL OR `tracking_number` = '';
