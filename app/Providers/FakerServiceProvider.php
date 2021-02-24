<?php

namespace App\Providers;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;

class FakerServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('Faker', function ($app) {
            $faker = \Faker\Factory::create();
            $newClass = new class($faker) extends \Faker\Provider\Base {
                public function name($nbWords = 4)
                {
                    $sentence = '';
                    for ($i = 0; $i < $nbWords; $i++) {
                        $sentence .= static::randomElement(Config::get('myanmar-data.name'));
                    }
                    return $sentence;
                }
            };

            $faker->addProvider($newClass);
            return $faker;
        });
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
