<?php

namespace Davispeixoto\Laravel4Solr;

use Illuminate\Support\ServiceProvider;

class Laravel4SolrServiceProvider extends ServiceProvider {

	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
	protected $defer = false;

	/**
	 * Bootstrap the application events.
	 *
	 * @return void
	 */
	public function boot()
	{
		$this->package('davispeixoto/laravel-4-solr');
	}

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		$this->app->booting(function() {
			$loader = \Illuminate\Foundation\AliasLoader::getInstance();
			$loader->alias('Solr', 'Davispeixoto\Laravel4Solr\Facades\Solr');
		});
		
		$this->app['laravel-4-solr'] = $this->app->share(function($app) {
			return new Solr($app['config']);
		});
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return array('laravel-4-solr');
	}

}
