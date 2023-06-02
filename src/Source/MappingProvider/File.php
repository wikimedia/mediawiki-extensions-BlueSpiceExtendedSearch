<?php

namespace BS\ExtendedSearch\Source\MappingProvider;

class File extends Base {

	/**
	 * We don't need the base64 code in the index, just the extracted data
	 * @see http://stackoverflow.com/questions/29982129/how-to-not-store-attachment-content-using-elastica
	 * @return array
	 */
	public function getSourceConfig(): array {
		$parentConfig = parent::getSourceConfig();
		$value = [ 'the_file' ];
		$excludes = isset( $parentConfig['excludes'] ) ? $parentConfig['excludes'] : [];
		if ( !is_array( $excludes ) ) {
			$excludes = [ $excludes ];
		}
		$parentConfig['excludes'] = array_merge( $excludes, $value );

		return $parentConfig;
	}

	/**
	 *
	 * @return array
	 */
	public function getPropertyConfig(): array {
		$config = parent::getPropertyConfig();
		return array_merge( $config, [
			'filename' => [
				'type' => 'text',
				'copy_to' => [ 'congregated', 'suggestions' ],
				// required in order to be sortable
				'fielddata' => true
			],
			'the_file' => [
				'type' => 'binary'
			],
			'source_file_path' => [
				'type' => 'text'
			],
		] );
	}
}
