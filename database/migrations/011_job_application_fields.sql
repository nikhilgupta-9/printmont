-- ============================================================
-- Migration 011: Missing columns on job_applications
--
-- CareerController::createApplication inserts into linkedin_url,
-- portfolio_url, experience, education and skills, but none of those
-- columns exist. prepare() therefore returned false and the next line
-- called bind_param() on it, so every job application died with a PHP
-- fatal error — the careers apply form could never have worked.
--
-- Adding the columns the controller already writes, rather than dropping
-- the fields, so the application form can capture them.
--
-- Safe to re-run: the migration runner treats an existing column as
-- already applied.
-- ============================================================

ALTER TABLE `job_applications` ADD COLUMN `linkedin_url` VARCHAR(500) NULL AFTER `cover_letter`;
ALTER TABLE `job_applications` ADD COLUMN `portfolio_url` VARCHAR(500) NULL AFTER `linkedin_url`;
ALTER TABLE `job_applications` ADD COLUMN `experience` VARCHAR(255) NULL AFTER `portfolio_url`;
ALTER TABLE `job_applications` ADD COLUMN `education` VARCHAR(255) NULL AFTER `experience`;
ALTER TABLE `job_applications` ADD COLUMN `skills` TEXT NULL AFTER `education`;
