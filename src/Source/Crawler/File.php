<?php

namespace BS\ExtendedSearch\Source\Crawler;

class File extends Base {

	/**
	 *
	 * @param \SplFileInfo $file
	 * @return bool
	 */
	protected function shouldSkip( $file ) {
		if ( $this->sourceConfig->has( 'extension_blacklist' ) ) {
			$lcExt = strtolower( $file->getExtension() );
			foreach ( $this->sourceConfig->get( 'extension_blacklist' ) as $blacklisted ) {
				if ( $lcExt === strtolower( $blacklisted ) ) {
					return true;
				}
			}
		}

		if ( $this->sourceConfig->has( 'max_size' ) ) {
			if ( $file->getSize() > $this->sourceConfig->get( 'max_size' ) ) {
				return true;
			}
		}
		return false;
	}
}
