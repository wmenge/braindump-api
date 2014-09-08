-- ----------------------------
--  Drop all known application tables
-- ----------------------------
PRAGMA foreign_keys = false;

DROP TABLE IF EXISTS "notes";
DROP TABLE IF EXISTS "notebooks";
DROP TABLE IF EXISTS "migration";

PRAGMA foreign_keys = true;