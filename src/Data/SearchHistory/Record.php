<?php

namespace BS\ExtendedSearch\Data\SearchHistory;

use BlueSpice\Data\Record as BaseRecord;

class Record extends BaseRecord {
	public const ID = 'esh_id';
	public const USER_ID = 'esh_user';
	public const TERM = 'esh_term';
	public const HITS = 'esh_hits';
	public const HITS_APPROXIMATED = 'esh_hits_approximated';
	public const TIMESTAMP = 'esh_timestamp';
	public const AUTOCORRECTED = 'esh_autocorrected';
	public const LOOKUP = 'esh_lookup';
}
