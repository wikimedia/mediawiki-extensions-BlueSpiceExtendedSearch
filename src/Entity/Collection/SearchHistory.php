<?php

namespace BS\ExtendedSearch\Entity\Collection;

use BlueSpice\ExtendedStatistics\Entity\Collection;

class SearchHistory extends Collection {
	const TYPE = 'searchhistory';

	const ATTR_NUMBER_SEARCHED = 'numbersearched';
	const ATTR_TERM = 'searchterm';
}
