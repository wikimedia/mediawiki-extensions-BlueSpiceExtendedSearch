<?php

namespace BS\ExtendedSearch\Source\Job;

use Exception;
use Status;
use BlueSpice\Services;
use BS\ExtendedSearch\Backend;
use BS\ExtendedSearch\Source\Base;
use BS\ExtendedSearch\ExternalIndexFactory;
use BS\ExtendedSearch\IExternalIndex;

abstract class UpdateBase extends \Job {
	const ACTION_DELETE = 'delete';
	const ACTION_UPDATE = 'update';

	protected $action = self::ACTION_UPDATE;

	protected $sBackendKey = 'local';
	protected $sSourceKey = '';

	/**
	 * Run the job
	 * @return bool Success
	 */
	public function run() {
		if ( $this->skipProcessing() ) {
			return true;
		}
		$dC = $this->doRun();
		if ( !empty( $dC ) && is_array( $dC ) ) {
			$status = $this->pushToExternal( $dC );
			if ( !$status->isOK() ) {
				$this->setLastError( $status->getMessage() );
			}
		}
		return true;
	}

	abstract protected function isDeletion();

	/**
	 *
	 * @return Backend
	 */
	protected function getBackend() {
		return Backend::instance( $this->getBackendKey() );
	}

	/**
	 *
	 * @return Base
	 * @throws Exception
	 */
	protected function getSource() {
		return $this->getBackend()->getSource( $this->getSourceKey() );
	}

	/**
	 *
	 * @return string
	 */
	protected function getBackendKey() {
		if( isset( $this->params['backend'] ) ) {
			return $this->params['backend'];
		}
		return $this->sBackendKey;
	}

	/**
	 *
	 * @return string
	 */
	protected function getSourceKey() {
		if( isset( $this->params['source'] ) ) {
			return $this->params['source'];
		}
		return $this->sSourceKey;
	}

	/**
	 * Execute this job and return the data config as an array
	 * @return array
	 */
	abstract protected function doRun();

	/**
	 *
	 * @param array $dC
	 * @return Status
	 */
	protected function pushToExternal( $dC ) {
		$status = Status::newGood();
		$dC[IExternalIndex::FIELD_SOURCE_KEY] = $this->getSourceKey();
		$dC[IExternalIndex::FIELD_BACKEND_KEY] = $this->getBackendKey();
		$dC[IExternalIndex::FIELD_INDEX_NAME] = $this->getBackend()->getConfig()->get(
			'index'
		);
		foreach( $this->getExternalIndexFactory()->getTypes() as $type ) {
			try {
				$externalIndex = $this->getExternalIndexFactory()->getExternalIndex( $type, $dC );
				if ( !$externalIndex ) {
					continue;
				}
				$status->merge( $externalIndex->push(
					$this->isDeletion() ? static::ACTION_DELETE : static::ACTION_UPDATE
				) );
			} catch( Exception $e ) {
				$status->error( $e );
			}
		}
		return $status;
	}

	/**
	 *
	 * @return ExternalIndexFactory
	 */
	protected function getExternalIndexFactory() {
		return Services::getInstance()->getService(
			'BSExtendedSearchExternalIndexFactory'
		);
	}

	/**
	 *
	 * @return bool
	 */
	protected function skipProcessing() {
		return false;
	}

}
