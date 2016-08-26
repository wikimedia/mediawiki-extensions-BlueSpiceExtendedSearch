<?php

namespace BS\ExtendedSearch;

class Indices {

	/**
	 *
	 * @param string $sKey
	 * @return \BS\ExtendedSearch\Index
	 * @throws Exception
	 */
	public static function factory( $sKey ) {
		$aConfigs = self::getIndexConfigs();
		if( !isset($aConfigs[$sKey]['class']) ) {
			throw new \Exception();
		}
		return new $aConfigs[$sKey]['class']( $aConfigs[$sKey] );
	}

	/**
	 *
	 * @return \BS\ExtendedSearch\Index[]
	 */
	public static function factoryAll() {
		$aConfigs = self::getIndexConfigs();

		$aInstances = [];
		foreach( $aConfigs as $sKey => $aConfig ) {
			$aInstances[$sKey] = new $aConfig['class']( $aConfig );
		}

		return $aInstances;
	}

	protected static function getIndexConfigs() {
		$config = \ConfigFactory::getDefaultInstance()->makeConfig( 'bsgES' );
		return $config->get("Indizes");
	}
}