<?php

namespace BS\ExtendedSearch\Source\QueryProcessor;

class WikiPageSecurityTrimming extends Base {

	/**
	 *
	 * @param \Elastica\Query $oQuery
	 * @param \Elastica\QueryBuilder $oQueryBuilder
	 */
	public function process( &$oQuery, $oQueryBuilder ) {


		$aNamespaceIds = $this->oContext->getLanguage()->getNamespaceIds();
		$aNamespaceIdBlacklist = [];
		foreach( $aNamespaceIds as $sNsText => $iNsId ) {
			if( !\Title::makeTitle( $iNsId, 'Dummy')->userCan( 'read' ) ) {
				$aNamespaceIdBlacklist[] = $iNsId;
			}
		}

		
	}
}