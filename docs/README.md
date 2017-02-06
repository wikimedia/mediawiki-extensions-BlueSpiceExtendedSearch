# Setup and installation

Execute

    composer install

within the installation directory of `BlueSpiceExtendedSearch` to resolve dependencies.

Download Elasticsearch 5.x from https://www.elastic.co/downloads/elasticsearch
and install the plugin "mapper-attachments" by executing

    bin/elasticsearch-plugin install mapper-attachments

from the Elasticsearch directory.

Start the service using

    bin\elasticsearch

# Configuration
## External File Source

    $bsgESBackends['local']['sources']['externalfile']['args'] = array(
	    'paths' => array(
		    '\\\\some\\network\\share' => 'file:///S:\\network\\share',
		    '/mnt/some/network/share' => 'file:///network/share'
	   )
    );

## Linked File Source

    $bsgESBackends['local']['sources']['linkedfile']['args'] = array(
	    'paths' => array(
		    '\\\\some\\network\\share' => 'file:///S:\\network\\share',
		    '/mnt/some/network/share' => 'file:///network/share'
	    )
    );

# Run Unit Tests on terminal
    php tests/phpunit/phpunit.php extensions/BlueSpiceExtendedSearch/tests/phpunit/

# Create/Update index
    php extensions/BlueSpiceExtendedSearch/maintenance/rebuildIndex.php
    php maintenance/runJobs.php

# Usefull ES URLS
* http://localhost:9200/_mappings?pretty
* http://localhost:9200/_cat/indices?v

# TODO
* Integrate with $wgSearchType