<?php

namespace BS\ExtendedSearch\Source\PostProcessor;

use Elastica\Result;

class WikiPage extends Base {

	/**
	 * @param Result $result
	 * @return string
	 */
	protected function getTitleField( $result ) {
		$data = $result->getData();
		if ( $data['display_title'] !== '' ) {
			return $data['display_title'];
		}
		return $data['prefixed_title'];
	}
}
