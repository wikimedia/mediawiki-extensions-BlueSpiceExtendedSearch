<?php

namespace BS\ExtendedSearch\Hook\GetPreferences;

use BlueSpice\Hook\GetPreferences;

class AddUserPreferenceSearchShortCut extends GetPreferences {

	protected function doProcess() {
		$this->preferences['searchShortcut'] = [
			'type' => 'toggle',
			'label-message' => 'bs-extendedsearch-user-pref-shortcut',
			'help-message' => 'bs-extendedsearch-user-pref-shortcut-help',
			'section' => 'extendedsearch/search'
		];

		return true;
	}

}
