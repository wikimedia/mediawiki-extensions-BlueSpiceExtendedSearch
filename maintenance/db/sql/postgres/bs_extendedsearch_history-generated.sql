-- This file is automatically generated using maintenance/generateSchemaSql.php.
-- Source: extensions/BlueSpiceExtendedSearch/maintenance/db/sql/bs_extendedsearch_history.json
-- Do not modify this file directly.
-- See https://www.mediawiki.org/wiki/Manual:Schema_changes
CREATE TABLE bs_extendedsearch_history (
  esh_id SERIAL NOT NULL,
  esh_user INT NOT NULL,
  esh_term VARCHAR(255) NOT NULL,
  esh_hits INT DEFAULT 0 NOT NULL,
  esh_hits_approximated SMALLINT DEFAULT 0 NOT NULL,
  esh_timestamp TIMESTAMPTZ DEFAULT NULL,
  esh_autocorrected SMALLINT DEFAULT 0 NOT NULL,
  esh_lookup TEXT DEFAULT NULL,
  PRIMARY KEY(esh_id)
);