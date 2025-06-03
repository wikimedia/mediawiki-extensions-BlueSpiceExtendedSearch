<?php

namespace BS\ExtendedSearch\Source\LookupModifier;

use BS\ExtendedSearch\Backend;
use BS\ExtendedSearch\Lookup;
use BS\ExtendedSearch\Plugin\ISearchContextProvider;
use BS\ExtendedSearch\PluginManager;
use MediaWiki\Context\IContextSource;

class SearchContext extends LookupModifier {

	private ?ISearchContextProvider $provider = null;

	/** @var array|null */
	private ?array $searchContext = null;

	/**
	 * @param Lookup $lookup
	 * @param IContextSource $context
	 * @param PluginManager $pluginManager
	 */
	public function __construct(
		$lookup, $context,
		private readonly PluginManager $pluginManager
	) {
		parent::__construct( $lookup, $context );
	}

	public function apply() {
		$this->searchContext = $this->lookup['context'] ?? null;
		unset( $this->lookup['context'] );
		if ( !is_array( $this->searchContext ) || !isset( $this->searchContext['key'] ) ) {
			return;
		}
		$this->provider = $this->getContextProvider( $this->searchContext['key'] );
		if ( !$this->provider ) {
			return;
		}
		$this->provider->applyContext( $this->searchContext['definition'], $this->context->getUser(), $this->lookup );
	}

	public function undo() {
		if ( is_array( $this->searchContext ) ) {
			$this->lookup['context'] = $this->searchContext;
		}
		if ( $this->provider ) {
			$this->provider->undoContext( $this->searchContext['definition'], $this->lookup );
		}
	}

	/**
	 * @return string[]
	 */
	public function getSearchTypes() {
		return [ Backend::QUERY_TYPE_AUTOCOMPLETE, Backend::QUERY_TYPE_SEARCH ];
	}

	/**
	 * @return int
	 */
	public function getPriority() {
		return 1;
	}

	private function getContextProvider( string $key ): ?ISearchContextProvider {
		$plugins = $this->pluginManager->getPluginsImplementing( ISearchContextProvider::class );
		/** @var ISearchContextProvider $plugin */
		foreach ( $plugins as $plugin ) {
			if ( $plugin->getContextKey() === $key ) {
				return $plugin;
			}
		}
		return null;
	}
}
