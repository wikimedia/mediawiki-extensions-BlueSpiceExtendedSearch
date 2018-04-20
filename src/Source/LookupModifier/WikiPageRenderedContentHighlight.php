<?php

namespace BS\ExtendedSearch\Source\LookupModifier;

class WikiPageRenderedContentHighlight extends Base {

	public function apply() {
		$this->oLookup->addHighlighter( 'rendered_content' );
	}

	public function undo() {
		$this->oLookup->removeHighlighter( 'rendered_content' );
	}
}