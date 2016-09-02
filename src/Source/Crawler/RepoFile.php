<?php

namespace BS\ExtendedSearch\Source\Crawler;

class RepoFile extends WikiPage {
	protected $sJobClass = 'BS\ExtendedSearch\Source\Job\UpdateRepoFile';

	public function makeQueryConditions() {
		return [
			'page_namespace' => NS_FILE
		];
	}
}