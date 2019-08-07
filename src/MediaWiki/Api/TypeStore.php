<?php
namespace BS\ExtendedSearch\MediaWiki\Api;

class TypeStore extends \BSApiExtJSStoreBase {
	/**
	 * @param string $sQuery Potential query provided by ExtJS component.
	 * This is some kind of preliminary filtering. Subclass has to decide if
	 * and how to process it
	 * @return array - Full list of of data objects. Filters, paging, sorting
	 * will be done by the base class
	 */
	protected function makeData( $sQuery = '' ) {
		$aData = [];
		$backend = \BS\ExtendedSearch\Backend::instance();
		$sources = $backend->getSources();
		foreach ( $sources as $source ) {
			$aData[] = $source->getTypeKey();
		}

		return $aData;
	}
}
