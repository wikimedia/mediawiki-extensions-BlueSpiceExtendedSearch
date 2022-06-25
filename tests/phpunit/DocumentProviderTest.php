<?php

namespace BS\ExtendedSearch\Tests;

/**
 * @group Database
 * @group BlueSpice
 * @group BlueSpiceExtensions
 * @group BlueSpiceExtendedSearch
 */
class DocumentProviderTest extends \MediaWikiIntegrationTestCase {

	/**
	 * @return bool
	 * @since 1.18
	 */
	public function needsDB() {
		// If the test says it uses database tables, it needs the database
		return true;
	}

	/**
	 * @group Database
	 * @covers \BS\ExtendedSearch\Source\DocumentProvider\Base::getDataConfig
	 */
	public function testBaseDocumentProvider() {
		$oDP = new \BS\ExtendedSearch\Source\DocumentProvider\Base();
		$sTestUri = 'http://some.server.tld/with/a/file.html';
		$sTestUriMD5 = md5( $sTestUri );
		$aDC = $oDP->getDataConfig( $sTestUri, null );

		$this->assertNotEmpty( $aDC['id'] );
		$this->assertEquals( $sTestUriMD5, $aDC['id'] );
	}

	/**
	 * @group Database
	 * @covers \BS\ExtendedSearch\Source\DocumentProvider\WikiPage::getDataConfig
	 */
	public function testWikiPageDocumentProvider() {
		$oDP = new \BS\ExtendedSearch\Source\DocumentProvider\WikiPage(
			new \BS\ExtendedSearch\Source\DocumentProvider\Base()
		);

		$title = \Title::makeTitle( NS_HELP, 'Dummy title' );
		$this->insertPage( $title, 'Dummy text' );
		$oWikiPage = $this->getServiceContainer()->getWikiPageFactory()->newFromTitle( $title );
		$sTestUri = $oWikiPage->getTitle()->getCanonicalURL();
		$sTestUriMD5 = md5( $sTestUri );

		$aDC = $oDP->getDataConfig( $sTestUri, $oWikiPage );
		$this->assertNotEmpty( $aDC['id'] );
		$this->assertEquals( $sTestUriMD5, $aDC['id'] );
		$this->assertEquals( $oWikiPage->getTitle()->getBaseText(), $aDC['basename'] );
		$this->assertEquals( 'text/x-wiki', $aDC['mime_type'] );
		$this->assertEquals( 'wiki', $aDC['extension'] );
		$this->assertEquals( $oWikiPage->getTitle()->getNamespace(), $aDC['namespace'] );
		$this->assertEquals( $oWikiPage->getTitle()->getNsText(), $aDC['namespace_text'] );
	}

	/**
	 * @group Database
	 * @covers \BS\ExtendedSearch\Source\DocumentProvider\File::getDataConfig
	 */
	public function testFileDocumentProvider() {
		$oDP = new \BS\ExtendedSearch\Source\DocumentProvider\File(
			new \BS\ExtendedSearch\Source\DocumentProvider\Base()
		);

		$oFile = new \SplFileInfo( __DIR__ . '/data/Test.txt' );
		$sTestUri = 'file:///' . $oFile->getPathname();
		$sTestUriMD5 = md5( $sTestUri );

		$aDC = $oDP->getDataConfig( $sTestUri, $oFile );
		$this->assertNotEmpty( $aDC['id'] );
		$this->assertEquals( $sTestUriMD5, $aDC['id'] );
		$this->assertEquals( $oFile->getBasename(), $aDC['basename'] );
		$this->assertEquals( 'text/plain', $aDC['mime_type'] );
		$this->assertEquals( 'txt', $aDC['extension'] );
		$this->assertEquals( 'This is a test', base64_decode( $aDC['the_file'] ) );
	}
}
