<?php

use BS\ExtendedSearch\Source\Job\UpdateWikiPage;
use MediaWiki\Maintenance\Maintenance;
use MediaWiki\MediaWikiServices;
use MediaWiki\Title\Title;

$IP = dirname( dirname( dirname( __DIR__ ) ) );

require_once "$IP/maintenance/Maintenance.php";

/**
 * Maintenance script to update wikipage index
 *
 * @ingroup Maintenance
 */
class UpdateWikiPageIndex extends Maintenance {

	public function __construct() {
		parent::__construct();
		$this->addDescription( "Update wikipage index for specified pages" );

		$this->requireExtension( "BlueSpiceExtendedSearch" );
		$this->addOption( 'src', 'File containing the list of pages to index', false, true );
	}

	/**
	 * @return bool|void|null
	 */
	public function execute() {
		$file = realpath( trim( $this->getOption( 'src' ) ) );
		if ( !file_exists( $file ) ) {
			$this->error( "Cannot open $file" . PHP_EOL );
			return;
		}

		$content = file_get_contents( $file );
		if ( $content === false ) {
			$this->error( "Source file $file cannot be read!" . PHP_EOL );
			return;
		}

		if ( substr( $file, -3 ) === 'xml' ) {
			$this->output( 'Parsing XML file... ' . PHP_EOL );
			$lines = $this->getXMLPages( $content );
		} else {
			$lines = explode( "\n", $content );
		}

		if ( count( $lines ) === 0 ) {
			$this->output( 'No pages to index found!' . PHP_EOL );
			return;
		}

		$validCount = 0;
		$jobs = [];
		foreach ( $lines as $line ) {
			$page = trim( $line );
			$title = Title::newFromText( $page );
			if ( !$title instanceof Title ) {
				continue;
			}
			$validCount++;
			$jobs[] = new UpdateWikiPage( $title );
		}
		MediaWikiServices::getInstance()->getJobQueueGroup()->push( $jobs );

		$this->output( 'Created jobs to update ' . $validCount . ' page(s)' . PHP_EOL );

		$ip = $GLOBALS['IP'];
		$this->output( "You should now run 'php $ip/maintenance/runJobs.php'" . PHP_EOL );
	}

	/**
	 * @param string $content
	 * @return array
	 */
	private function getXMLPages( $content ) {
		$pages = [];
		$xml = simplexml_load_string( $content );
		foreach ( $xml->page as $pageItem ) {
			if ( !$pageItem instanceof SimpleXMLElement ) {
				continue;
			}
			if ( property_exists( $pageItem, 'title' ) ) {
				$pages[] = $pageItem->title;
			}
		}

		return $pages;
	}
}

$maintClass = UpdateWikiPageIndex::class;
require_once RUN_MAINTENANCE_IF_MAIN;
