PRAGMA foreign_keys = false;
PRAGMA ignore_check_constraints = false;

-- ----------------------------
--  Table structure for "note"
-- ----------------------------
DROP TABLE IF EXISTS "note";
CREATE TABLE "note" (
	 "id" integer NOT NULL PRIMARY KEY AUTOINCREMENT,
	 "notebook_id" integer NOT NULL REFERENCES "notebook" ("id") ON UPDATE CASCADE ON DELETE CASCADE,
	 "title" text NOT NULL,
	 "created" integer NOT NULL,
	 "updated" integer NOT NULL,
	 "url" text,
	 "type" text NOT NULL CHECK (type IN ('HTML', 'Text')),
	 "content" text,
	 "user_id" integer REFERENCES "users" ("id") ON UPDATE CASCADE ON DELETE CASCADE	 
);

CREATE INDEX IF NOT EXISTS "user" ON "note" ( "user_id" );
CREATE INDEX IF NOT EXISTS "notebook_id" ON "note" ( "notebook_id" );

-- ----------------------------
--  Table structure for "notebook"
-- ----------------------------
DROP TABLE IF EXISTS "notebook";
CREATE TABLE IF NOT EXISTS "notebook" (
	 "id" integer NOT NULL PRIMARY KEY AUTOINCREMENT,
	 "title" text NOT NULL,
	 "created" integer NOT NULL,
	 "updated" integer NOT NULL,
	 "user_id" integer REFERENCES "users" ("id") ON UPDATE CASCADE ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS "user" ON "notebook" ( "user_id" );
CREATE UNIQUE INDEX IF NOT EXISTS "unique_title" ON "notebook" ( "title", "user_id" );

-- ----------------------------
--  Table structure for "groups"
-- ----------------------------
DROP TABLE IF EXISTS "groups";
CREATE TABLE "groups" (
  "id" integer NOT NULL PRIMARY KEY AUTOINCREMENT,
  "name" text NOT NULL UNIQUE,
  "permissions" text,
  "created_at" integer NOT NULL DEFAULT 0,
  "updated_at" integer NOT NULL DEFAULT 0
);

CREATE UNIQUE INDEX IF NOT EXISTS "groups_name_unique" ON "groups" ( "name" );
 
INSERT INTO "groups" VALUES (1, "Administrators", "{""|^/admin(/.*)?$|"":1, ""|^/api(/.*)?$|"":1}", date('now'), date('now'));
INSERT INTO "groups" VALUES (2, "Users", "{""|^/api(/.*)?$|"":1}", date('now'), date('now'));

-- ----------------------------
--  Table structure for "users"
-- ----------------------------
DROP TABLE IF EXISTS "users";

CREATE TABLE "users" (
  "id" integer NOT NULL PRIMARY KEY AUTOINCREMENT,
  "email" text NOT NULL UNIQUE,
  "password" text NOT NULL,
  "permissions" text,
  "activated" integer NOT NULL DEFAULT 0,
  "activation_code" text,
  "activated_at" text,
  "last_login" text,
  "persist_code" text,
  "reset_password_code" text,
  "first_name" text,
  "last_name" text,
  "created_at" integer NOT NULL DEFAULT 0,
  "updated_at" integer NOT NULL DEFAULT 0
);

CREATE UNIQUE INDEX IF NOT EXISTS "users_email_unique" ON "users" ( "email" );
CREATE INDEX IF NOT EXISTS "users_activation_code_index" ON "users" ( "activation_code" );
CREATE INDEX IF NOT EXISTS "users_reset_password_code_index" ON "users" ( "reset_password_code" );

-- ----------------------------
--  Table structure for "throttle"
-- ----------------------------
DROP TABLE IF EXISTS "throttle";

CREATE TABLE "throttle" (
  "id" integer NOT NULL PRIMARY KEY AUTOINCREMENT,
  "user_id" integer NOT NULL,
  "ip_address" text NULL,
  "attempts" integer NOT NULL DEFAULT '0',
  "suspended" integer NOT NULL DEFAULT '0',
  "banned" integer NOT NULL DEFAULT '0',
  "last_attempt_at" integer NULL DEFAULT NULL,
  "suspended_at" integer NULL DEFAULT NULL,
  "banned_at" integer NULL DEFAULT NULL
);

CREATE INDEX IF NOT EXISTS "fk_user_id" ON "throttle" ( "user_id" );

-- ----------------------------
--  Table structure for "users_groups"
-- ----------------------------
DROP TABLE IF EXISTS "users_groups";

CREATE TABLE "users_groups" (
  "id" integer NOT NULL PRIMARY KEY AUTOINCREMENT,
  "user_id" integer NOT NULL,
  "group_id" integer NOT NULL
);

PRAGMA foreign_keys = true;
PRAGMA ignore_check_constraints = false;
