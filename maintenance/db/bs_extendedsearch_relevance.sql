CREATE TABLE IF NOT EXISTS /*$wgDBprefix*/bs_extendedsearch_relevance (
	esr_user INT (6) NOT NULL,
	esr_result VARCHAR (100) NOT NULL,
	esr_value SMALLINT(1) NOT NULL DEFAULT '0',
	esr_timestamp VARCHAR (15) NULL,
	PRIMARY KEY (esr_user, esr_result)
) /*$wgDBTableOptions*/;
