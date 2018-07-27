CREATE TABLE IF NOT EXISTS /*$wgDBprefix*/bs_extendedsearch_history (
	esh_id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
	esh_user INT(6) NOT NULL,
	esh_term VARCHAR (255) NOT NULL,
	esh_hits INT NOT NULL DEFAULT '0',
	esh_hits_approximated SMALLINT(1) NOT NULL DEFAULT '0',
	esh_timestamp VARCHAR (15) NULL,
	esh_autocorrected SMALLINT(1) NOT NULL DEFAULT '0',
	esh_lookup TEXT NULL
) /*$wgDBTableOptions*/;
