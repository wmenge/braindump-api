PRAGMA foreign_keys = false;
PRAGMA ignore_check_constraints = true;

-- ----------------------------
--  Table structure for "user_configuration"
-- ----------------------------
DROP TABLE IF EXISTS "file";
CREATE TABLE "file" (
     "id" integer NOT NULL PRIMARY KEY AUTOINCREMENT,
     "user_id" integer REFERENCES "users" ("id") ON UPDATE CASCADE ON DELETE CASCADE,
     "logical_filename" text NOT NULL,
     "physical_filename" text NOT NULL,
     "original_filename" text NOT NULL,
     "hash" text NOT NULL,
     "mime_type" text NOT NULL,
     "size" integer NOT NULL
);

CREATE UNIQUE INDEX IF NOT EXISTS "file_logical_filename" ON "file" ( "user_id", "logical_filename" );
CREATE UNIQUE INDEX IF NOT EXISTS "file_physical_filename" ON "file" ( "physical_filename" );
CREATE INDEX IF NOT EXISTS "file_original_filename" ON "file" ( "original_filename", "hash" );
CREATE INDEX IF NOT EXISTS "file_hash" ON "file" ( "hash" );

PRAGMA foreign_keys = true;
PRAGMA ignore_check_constraints = false;
