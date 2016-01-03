-- ----------------------------
--  Drop all known application tables
-- ----------------------------
PRAGMA foreign_keys = false;

DROP TABLE IF EXISTS "notes";
DROP TABLE IF EXISTS "notebooks";
DROP TABLE IF EXISTS "migration";

DROP TABLE IF EXISTS "groups";
DROP TABLE IF EXISTS "users";
DROP TABLE IF EXISTS "throttle";

DROP TABLE IF EXISTS "user_configuration";

DROP TABLE IF EXISTS "file";

PRAGMA foreign_keys = true;