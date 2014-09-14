PRAGMA foreign_keys = false;
PRAGMA ignore_check_constraints = false;

-- ----------------------------
--  Add:
--  * Relation between users and notes/notebookds
--  * Index to enforce that notebook titles are unique for a user
-- ----------------------------
ALTER TABLE "notebook" ADD COLUMN "user_id" integer REFERENCES "users" ("id") ON UPDATE CASCADE ON DELETE CASCADE;
ALTER TABLE "note" ADD COLUMN "user_id" integer REFERENCES "users" ("id") ON UPDATE CASCADE ON DELETE CASCADE;

-- If there are already Notes or Notebooks in the DB, assign them to the first user
UPDATE "notebook" SET "user_id" = 1 WHERE user_id = 0;
UPDATE "note" SET "user_id" = 1 WHERE user_id = 0;

CREATE INDEX IF NOT EXISTS "user" ON "note" ( "user_id" );
CREATE INDEX IF NOT EXISTS "user" ON "notebook" ( "user_id" );
CREATE UNIQUE INDEX IF NOT EXISTS "unique_title" ON "notebook" ( "title", "user_id" );

PRAGMA foreign_keys = true;
PRAGMA ignore_check_constraints = false;