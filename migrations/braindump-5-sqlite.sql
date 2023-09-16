PRAGMA foreign_keys = false;
PRAGMA ignore_check_constraints = true;

-- ----------------------------
--  Add Markdown as type
-- ----------------------------

ALTER TABLE "note" RENAME COLUMN "type" to "type_old";
ALTER TABLE "note" ADD COLUMN "type" text NOT NULL CHECK (type IN ('HTML', 'Text', 'Markdown')) default 'Text';
UPDATE "note" SET "type" = "type_old";
ALTER TABLE "note" DROP COLUMN "type_old";