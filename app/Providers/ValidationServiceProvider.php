<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Validation\Factory;

class ValidationServiceProvider extends ServiceProvider
{

	/**
	 * Bootstrap the application services.
	 *
	 * @return void
	 */
	public function boot()
	{
		$this->registerValidationRules($this->app['validator']);
	}

	protected function registerValidationRules(Factory $validator)
	{
		$methods = \get_class_methods('App\Http\Rules\Validation');
		foreach ($methods as $value) {
			$ruleName = str_replace("validate", "", $value);
			$validator->extend(trim($ruleName), 'App\Http\Rules\Validation@'.$value);
		}
	}

	/**
	 * Register the application services.
	 *
	 * @return void
	 */
	public function register()
	{
		//
	}
}
