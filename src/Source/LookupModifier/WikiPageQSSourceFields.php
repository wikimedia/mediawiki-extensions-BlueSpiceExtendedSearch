<?php

namespace BS\ExtendedSearch\Source\LookupModifier;

use MediaWiki\MediaWikiServices;

class WikiPageQSSourceFields extends LookupModifier {

	public function apply() {
		foreach ( $this->getSource() as $field ) {
			$this->lookup->addSourceField( $field );
		}
		$queryString = $this->lookup->getQueryString();

		$fields = $this->getFieldsToSearchIn();
		if ( isset( $queryString['fields'] ) && is_array( $queryString['fields'] ) ) {
			$queryString['fields'] = array_merge( $queryString['fields'], $fields );
		} else {
			$queryString['fields'] = $fields;
		}

		$this->lookup->setQueryString( $queryString );
	}

	public function undo() {
		$queryString = $this->lookup->getQueryString();

		if ( isset( $queryString['fields'] ) && is_array( $queryString['fields'] ) ) {
			$queryString['fields'] = array_diff(
				$queryString['fields'],
				$this->getFieldsToSearchIn()
			);
		}

		$this->lookup->setQueryString( $queryString );
		foreach ( $this->getSource() as $field ) {
			$this->lookup->removeSourceField( $field );
		}
	}

	/**
	 * @return string[]
	 */
	private function getFieldsToSearchIn() {
		$fields = [ 'rendered_content', 'prefixed_title', 'display_title^2' ];
		if ( $this->shouldSearchInRaw() ) {
			$fields[] = 'source_content';
			$fields[] = 'source_content.raw';
		}

		return $fields;
	}

	/**
	 * @return string[]
	 */
	private function getSource() {
		return [
			'rendered_content',
			'rendered_content.raw',
			'prefixed_title',
			'display_title',
			'namespace',
			'namespace_text',
			'sections',
			'categories',
			'is_redirect',
			'redirects_to',
			'page_language',
			'page_id'
		];
	}

	private function shouldSearchInRaw() {
		$config = MediaWikiServices::getInstance()->getConfigFactory()->makeConfig( 'bsg' );
		return (bool)$config->get( 'ESSearchInRawWikitext' );
	}
}
