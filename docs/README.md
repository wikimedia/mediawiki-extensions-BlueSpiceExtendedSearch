# Setup and installation

## OpenSearch
Requires OpenSearch (2.7+) to be running.
- See https://opensearch.org/docs/latest/install-and-configure/install-opensearch/index/ for installation instructions.
- Install `ingest-attachment` plugin in correct version for out OS version.
(https://opensearch.org/docs/latest/install-and-configure/plugins/)
- **MUST** setup a valid SSL certificate to allow secure connection to OS instance.
## Extension
- **MUST** set username and password for accessing OS (defaults to `admin:admin`, strongly recommended to change it).
```php
     $bsgESBackendUsername = 'username';
     $bsgESBackendPassword = 'password';
```

- If needed, configure host, port etc. (see `extension.json` for available config variables)


# Run Unit Tests on terminal
    php tests/phpunit/phpunit.php extensions/BlueSpiceExtendedSearch/tests/phpunit/

# Create/Update index
    // initialize indices
    php extensions/BlueSpiceExtendedSearch/maintenance/initBackends.php
    // crawl the sources
    php extensions/BlueSpiceExtendedSearch/maintenance/rebuildIndex.php
    // Index documents
    php maintenance/runJobs.php

# Extending indices

## Plugins
Search infrastructure allows other extensions to implement plugins that will extend or modify
the normal behaviour of the search.

Plugin must implement `BS\ExtendedSearch\Plugin\ISearchPlugin` interface.
Depending on the needs of the plugin, it should implement additional interfaces
- `BS\ExtendedSearch\Plugin\IMappingModifier` - to modify the mapping and index settings
- `BS\ExtendedSearch\Plugin\IDocumentDataModifier` - to modify the data being indexed (also adding new fields)
- `BS\ExtendedSearch\Plugin\ILookupModifierProvider` - to provide the list of `BS\ExtendedSearch\Plugin\ILookupModifier` instances
- `BS\ExtendedSearch\Plugin\IFormattingModifier` - to modify formatting of fulltext and AC results
- `BS\ExtendedSearch\Plugin\IFilterModifier` - to modify filters available on the client-side
- `BS\ExtendedSearch\IPostProcessingProvider` - to provider the list of `BS\ExtendedSearch\Plugin\IPostProcessor` instances

Register plugins:
- In manifest registry, in attribute `PluginRegistry` as `plugin-key` => `OF_spec`, or
- Using a hook: `BSExtendedSearchRegisterPlugin`

## Hooks
- Replace revision used by the `wikipage` source - `BSExtendedSearchWikiPageFetchRevision` hook
- Skip indexing of documents - `BSExtendedSearchIndexDocumentSkip` hook
- Get file version to index by the `repofile` source - `BSExtendedSearchRepoFileGetFile` hook


# Usefull OS URLS
* https://localhost:9200/_mappings?pretty
* https://localhost:9200/_cat/indices?v
