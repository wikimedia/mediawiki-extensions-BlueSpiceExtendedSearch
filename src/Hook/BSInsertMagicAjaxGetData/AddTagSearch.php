<?php

namespace BS\ExtendedSearch\Hook\BSInsertMagicAjaxGetData;

use BlueSpice\InsertMagic\Hook\BSInsertMagicAjaxGetData;

class AddTagSearch extends BSInsertMagicAjaxGetData {
	protected function skipProcessing() {
		return $this->type != 'tags';
	}

	protected function doProcess() {
		$extension = $this->getServices()
			->getBSExtensionFactory()
			->getExtension( 'BlueSpiceExtendedSearch' );

		$oDescriptor = new \stdClass();
		$oDescriptor->id = 'bs:tagsearch';
		$oDescriptor->type = 'tag';
		$oDescriptor->name = 'tagsearch';
		$oDescriptor->desc = $this->msg( 'bs-extendedsearch-tagsearch-extension-description' )->text();
		$oDescriptor->mwvecommand = 'tagsearchCommand';
		$oDescriptor->code = '<bs:tagsearch />';
		$oDescriptor->previewable = false;
		$oDescriptor->helplink = $extension->getUrl();
		$this->response->result[] = $oDescriptor;

		return true;
	}

}
