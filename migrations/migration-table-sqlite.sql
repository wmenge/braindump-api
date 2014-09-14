-- ----------------------------
--  Table structure for "migration"
-- ----------------------------
CREATE TABLE IF NOT EXISTS "migration" (
	 "id" integer NOT NULL PRIMARY KEY AUTOINCREMENT,
	 "version" text UNIQUE NOT NULL,
	 "executed" integer NOT NULL
);