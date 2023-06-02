<?php

namespace BS\ExtendedSearch\Data\SearchHistory;

use MWStake\MediaWiki\Component\DataStore\PrimaryDatabaseDataProvider;
use Wikimedia\Rdbms\IDatabase;

class PrimaryDataProvider extends PrimaryDatabaseDataProvider {

	/**
	 *
	 * @var Record[]
	 */
	protected $data = [];

	/**
	 *
	 * @var IDatabase
	 */
	protected $db = null;

	/**
	 *
	 * @return array
	 */
	protected function getTableNames() {
		return [ Schema::TABLE_NAME ];
	}

	/**
	 *
	 * @param \stdClass $row
	 */
	protected function appendRowToData( \stdClass $row ) {
		$this->data[] = new Record( (object)[
			Record::ID => (int)$row->{Record::ID},
			Record::USER_ID => (int)$row->{Record::USER_ID},
			Record::HITS => (int)$row->{Record::HITS},
			Record::HITS_APPROXIMATED => (int)$row->{Record::HITS_APPROXIMATED},
			Record::TIMESTAMP => $row->{Record::TIMESTAMP},
			Record::AUTOCORRECTED => (int)$row->{Record::AUTOCORRECTED},
			Record::LOOKUP => $row->{Record::LOOKUP},
			Record::TERM => $this->normalizeTerm( $row->{Record::TERM} ),
		] );
	}

	/**
	 *
	 * @param string $term
	 * @return string
	 */
	protected function normalizeTerm( $term ) {
		// 'term\\.com' -> 'term.com'
		$term = preg_replace( "/(\\\)/", "", $term );
		// '*term*' -> 'term'
		$term = preg_replace( "/(\*)/", "", $term );
		// 'term~0.5' -> 'term'
		$term = preg_replace( "/(~.*)/", "", $term );
		// '"term"' -> 'term'
		$term = preg_replace( "/(\"*)/", "", $term );
		// 'term1%20term2' -> 'term1 term2'
		$term = preg_replace( "/(\%20*)/", " ", $term );
		// 'sch%c3%b6n' -> 'schön'
		$term = preg_replace( "/(\%c3%b6*)/i", "ö", $term );
		// 'sch%c3%b6n' -> 'schön'
		$term = preg_replace( "/(\%c3%96*)/i", "Ö", $term );
		// 't%c3%bcr' -> 'tür'
		$term = preg_replace( "/(\%c3%bc*)/i", "ü", $term );
		// 't%c3%bcr' -> 'tür'
		$term = preg_replace( "/(\%c3%9c*)/i", "Ü", $term );
		// 'b%c3%a4r' -> 'bär'
		$term = preg_replace( "/(\%c3%a4*)/i", "ä", $term );
		// 'b%c3%a4r' -> 'bär'
		$term = preg_replace( "/(\%c3%84*)/i", "Ä", $term );
		// 'spa%c3%9F' -> 'spaß'
		$term = preg_replace( "/(\%c3%9F*)/i", "ß", $term );

		// ' term  ' -> 'term'
		$term = trim( $term );

		return $term;
	}
}
