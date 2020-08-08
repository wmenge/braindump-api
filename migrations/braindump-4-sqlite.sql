PRAGMA foreign_keys = false;
PRAGMA ignore_check_constraints = true;

-- ----------------------------
--  Update user table. SQLite has limited support for ALTER TABLE, so just create a new table and copy the data
-- ----------------------------

DROP TABLE IF EXISTS "users_temp";

CREATE TABLE "users_temp" (
  "id" integer NOT NULL PRIMARY KEY AUTOINCREMENT,
  "login" text NOT NULL UNIQUE,
  "password" text,
  "permissions" text,
  "activated" integer NOT NULL DEFAULT 0,
  "activation_code" text,
  "activated_at" text,
  "last_login" text,
  "persist_code" text,
  "reset_password_code" text,
  "name" text,
  "created_at" integer NOT NULL DEFAULT 0,
  "updated_at" integer NOT NULL DEFAULT 0
);

CREATE UNIQUE INDEX IF NOT EXISTS "users_login_unique" ON "users_temp" ( "login" );
CREATE INDEX IF NOT EXISTS "users_activation_code_index" ON "users_temp" ( "activation_code" );
CREATE INDEX IF NOT EXISTS "users_reset_password_code_index" ON "users_temp" ( "reset_password_code" );

INSERT INTO "users_temp"("id", "login", "password", "permissions", "activated", "activation_code", "activated_at", "last_login", "persist_code", "reset_password_code", "name", "created_at", "updated_at")
SELECT "id", "email", "password", "permissions", "activated", "activation_code", "activated_at", "last_login", "persist_code", "reset_password_code", "first_name" || ' ' || "last_name", "created_at", "updated_at"
FROM "users";

DROP TABLE IF EXISTS "users";

ALTER TABLE "users_temp" RENAME TO "users";

PRAGMA foreign_keys = true;
PRAGMA ignore_check_constraints = false;


