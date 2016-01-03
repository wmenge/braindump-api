PRAGMA foreign_keys = false;
PRAGMA ignore_check_constraints = true;

-- ----------------------------
--  Table structure for "user_configuration"
-- ----------------------------
DROP TABLE IF EXISTS "user_configuration";
CREATE TABLE "user_configuration" (
     "id" integer NOT NULL PRIMARY KEY AUTOINCREMENT,
     "user_id" integer REFERENCES "users" ("id") ON UPDATE CASCADE ON DELETE CASCADE,
     "email_to_notebook" integer references "notebook" ("id")
);

CREATE UNIQUE INDEX IF NOT EXISTS "user_configuration_user_id" ON "user_configuration" ( "user_id" );

PRAGMA foreign_keys = true;
PRAGMA ignore_check_constraints = false;
