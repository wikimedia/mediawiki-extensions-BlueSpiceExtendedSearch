<?php
namespace BS\ExtendedSearch\Hook\BSUEModulePDFBeforeCreatePDF;

use BlueSpice\UEModulePDF\Hook\BSUEModulePDFBeforeCreatePDF;
use DomXPath;

class RemoveTagSearch extends BSUEModulePDFBeforeCreatePDF {

	/**
	 *
	 * @return bool
	 */
	protected function doProcess() {
		$finder = new DomXPath( $this->DOM );
		$forms = $finder->query( "//*[contains(@class, 'bs-tagsearch-form')]" );
		foreach ( $forms as $form ) {
			$form->parentNode->removeChild( $form );
		}
		return true;
	}

}
