<?php

namespace BS\ExtendedSearch\Source\LookupModifier;

class WikiPageRenderedContentHighlight extends LookupModifier {

	public function apply() {
		$this->lookup->addHighlighter( 'rendered_content' );
	}

	public function undo() {
		$this->lookup->removeHighlighter( 'rendered_content' );
	}
}
