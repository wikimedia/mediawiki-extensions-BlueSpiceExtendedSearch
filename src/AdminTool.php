<?php

namespace BS\ExtendedSearch;

use BlueSpice\IAdminTool;

class AdminTool implements IAdminTool {

	public function getURL() {
		$tool = \SpecialPage::getTitleFor( 'BSSearchAdmin' );
		return $tool->getLocalURL();
	}

	public function getDescription() {
		return wfMessage( 'bssearchadmin-desc' );
	}

	public function getName() {
		return wfMessage( 'bssearchadmin' );
	}

	public function getClasses() {
		$classes = array(
			'bs-icon-magnifying-glass'
		);

		return implode( ' ', $classes );
	}

	public function getDataAttributes() {
	}

	public function getPermissions() {
		$permissions = array(
			'extendedsearchadmin-viewspecialpage'
		);
		return $permissions;
	}

}