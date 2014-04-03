<?php namespace Davispeixoto\Laravel4Solr\Facades;

use Illuminate\Support\Facades\Facade;

class Solr extends Facade {
	/**
	 * Get the registered name of the component.
	 *
	 * @return string
	 */
	protected static function getFacadeAccessor() {
		return 'laravel-4-solr';
	}
}