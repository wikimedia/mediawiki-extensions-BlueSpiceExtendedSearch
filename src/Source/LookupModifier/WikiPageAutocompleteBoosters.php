<?php

namespace BS\ExtendedSearch\Source\LookupModifier;

class WikiPageAutocompleteBoosters extends Base {

	public function apply() {
		//Boost "wikipage" type as its most important on a wiki
		$this->oLookup->addShouldMatch( '_type', 'wikipage', 3 );
		//Boost NS_MAIN
		$this->oLookup->addShouldMatch( 'namespace', 0, 2 );
	}

	public function undo() {
	}

}
