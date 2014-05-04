/*
 Navicat SQLite Data Transfer

 Source Server         : Braindump
 Source Server Version : 3007005
 Source Database       : main

 Target Server Version : 3007005
 File Encoding         : utf-8

 Date: 04/22/2014 17:50:09 PM
*/

PRAGMA foreign_keys = false;

-- ----------------------------
--  Table structure for "note"
-- ----------------------------
DROP TABLE IF EXISTS "note";
CREATE TABLE "note" (
	 "id" integer NOT NULL PRIMARY KEY AUTOINCREMENT,
	 "notebook_id" integer NOT NULL,
	 "title" text NOT NULL,
	 "created" integer NOT NULL,
	 "updated" integer NOT NULL,
	 "url" text,
	 "content" text,
	CONSTRAINT "notebook_id" FOREIGN KEY ("notebook_id") REFERENCES "notebook" ("notebook_id") ON DELETE CASCADE ON UPDATE CASCADE
);

-- ----------------------------
--  Table structure for "notebook"
-- ----------------------------
DROP TABLE IF EXISTS "notebook";
CREATE TABLE "notebook" (
	 "id" integer NOT NULL PRIMARY KEY AUTOINCREMENT,
	 "title" text NOT NULL
);

PRAGMA foreign_keys = true;
