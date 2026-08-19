<?php

namespace BS\ExtendedSearch\Hook\UserMergeAccountFields;

use BlueSpice\DistributionConnector\Hook\UserMergeAccountFields;

class MergeExtendedSearchDBFields extends UserMergeAccountFields {

	protected function doProcess() {
		$this->updateFields[] = [ 'bs_extendedsearch_history', 'esh_user' ];
	}

}
