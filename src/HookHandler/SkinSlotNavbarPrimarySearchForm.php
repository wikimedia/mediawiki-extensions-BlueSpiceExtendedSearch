<?php

namespace BS\ExtendedSearch\HookHandler;

use BS\ExtendedSearch\ExtendedSearchForm;
use MWStake\MediaWiki\Component\CommonUserInterface\Hook\MWStakeCommonUIRegisterSkinSlotComponents;

class SkinSlotNavbarPrimarySearchForm implements MWStakeCommonUIRegisterSkinSlotComponents {

	/**
	 * @inheritDoc
	 */
	public function onMWStakeCommonUIRegisterSkinSlotComponents( $registry ): void {
		$registry->register(
			'NavbarPrimarySearchForm',
			[
				'bs-extended-search' => [
					'factory' => static function () {
						return new ExtendedSearchForm();
					}
				]
			]
		);
	}
}
