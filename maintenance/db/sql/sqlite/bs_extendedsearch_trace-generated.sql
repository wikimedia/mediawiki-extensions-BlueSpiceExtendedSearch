-- This file is automatically generated using maintenance/generateSchemaSql.php.
-- Source: extensions/BlueSpiceExtendedSearch/maintenance/db/sql/bs_extendedsearch_trace.json
-- Do not modify this file directly.
-- See https://www.mediawiki.org/wiki/Manual:Schema_changes
CREATE TABLE /*_*/bs_extendedsearch_trace (
  est_title VARCHAR(255) NOT NULL,
  est_namespace INTEGER NOT NULL,
  est_user INTEGER NOT NULL,
  est_type VARCHAR(255) NOT NULL,
  est_count INTEGER DEFAULT 0 NOT NULL,
  est_last_view BLOB NOT NULL,
  PRIMARY KEY(
    est_title, est_namespace, est_user
  )
);