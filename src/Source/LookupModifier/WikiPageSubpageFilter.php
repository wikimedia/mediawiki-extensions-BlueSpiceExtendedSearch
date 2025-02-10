<?php

namespace BS\ExtendedSearch\Source\LookupModifier;

use MediaWiki\Config\ConfigException;
use MediaWiki\MediaWikiServices;

class WikiPageSubpageFilter extends LookupModifier {
	/** @var string */
	protected $originalQuery;
	/** @var string */
	protected $basePage;
	/** @var bool */
	protected $skip = false;

	/**
	 * If so configured and terms matches, search only inside parent page
	 */
	public function apply() {
		$this->originalQuery = $this->lookup->getQueryString()['query'];
		if ( $this->shouldSkip() ) {
			$this->skip = true;
		} else {
			$this->setSubpageSearch();
		}
	}

	/**
	 * @return bool
	 * @throws ConfigException
	 */
	private function shouldSkip() {
		$config = MediaWikiServices::getInstance()->getConfigFactory()->makeConfig( 'bsg' );
		if (
			!$config->get( 'ESAutoRecognizeSubpages' ) ||
			(
				$config->has( 'ESUseSubpagePillsAutocomplete' ) &&
				!$config->get( 'ESUseSubpagePillsAutocomplete' )
			)
		) {
			return true;
		}

		$parts = explode( '/', $this->originalQuery );

		return count( $parts ) === 1;
	}

	/**
	 * Split and set new query and filters
	 */
	protected function setSubpageSearch() {
		$queryString = $this->lookup->getQueryString();
		$parts = explode( '/', $this->originalQuery );
		$pageQuery = array_pop( $parts );
		$this->basePage = implode( '/', $parts );
		$queryString['query'] = $pageQuery;
		$this->lookup->setQueryString( $queryString );
		$this->lookup->addTermFilter( 'basename_exact', $this->basePage );
	}

	/**
	 * If previously applied, undo any changes made
	 */
	public function undo() {
		if ( $this->skip ) {
			return;
		}
		$queryString = $this->lookup->getQueryString();
		$queryString['query'] = $this->originalQuery;
		$this->lookup->setQueryString( $queryString );
		$this->lookup->removeTermFilter( 'basename_exact', $this->basePage );
	}
}
