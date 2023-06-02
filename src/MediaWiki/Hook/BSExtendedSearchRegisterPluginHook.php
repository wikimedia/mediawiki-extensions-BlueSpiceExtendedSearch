<?php

namespace BS\ExtendedSearch\MediaWiki\Hook;

interface BSExtendedSearchRegisterPluginHook {
	/**
	 * @param array &$pluginInstances
	 *
	 * @return bool
	 */
	public function onBSExtendedSearchRegisterPlugin( &$pluginInstances );
}
