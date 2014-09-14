PRAGMA foreign_keys = false;

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
	 "content" text
);

CREATE INDEX IF NOT EXISTS "notebook_id" ON "note" ( "notebook_id" );

-- ----------------------------
--  Table structure for "notebook"
-- ----------------------------
DROP TABLE IF EXISTS "notebook";
CREATE TABLE IF NOT EXISTS "notebook" (
	 "id" integer NOT NULL PRIMARY KEY AUTOINCREMENT,
	 "title" text NOT NULL
);

PRAGMA foreign_keys = true;
