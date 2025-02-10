<?php

namespace BS\ExtendedSearch;

use MediaWiki\Html\TemplateParser;
use MediaWiki\MediaWikiServices;
use MediaWiki\Message\Message;
use MediaWiki\SpecialPage\SpecialPage;
use MWStake\MediaWiki\Component\CommonUserInterface\Component\Literal;
use MWStake\MediaWiki\Component\CommonUserInterface\IRestrictedComponent;

class ExtendedSearchForm extends Literal implements IRestrictedComponent {

	/**
	 *
	 */
	public function __construct() {
		parent::__construct(
			'bs-extended-search-form',
			$this->getTemplateHtml()
		);
	}

	/**
	 *
	 * @return array
	 */
	public function getPermissions(): array {
		return [ 'read' ];
	}

	/**
	 *
	 * @return array
	 */
	private function getParams(): array {
		$services = MediaWikiServices::getInstance();
		$config = $services->getMainConfig();
		$specialSearch = SpecialPage::getTitleFor( 'BSSearchCenter' );

		$params = [
			'form-id' => 'bs-extendedsearch-box',
			'form-class' => 'form-inline input-group',
			'form-action' => $config->get( 'Script' ),
			'form-method' => 'GET',
			'button-id' => 'mw-searchButton',
			'button-class' => 'input-group-text bi bi-search',
			'button-title' => Message::newFromKey( 'bs-extendedsearch-search-button-title' )->text(),
			'button-aria-label' => Message::newFromKey( 'bs-extendedsearch-search-button-aria-label' )->text(),
			'button-type' => 'submit',
			'input-id' => 'bs-extendedsearch-input',
			'input-class' => 'form-control input_pass',
			'input-type' => 'text',
			'input-placeholder' => Message::newFromKey( 'bs-extendedsearch-search-input-placeholder' )->text(),
			'input-aria-label' => Message::newFromKey( 'bs-extendedsearch-search-input-aria-label' )->text(),
			'input-autocomplete' => 'off',
			'input-name' => 'raw_term',
			'page-name' => $specialSearch->getPrefixedText(),
			'field-name' => 'fulltext',
			'field-value' => '1'
		];
		return $params;
	}

	/**
	 *
	 * @return string
	 */
	private function getTemplateHtml(): string {
		$templateParser = new TemplateParser(
			dirname( __DIR__ ) . '/resources/templates'
		);
		$html = $templateParser->processTemplate(
			'ExtendedSearchForm',
			$this->getParams()
		);
		return $html;
	}

	/**
	 *
	 * @inheritDoc
	 */
	public function getRequiredRLModules(): array {
		return [ 'ext.bluespice.extendedsearch.searchform' ];
	}

}
