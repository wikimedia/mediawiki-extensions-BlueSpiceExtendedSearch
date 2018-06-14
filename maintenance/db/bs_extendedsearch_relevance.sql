CREATE TABLE IF NOT EXISTS /*$wgDBprefix*/bs_extendedsearch_relevance (
	rel_user		INT		(6)					NOT NULL,
	rel_result		VARCHAR	(100)				NOT NULL,
	rel_value		SMALLINT(1)					NOT NULL		DEFAULT '0',
	rel_timestamp	VARCHAR	(15)				NULL,
	PRIMARY KEY (rel_user, rel_result)
) /*$wgDBTableOptions*/;
