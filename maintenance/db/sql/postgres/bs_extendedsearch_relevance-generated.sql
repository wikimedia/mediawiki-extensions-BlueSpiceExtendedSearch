-- This file is automatically generated using maintenance/generateSchemaSql.php.
-- Source: extensions/BlueSpiceExtendedSearch/maintenance/db/sql/bs_extendedsearch_relevance.json
-- Do not modify this file directly.
-- See https://www.mediawiki.org/wiki/Manual:Schema_changes
CREATE TABLE bs_extendedsearch_relevance (
  esr_user INT NOT NULL,
  esr_result VARCHAR(100) NOT NULL,
  esr_value SMALLINT DEFAULT 0 NOT NULL,
  esr_timestamp TIMESTAMPTZ DEFAULT NULL,
  PRIMARY KEY(esr_user, esr_result)
);
