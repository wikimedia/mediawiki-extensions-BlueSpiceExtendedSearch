<?php

namespace BS\ExtendedSearch\Plugin;

interface ILookupModifier {
	/**
	 * @deprecated since version 3.1.13 - already defined in
	 * \BS\ExtendedSearch\Backend::QUERY_TYPE_SEARCH
	 */
	public const TYPE_SEARCH = 'search';

	/**
	 * @deprecated since version 3.1.13 - already defined in
	 * \BS\ExtendedSearch\Backend::QUERY_TYPE_AUTOCOMPLETE
	 */
	public const TYPE_AUTOCOMPLETE = 'autocomplete';

	/**
	 * Gets how far down should the LM be executed
	 *
	 * Allowed values: 1-100
	 *
	 * @return int
	 */
	public function getPriority();

	/**
	 * Modify the lookup object
	 *
	 * @return void
	 */
	public function apply();

	/**
	 * Remove any sensitive Lookup parts previously added
	 * by this modifier, in case they should not be sent to client
	 */
	public function undo();

	/**
	 * @return string[]
	 */
	public function getSearchTypes();
}
