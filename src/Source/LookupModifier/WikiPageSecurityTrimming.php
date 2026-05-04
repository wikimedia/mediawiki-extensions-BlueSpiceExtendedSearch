<?php

namespace BS\ExtendedSearch\Source\LookupModifier;

use BS\ExtendedSearch\Backend;
use BS\ExtendedSearch\Lookup;
use MediaWiki\Context\IContextSource;
use MediaWiki\MediaWikiServices;
use MWStake\MediaWiki\Component\Utils\UtilityFactory;

class WikiPageSecurityTrimming extends LookupModifier {

	/** @var UtilityFactory */
	protected $utilityFactory;

	/** @var int[] */
	protected $namespaceIdBlacklist = [];

	/**
	 * @param Lookup $lookup
	 * @param IContextSource $context
	 * @param UtilityFactory|null $utilityFactory
	 */
	public function __construct( $lookup, $context, ?UtilityFactory $utilityFactory ) {
		parent::__construct( $lookup, $context );
		$this->utilityFactory =
			$utilityFactory ?? MediaWikiServices::getInstance()->getService( 'MWStakeCommonUtilsFactory' );
	}

	/**
	 * We can not use a namespace whitelist here and just add a filter,
	 * because all documents that to not have the 'namespace' field (like
	 * e.g. ExternalFile) will also filtered!
	 * Instead we make use of the 'must_not' clause in boolean query. This
	 * clause gets applied on the already filtered result.
	 *
	 * As the Lookup object does not provide a method for this (by design),
	 * we manipulate it through its nature of being an ArrayObject.
	 *
	 * This can be seen as a reference implementation, not as
	 * "hacky workaround"
	 *
	 * See, https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-bool-query.html
	 */
	public function apply() {
		$readableNamespaces = $this->utilityFactory->getReadableNamespacesHelper();

		$this->namespaceIdBlacklist = $readableNamespaces->getRestrictedNamespaces( $this->context->getUser() );
		if ( !empty( $this->namespaceIdBlacklist ) ) {
			$this->lookup->addBoolMustNotTerms( 'namespace', $this->namespaceIdBlacklist );
		}
	}

	public function undo() {
		if ( !empty( $this->namespaceIdBlacklist ) ) {
			$this->lookup->removeBoolMustNotTerms( 'namespace', $this->namespaceIdBlacklist );
		}
	}

	/**
	 * @return string[]
	 */
	public function getSearchTypes() {
		return [
			Backend::QUERY_TYPE_AUTOCOMPLETE,
			Backend::QUERY_TYPE_SEARCH
		];
	}
}
