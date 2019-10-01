<?php

namespace BS\ExtendedSearch\EntityConfig\Collection;

use BlueSpice\ExtendedStatistics\Data\Entity\Collection\Schema;
use BlueSpice\Data\FieldType;
use BlueSpice\ExtendedStatistics\EntityConfig\Collection;
use BS\ExtendedSearch\Entity\Collection\SearchHistory as Entity;

class SearchHistory extends Collection {

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
		return array_merge( parent::get_VarMessageKeys(), [
			Entity::ATTR_TERM => 'bs-extendedsearch-collection-var-searchterm',
			Entity::ATTR_NUMBER_SEARCHED => 'bs-extendedsearch-collection-var-numbersearched',
		] );
	}

	/**
	 *
	 * @return string[]
	 */
	protected function get_Modules() {
		return array_merge( parent::get_Modules(), [
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
		$attributes = array_merge( parent::get_AttributeDefinitions(), [
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
