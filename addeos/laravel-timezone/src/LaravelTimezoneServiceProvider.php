<?php

namespace Addeos\LaravelTimezone;

use Illuminate\Support\ServiceProvider;

class LaravelTimezoneServiceProvider extends ServiceProvider
{
    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {
        // Allow migrations publish
        if (!class_exists('AddTimezoneColumnToUsersTable')) {
            $this->publishes([
                __DIR__ . '/database/migrations/add_timezone_column_to_users_table.php.stub' => database_path(
                    '/migrations/' . date('Y_m_d_His') . '_add_timezone_column_to_users_table.php'
                ),
            ], 'migrations');
        }
    }
}