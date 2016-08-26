<?php

namespace BS\ExtendedSearch\Source;

class WikiPages extends DecoratorBase {
	public function getTypeKey() {
		return 'wikipage';
	}

	public function getCrawler() {
		return new Crawler\WikiPage();
	}

	public function makeMappingPropertyConfig() {
		$aMapping = $this->oDecoratedSource->makeMappingPropertyConfig();
		$aMapping += [
			'namespace' => array(
				'type' => 'integer',
				'include_in_all' => false
			),
		];
		return $aMapping;
	}

	/**
	 *
	 * @return \BS\ExtendedSearch\Source\Updater\WikiPage
	 */
	public function getUpdater() {
		return new Updater\WikiPage( $this->oDecoratedSource );
	}
}