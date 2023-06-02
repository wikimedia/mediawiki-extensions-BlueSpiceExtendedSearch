<?php

namespace BS\ExtendedSearch;

use BS\ExtendedSearch\Plugin\IPostProcessor;

interface IPostProcessorProvider {

	/**
	 * @param PostProcessor $postProcessorRunner
	 *
	 * @return IPostProcessor[]
	 */
	public function getPostProcessors( PostProcessor $postProcessorRunner ): array;
}
