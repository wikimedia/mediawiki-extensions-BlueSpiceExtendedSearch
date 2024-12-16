<?php
namespace BS\ExtendedSearch\MediaWiki\Api;

use BS\ExtendedSearch\Backend;
use BS\ExtendedSearch\ISearchSource;
use MediaWiki\Api\ApiBase;
use MediaWiki\Api\ApiMain;
use MediaWiki\MediaWikiServices;

class TypeStore extends ApiBase {

	/**
	 * @var Backend
	 */
	protected $backend;

	/**
	 * @param ApiMain $mainModule
	 * @param string $moduleName
	 * @param string $modulePrefix
	 */
	public function __construct( ApiMain $mainModule, string $moduleName, string $modulePrefix = '' ) {
		parent::__construct( $mainModule, $moduleName, $modulePrefix );
		$this->backend = MediaWikiServices::getInstance()->getService( 'BSExtendedSearchBackend' );
	}

	public function execute() {
		$result = $this->getResult();
		$types = $this->getTypes();
		$count = count( $types );
		$result->setIndexedTagName( $types, 'results' );
		$result->addValue( null, 'results', $types );
		$result->addValue( null, 'total', $count );
	}

	/**
	 * @return array
	 * @throws \Exception
	 */
	protected function getTypes(): array {
		return array_map( function ( ISearchSource $source ) {
			$key = $source->getTypeKey();
			$msg = $this->getContext()->msg( 'bs-extendedsearch-source-type-' . $key . '-label' );
			if ( $msg->exists() ) {
				return $msg->text();
			}
			return $key;
		}, $this->backend->getSources() );
	}
}
