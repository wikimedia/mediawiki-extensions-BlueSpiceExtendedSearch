<?php

namespace BS\ExtendedSearch\Source\Crawler;

class File extends Base {
	public function crawl() {
	}

	protected function shouldSkip( $file ) {
		if ( $this->oConfig->has( 'extension_blacklist' ) ) {
			$lcExt = strtolower( $file->getExtension() );
			foreach ( $this->oConfig->get( 'extension_blacklist' ) as $blacklisted ) {
				if ( $lcExt === strtolower( $blacklisted ) ) {
					return true;
				}
			}
		}

		if ( $this->oConfig->has( 'max_size' ) ) {
			if ( $file->getSize() > $this->oConfig->get( 'max_size' ) ) {
				return true;
			}
		}
		return false;
	}
}
