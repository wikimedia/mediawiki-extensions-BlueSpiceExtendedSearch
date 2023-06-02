<?php

namespace BS\ExtendedSearch\Source\LookupModifier;

use BS\ExtendedSearch\Backend;

class BaseConvertTypeFilter extends LookupModifier {

	public function apply() {
		$filter = $this->lookup->getFilters();
		$terms = $filter['terms'] ?? null;
		if ( !$terms ) {
			return;
		}
		if ( !isset( $filter['terms']['_type'] ) ) {
			return;
		}
		$types = $filter['terms']['_type'];
		$this->lookup->removeTermsFilter( '_type', $types );
		$this->lookup->addSearchInTypes( $types );
	}

	public function undo() {
		$searchInTypes = $this->lookup['searchInTypes'] ?? [];
		if ( empty( $searchInTypes ) ) {
			return;
		}
		$this->lookup->clearTypeWhitelist();
		$this->lookup->addTermsFilter( '_type', $searchInTypes );
	}

	/**
	 * @return string[]
	 */
	public function getSearchTypes() {
		return [ Backend::QUERY_TYPE_AUTOCOMPLETE, Backend::QUERY_TYPE_SEARCH ];
	}
}
