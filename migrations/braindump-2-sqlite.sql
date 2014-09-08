-- ----------------------------
--  Add created and updated columns for table "notebook"
-- ----------------------------
ALTER TABLE "notebook" ADD COLUMN "created" integer NOT NULL DEFAULT 0;
ALTER TABLE "notebook" ADD COLUMN "updated" integer NOT NULL DEFAULT 0;