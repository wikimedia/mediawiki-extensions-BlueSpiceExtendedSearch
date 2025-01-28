<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace BS\ExtendedSearch;

use BS\ExtendedSearch\Source\Job\UpdateBase;
use Exception;
use MediaWiki\Config\Config;
use MediaWiki\MediaWikiServices;
use MediaWiki\Status\Status;

abstract class ExternalIndex implements IExternalIndex {

	/**
	 *
	 * @var MediaWikiServices
	 */
	protected $services = null;

	/**
	 *
	 * @var Config
	 */
	protected $config = null;

	/**
	 *
	 * @var array
	 */
	protected $document = null;

	/**
	 *
	 * @param MediaWikiServices $services
	 * @param Config $config
	 * @param array $document
	 */
	protected function __construct(
		MediaWikiServices $services, Config $config, array $document
	) {
		$this->services = $services;
		$this->config = $config;
		$this->document = $document;
	}

	/**
	 *
	 * @param MediaWikiServices $services
	 * @param Config $config
	 * @param array $document
	 * @return IExternalIndex
	 */
	public static function factory(
		MediaWikiServices $services, Config $config, array $document
	) {
		return new static( $services, $config, $document );
	}

	/**
	 *
	 * @param string $action
	 * @return Status
	 */
	public function push( $action = UpdateBase::ACTION_UPDATE ) {
		if ( $this->skipProcessing( $action ) ) {
			return Status::newGood();
		}
		$mappedFields = [];
		foreach ( $this->getMapping() as $map => $field ) {
			$value = $this->getMappedValue( $map, null, $action );
			if ( $value === null && $this->skipNullValue( $action ) ) {
				continue;
			}
			$mappedFields[$field] = $value;
		}
		try {
			$status = $this->doPush( $mappedFields, $action );
		} catch ( Exception $e ) {
			$status = Status::newFatal( $e->getMessage() );
		}
		return $status;
	}

	/**
	 * @param array $mappedFields
	 * @param string $action
	 * @return Status
	 */
	abstract protected function doPush( array $mappedFields, $action );

	/**
	 *
	 * @param string $map
	 * @param mixed $default
	 * @param string $action
	 * @return mixed value
	 */
	protected function getMappedValue( $map, $default, $action ) {
		return isset( $this->document[$map] ) ? $this->document[$map] : $default;
	}

	/**
	 *
	 * @param string $action
	 * @return bool
	 */
	protected function skipProcessing( $action ) {
		return false;
	}

	/**
	 *
	 * @param string $action
	 * @return bool
	 */
	protected function skipNullValue( $action ) {
		return true;
	}

}
