<?php namespace Illuminate\Support\Facades;

use Illuminate\Support\Facades\Session;
class View extends Facade {

	/**
	 * Get the registered name of the component.
	 *
	 * @return string
	 */
	protected static function getFacadeAccessor() { return 'view'; }
}