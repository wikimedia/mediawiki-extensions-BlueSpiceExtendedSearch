<?php

namespace BS\ExtendedSearch\Source\Formatter;

use BS\ExtendedSearch\ISearchResultFormatter;
use BS\ExtendedSearch\ISearchSource;
use BS\ExtendedSearch\SearchResult;
use BS\ExtendedSearch\Wildcarder;
use MediaWiki\Config\ConfigException;
use MediaWiki\Context\IContextSource;
use MediaWiki\Html\Html;
use MediaWiki\Linker\LinkRenderer;
use MediaWiki\Title\Title;
use MWException;

class Base implements ISearchResultFormatter {
	/**
	 * Used to separate multiple values in arrays
	 * when they are displayed in the UI
	 */
	public const VALUE_SEPARATOR = ', ';

	/**
	 * Used to indicate there are more values than
	 * can be displayed
	 */
	public const MORE_VALUES_TEXT = '...';

	public const AC_RANK_PRIMARY = 'primary';
	public const AC_RANK_SECONDARY = 'secondary';

	/**
	 *
	 * @var ISearchSource
	 */
	protected $source;

	/**
	 *
	 * @var \BS\ExtendedSearch\Lookup
	 */
	protected $lookup;

	/**
	 * @var LinkRenderer
	 */
	protected $linkRenderer;

	/**
	 *
	 * @param ISearchSource $source
	 */
	public function __construct( $source ) {
		$this->source = $source;
		// Just for convenience, as many of the formatters would use it
		$this->linkRenderer = $this->source->getBackend()->getService( 'LinkRenderer' );
	}

	/**
	 * Sets current instance of Lookup object that the
	 * result being formatted
	 *
	 * @param \BS\ExtendedSearch\Lookup $lookup
	 */
	public function setLookup( $lookup ): void {
		$this->lookup = $lookup;
	}

	/**
	 * Convenience function - returns RequestContext object
	 *
	 * @return IContextSource
	 */
	public function getContext() {
		return $this->source->getBackend()->getContext();
	}

	/**
	 * Returns structure of the result for each source
	 * It allows sources to modify default result structure
	 *
	 * @param array $defaultResultStructure
	 * @return array
	 */
	public function getResultStructure( $defaultResultStructure = [] ): array {
		return $defaultResultStructure;
	}

	/**
	 * Allows sources to modify data returned by ES,
	 * before it goes to the client-side
	 *
	 * @param array &$resultData
	 * @param SearchResult $resultObject
	 */
	public function format( &$resultData, $resultObject ): void {
		// Base class format must work with original values
		// because it might be called multiple times
		$originalValues = $resultObject->getData();
		$resultData['id'] = $resultObject->getId();
		$resultData['type'] = $resultObject->getType();
		$resultData['score'] = $resultObject->getScore();
		$resultData['_index'] = $resultObject->getIndex();
		$resultData['_is_foreign'] = $this->source->getBackend()->isForeignIndex( $resultObject->getIndex() );

		if ( !$resultData['_is_foreign'] ) {
			$user = $this->getContext()->getUser();
			if ( $user->isRegistered() ) {
				$resultRelevance = new \BS\ExtendedSearch\ResultRelevance( $user, $resultObject->getId() );
				$resultData['user_relevance'] = (int)$resultRelevance->getValue();
			} else {
				$resultData['user_relevance'] = false;
			}
		}

		$type = $resultData['type'];
		$resultData['typetext'] = $this->getTypeText( $type );

		if ( $this->isFeatured( $resultData ) ) {
			$resultData['featured'] = 1;
		}
		$resultData['search_more'] = $resultObject->getParam( 'sort' );

		if ( !isset( $originalValues['ctime'] ) || !isset( $originalValues['mtime'] ) ) {
			// Not all types have these
			return;
		}
		$resultData['ctime'] = $this->getContext()->getLanguage()->date( $originalValues['ctime'] );
		$resultData['mtime'] = $this->getContext()->getLanguage()->date( $originalValues['mtime'] );
	}

	/**
	 * Allows sources to modify results of autocomplete query
	 *
	 * @param array &$results
	 * @param array $searchData
	 */
	public function formatAutocompleteResults( &$results, $searchData ): void {
		foreach ( $results as &$result ) {
			// For some reason _keys are not transmitted to client
			$result['id'] = $result['_id'];
			if ( !isset( $result['mtime'] ) || $result['rank'] !== 'top' ) {
				continue;
			}
			$result['modified_time'] = $this->getContext()->getLanguage()->timeanddate( $result['mtime'] );
			unset( $result['mtime'] );
		}
	}

