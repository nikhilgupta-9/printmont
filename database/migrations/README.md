# Database migrations

Every `.sql` file in this folder is applied once, in filename order, and recorded
in the `schema_migrations` table. Run them from the admin panel:
**DB Migrations** in the sidebar (`migrations.php`).

## Adding a migration

1. Create `NNN_short_description.sql` using the next free number.
2. Deploy the file to the server.
3. Open **DB Migrations** — it appears as *Pending*.
4. Take a database backup, then click **Run N pending migrations**.

## Rules

- **Never edit a migration that has already run.** Add a new one instead. Edited
  files are flagged *File changed* on the admin page because the recorded sha1 no
  longer matches.
- **Keep the numeric prefix.** Ordering is by filename.
- **Write defensively** so a re-run cannot break anything:
  - `CREATE TABLE IF NOT EXISTS`
  - `ALTER TABLE ... ADD COLUMN IF NOT EXISTS`
  - `INSERT IGNORE` / `ON DUPLICATE KEY UPDATE`
  - `DROP TABLE IF EXISTS`
- **Be careful with destructive statements.** `001_banner_sections_refactor.sql`
  contains `DROP TABLE IF EXISTS home_sliders`, which is why a database that was
  migrated by hand must be baselined rather than re-run.

## Baselining an existing database

If the schema was already updated manually, `schema_migrations` will be empty
while the tables already exist. Running the files then could drop live data. The
admin page detects this and offers **Mark all as already applied**, which records
the files without executing them. Do that once; afterwards only genuinely new
migrations run.

## Caveat: no rollback

MySQL/MariaDB commit DDL immediately, so a migration that fails halfway cannot be
rolled back — earlier statements in the file stay applied. The runner stops at the
first error and reports which statement failed. **Always back up before running on
production.**
