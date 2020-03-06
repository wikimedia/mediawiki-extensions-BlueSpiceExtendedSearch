<?php

namespace BS\ExtendedSearch\EntityConfig\Collection;

use Config;
use BlueSpice\Services;
use BlueSpice\ExtendedStatistics\Data\Entity\Collection\Schema;
use BlueSpice\Data\FieldType;
use BlueSpice\EntityConfig;
use BlueSpice\ExtendedStatistics\EntityConfig\Collection;
use BS\ExtendedSearch\Entity\Collection\SearchHistory as Entity;

class SearchHistory extends EntityConfig {

	/**
	 *
	 * @param Config $config
	 * @param string $key
	 * @param Services $services
	 * @return EntityConfig
	 */
	public static function factory( $config, $key, $services ) {
		$extension = $services->getService( 'BSExtensionFactory' )->getExtension(
			'BlueSpiceExtendedStatistics'
		);
		if ( !$extension ) {
			return null;
		}
		return new static( new Collection( $config ), $key );
	}

	/**
	 *
	 * @return string
	 */
	protected function get_StoreClass() {
		return $this->getConfig()->get( 'StoreClass' );
	}

	/**
	 *
	 * @return array
	 */
	protected function get_PrimaryAttributeDefinitions() {
		return array_filter( $this->get_AttributeDefinitions(), function ( $e ) {
			return isset( $e[Schema::PRIMARY] ) && $e[Schema::PRIMARY] === true;
		} );
	}

	/**
	 *
	 * @return string
	 */
	protected function get_TypeMessageKey() {
		return 'bs-extendedsearch-collection-type-searchhistory';
	}

	/**
	 *
	 * @return array
	 */
	protected function get_VarMessageKeys() {
		return array_merge( $this->getConfig()->get( 'VarMessageKeys' ), [
			Entity::ATTR_TERM => 'bs-extendedsearch-collection-var-searchterm',
			Entity::ATTR_NUMBER_SEARCHED => 'bs-extendedsearch-collection-var-numbersearched',
		] );
	}

	/**
	 *
	 * @return string[]
	 */
	protected function get_Modules() {
		return array_merge( $this->getConfig()->get( 'Modules' ), [
			'ext.bluespice.extendedsearch.collection.searchhistory',
		] );
	}

	/**
	 *
	 * @return string
	 */
	protected function get_EntityClass() {
		return "\\BS\\ExtendedSearch\\Entity\\Collection\\SearchHistory";
	}

	/**
	 *
	 * @return array
	 */
	protected function get_AttributeDefinitions() {
		$attributes = array_merge( $this->getConfig()->get( 'AttributeDefinitions' ), [
			Entity::ATTR_TERM => [
				Schema::FILTERABLE => true,
				Schema::SORTABLE => true,
				Schema::TYPE => FieldType::STRING,
				Schema::INDEXABLE => true,
				Schema::STORABLE => true,
			],
			Entity::ATTR_NUMBER_SEARCHED => [
				Schema::FILTERABLE => true,
				Schema::SORTABLE => true,
				Schema::TYPE => FieldType::INT,
				Schema::INDEXABLE => true,
				Schema::STORABLE => true,
				Schema::PRIMARY => true,
			],
		] );
		return $attributes;
	}

}
