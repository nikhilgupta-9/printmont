-- ============================================================
-- Migration 004: Recompute categories.level from parent_id depth
--
-- The level column drifted out of sync with the actual hierarchy:
-- top-level rows (parent_id = 0) were stored as level 0, their children as
-- level 1, and one row (Women Outfit) was level 2 while being a root.
--
-- The APIs read the hierarchy two different ways, so they disagreed:
--   category_api.php?action=categories      WHERE level = 1
--   category_api.php?action=tree            walks parent_id
--   ajax/get-sub-categories.php             WHERE level = 2 AND parent_id = ?
--
-- Result: ?action=categories returned 13 rows that all had parents, while the
-- tree returned 14 completely different rows, and ?action=subcategories was
-- empty for nearly every parent.
--
-- After this migration level is 1-based and derived from parent_id:
--   level 1 = top level, level 2 = child, level 3 = grandchild
--
-- Safe to re-run: it recomputes from parent_id every time.
-- ============================================================

-- STEP 1: top level.
-- Includes rows whose parent_id points at a category that no longer exists —
-- they have no reachable depth, and treating them as level 1 preserves the
-- levels their own children already have.
UPDATE `categories` c
LEFT JOIN `categories` p ON p.id = c.parent_id
SET c.level = 1
WHERE c.parent_id = 0 OR c.parent_id IS NULL OR p.id IS NULL;

-- STEP 2: direct children of a top-level category.
-- Each statement writes a value it never tests for (sets 2, tests for 1), so
-- the result does not depend on the order rows are processed in.
UPDATE `categories` c
JOIN `categories` p ON p.id = c.parent_id
SET c.level = 2
WHERE p.level = 1;

-- STEP 3: grandchildren. The category APIs model three levels, so anything
-- deeper stays at 3.
UPDATE `categories` c
JOIN `categories` p ON p.id = c.parent_id
SET c.level = 3
WHERE p.level = 2;
