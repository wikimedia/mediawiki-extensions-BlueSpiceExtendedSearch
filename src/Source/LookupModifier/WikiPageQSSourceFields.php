<?php

namespace BS\ExtendedSearch\Source\LookupModifier;

use MediaWiki\MediaWikiServices;

class WikiPageQSSourceFields extends Base {

	/**
	 * Adds fields that will be searched including query-time boosting
	 */
	public function apply() {
		$queryString = $this->oLookup->getQueryString();

		$fields = $this->getFields();
		if ( isset( $queryString['fields'] ) && is_array( $queryString['fields'] ) ) {
			$queryString['fields'] = array_merge( $queryString['fields'], $fields );
		} else {
			$queryString['fields'] = $fields;
		}

		$this->oLookup->setQueryString( $queryString );
	}

	public function undo() {
		$queryString = $this->oLookup->getQueryString();

		if ( isset( $queryString['fields'] ) && is_array( $queryString['fields'] ) ) {
			$queryString['fields'] = array_diff(
				$queryString['fields'],
				$this->getFields()
			);
		}

		$this->oLookup->setQueryString( $queryString );
	}

	/**
	 * @return string[]
	 */
	private function getFields() {
		$fields = [ 'rendered_content', 'prefixed_title', 'display_title^2' ];
		if ( $this->shouldSearchInRaw() ) {
			$fields[] = 'source_content';
		}

		return $fields;
	}

	private function shouldSearchInRaw() {
		$config = MediaWikiServices::getInstance()->getConfigFactory()->makeConfig( 'bsg' );
		return (bool)$config->get( 'ESSearchInRawWikitext' );
	}
}
