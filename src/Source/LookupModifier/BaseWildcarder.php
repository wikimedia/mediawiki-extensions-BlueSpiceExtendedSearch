<?php

namespace BS\ExtendedSearch\Source\LookupModifier;

class BaseWildcarder extends Base {

	public function apply() {
		return;
		$queryString = $this->oLookup->getSimpleQueryString();
		$rawString = $queryString['query'];
		$this->oLookup->setSimpleQueryString( $rawString . '*' );
	}

	public function undo() {
		
	}

}

