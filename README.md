Laravel 4 Apache Solr
=====================

[![Build Status](https://travis-ci.org/davispeixoto/Laravel-4-Solr.svg?branch=master)](https://travis-ci.org/davispeixoto/Laravel-4-Solr)

This Laravel 4 package provides an interface for consuming (querying) [Apache Solr](https://lucene.apache.org/solr/) via its restful interface.

Installation
------------

Begin by installing this package through Composer. Edit your project's `composer.json` file to require `davispeixoto/laravel-4-solr`.

	"require": {
		"laravel/framework": "4.1.*",
		"davispeixoto/laravel-4-solr": "1.0.*"
	}

Next, update Composer from the Terminal:

    composer update

Once this operation completes, still in Terminal run:

	php artisan config:publish davispeixoto/laravel-4-solr
	
Update the settings in the generated `app/config/packages/davispeixoto/laravel-4-solr` configuration file with solr endpoint, port, output format.

Finally add the service provider. Open `app/config/app.php`, and add a new item to the providers array.

    'Davispeixoto\Laravel4Solr\Laravel4SolrServiceProvider'

That's it! You're all set to go. Just use:

    Route::get('/test', function() {
		try {
	    	Solr::setCore('products');
	    	Solr::setFQ('color' , 'blue*');
	    	Solr::outputFormat('json');
	    	$results = Solr::getResults();
	    	echo print_r($results , true);
		} catch (Exception $e) {
			Log::error($e->getMessage());
			die($e->getMessage() . $e->getTraceAsString());
		}
    });

### License

This library is open-sourced software licensed under the [MIT license](http://opensource.org/licenses/MIT)

### Versioning

This projetct follows the [Semantic Versioning](http://semver.org/)