	/**
	 *
	 * @param string $type
	 * @return string
	 */
	protected function getTypeText( $type ) {
		$typeText = $type;
		if ( wfMessage( "bs-extendedsearch-source-type-$type-label" )->exists() ) {
			$typeText = wfMessage( "bs-extendedsearch-source-type-$type-label" )->plain();
		}

		return $typeText;
	}

	/**
	 * Allows sources to change ranking of the autocomplete query results
	 * Exact matches are TOP, matches containing search term are NORMAL,
	 * and matches not containing search term (fuzzy) are SECONDARY
	 *
	 * Ranking controls where result will be shown( which part of AC popup )
	 *
	 * @param array &$results
	 * @param array $searchData
	 * @throws MWException
	 */
	public function rankAutocompleteResults( &$results, $searchData ): void {
		foreach ( $results as &$result ) {
			if ( $result['is_ranked'] === true ) {
				continue;
			}

			$lcBasename = mb_strtolower( $result['basename'] );
			$lcSearchTerm = mb_strtolower( $searchData['value'] );
			if ( $this->matchTokenized( $lcBasename, $lcSearchTerm ) ) {
				$result['rank'] = self::AC_RANK_PRIMARY;
			} else {
				$result['rank'] = self::AC_RANK_SECONDARY;
			}

			$result['is_ranked'] = true;
		}
	}

	/**
	 * Splits needle up in tokens and matches each token
	 * with the result title. If one token does not match
	 * whole matching is failed
	 *
	 * @param string $haystack
	 * @param string $needle
	 * @return bool
	 * @throws MWException
	 * @throws ConfigException
	 */
	protected function matchTokenized( $haystack, $needle ) {
		$separated = Wildcarder::factory( $needle )->getSeparated( [ '\s' ] );
		foreach ( $separated as $bit ) {
			if ( strpos( $haystack, $bit ) === false ) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Basic implementation. Checks if searched term
	 * matches result exactly
	 *
	 * @param array $result
	 * @return bool
	 */
	protected function isFeatured( $result ) {
		if ( $this->lookup == null ) {
			return false;
		}

		$queryString = $this->lookup->getQueryString();
		if ( !isset( $queryString['query'] ) ) {
			return false;
		}

		$term = $queryString['query'];
		if ( strtolower( $term ) == strtolower( $result['basename'] ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Allows sources to modify filterCfg if needed
	 *
	 * @param array &$aggs
	 * @param array &$filterCfg
	 * @param bool $fieldsWithANDEnabled
	 */
	public function formatFilters( &$aggs, &$filterCfg, $fieldsWithANDEnabled = false ): void {
		if ( isset( $filterCfg['namespace'] ) ) {
			foreach ( $filterCfg['namespace']['buckets'] as &$bucket ) {
				$id = (int)$bucket['key'];
				if ( $id === NS_MAIN ) {
					$bucket['label'] = wfMessage( 'blanknamespace' )->text();
				} else {
					$bucket['label'] = $this->getContext()->getLanguage()->getNsText( $id );
				}
				$bucket['key'] = (string)$bucket['key'];
			}
		}
	}

	/**
	 *
	 * @param array $results
	 * @return array|false
	 */
	protected function getACHighestScored( $results ) {
		$highest = false;
		foreach ( $results as $result ) {
			if ( !$highest || ( $result['score'] > $highest['score'] ) ) {
				$highest = $result;
			}
		}

		return $highest;
	}

	/**
	 * Get page anchor that can be traced
	 * @param Title $title
	 * @param string $text
	 * @return string
	 */
	public function getTraceablePageAnchor( Title $title, string $text ): string {
		$data = [
			'dbkey' => $title->getDBkey(),
			'namespace' => $title->getNamespace(),
			'url' => $title->getFullURL()
		];
		if ( $text ) {
			$display = $text;
		} else {
			$display = $title->getText();
			if ( $title->isSubpage() ) {
				$display = $title->getSubpageText();
			}
		}

		return Html::element( 'a', [
			'href' => $title->getLocalURL(),
			'class' => 'bs-traceable-link',
			'data-bs-traceable-page' => json_encode( $data ),
			'data-title' => $title->getPrefixedText()
		], $display );
	}
}
