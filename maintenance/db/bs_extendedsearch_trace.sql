CREATE TABLE IF NOT EXISTS /*$wgDBprefix*/bs_extendedsearch_trace (
	est_title VARCHAR(255) NOT NULL,
	est_namespace INT NOT NULL,
	est_user INT(6) NOT NULL DEFAULT '0',
	est_type VARCHAR (255) NOT NULL,
    est_count INT NULL DEFAULT '0',
    est_last_view VARCHAR(14) NOT NULL,
	PRIMARY KEY (est_title, est_namespace, est_user )
) /*$wgDBTableOptions*/;
