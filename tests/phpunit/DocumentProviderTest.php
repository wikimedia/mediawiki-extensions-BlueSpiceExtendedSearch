<?php

namespace BS\ExtendedSearch\Tests;

use MediaWiki\Title\Title;

/**
 * @group Database
 * @group BlueSpice
 * @group BlueSpiceExtensions
 * @group BlueSpiceExtendedSearch
 */
class DocumentProviderTest extends \MediaWikiIntegrationTestCase {

	/**
	 * @covers \BS\ExtendedSearch\Source\DocumentProvider\Base::getDocumentData
	 */
	public function testBaseDocumentProvider() {
		$oDP = new \BS\ExtendedSearch\Source\DocumentProvider\Base();
		$sTestUri = 'http://some.server.tld/with/a/file.html';
		$sTestUriMD5 = md5( $sTestUri );
		$aDC = $oDP->getDocumentData( $sTestUri, $sTestUriMD5, null );

		$this->assertNotEmpty( $aDC['id'] );
		$this->assertEquals( $sTestUriMD5, $aDC['id'] );
	}

	/**
	 * @covers \BS\ExtendedSearch\Source\DocumentProvider\WikiPage::getDocumentData
	 */
	public function testWikiPageDocumentProvider() {
		$oDP = new \BS\ExtendedSearch\Source\DocumentProvider\WikiPage(
			$this->getServiceContainer()->getHookContainer(),
			$this->getServiceContainer()->getContentRenderer(),
			$this->getServiceContainer()->getRevisionLookup(),
			$this->getServiceContainer()->getPageProps(),
			$this->getServiceContainer()->getParser(),
			$this->getServiceContainer()->getRedirectLookup(),
			$this->getServiceContainer()->getUserFactory(),
			$this->getServiceContainer()->getRevisionRenderer()
		);

		$title = Title::makeTitle( NS_HELP, 'Dummy title' );
		$this->insertPage( $title, 'Dummy text' );
		$oWikiPage = $this->getServiceContainer()->getWikiPageFactory()->newFromTitle( $title );
		$testId = $oWikiPage->getTitle()->getNamespace() . '|' . $oWikiPage->getTitle()->getDBkey();
		$testIdMd5 = md5( $testId );

		$aDC = $oDP->getDocumentData( $testId, $testIdMd5, $oWikiPage );
		$this->assertNotEmpty( $aDC['id'] );
		$this->assertEquals( $testIdMd5, $aDC['id'] );
		$this->assertEquals( $oWikiPage->getTitle()->getBaseText(), $aDC['basename'] );
		$this->assertEquals( 'text/x-wiki', $aDC['mime_type'] );
		$this->assertEquals( 'wiki', $aDC['extension'] );
		$this->assertEquals( $oWikiPage->getTitle()->getNamespace(), $aDC['namespace'] );
		$this->assertEquals( $oWikiPage->getTitle()->getNsText(), $aDC['namespace_text'] );
	}

	/**
	 * @covers \BS\ExtendedSearch\Source\DocumentProvider\File::getDocumentData
	 */
	public function testFileDocumentProvider() {
		$oDP = new \BS\ExtendedSearch\Source\DocumentProvider\File(
			$this->getServiceContainer()->getMimeAnalyzer()
		);

		$oFile = new \SplFileInfo( __DIR__ . '/data/Test.txt' );
		$sTestUri = 'file:///' . $oFile->getPathname();
		$sTestUriMD5 = md5( $sTestUri );

		$aDC = $oDP->getDocumentData( $sTestUri, $sTestUriMD5, $oFile );
		$this->assertNotEmpty( $aDC['id'] );
		$this->assertEquals( $sTestUriMD5, $aDC['id'] );
		$this->assertEquals( $oFile->getBasename(), $aDC['basename'] );
		$this->assertEquals( 'text/plain', $aDC['mime_type'] );
		$this->assertEquals( 'txt', $aDC['extension'] );
		$this->assertEquals( 'This is a test', base64_decode( $aDC['the_file'] ) );
	}
}
