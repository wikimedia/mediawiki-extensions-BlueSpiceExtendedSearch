<?php

namespace BS\ExtendedSearch\Data\SearchHistory;

use BlueSpice\Data\Record as BaseRecord;

class Record extends BaseRecord {
	const ID = 'esh_id';
	const USER_ID = 'esh_user';
	const TERM = 'esh_term';
	const HITS = 'esh_hits';
	const HITS_APPROXIMATED = 'esh_hits_approximated';
	const TIMESTAMP = 'esh_timestamp';
	const AUTOCORRECTED = 'esh_autocorrected';
	const LOOKUP = 'esh_lookup';
}
