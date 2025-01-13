<?php

namespace App\Listeners\Auth;

use Addeos\LaravelTimezone\Helper;
use Illuminate\Auth\Events\Registered;

class UpdateUsersTimezone
{
    /**
     * Handle the event.
     *
     * @return void
     */
    public function handle(Registered $event)
    {
        $user = $event->user;
        $tz = Helper::getLocalTimeZone();
        if ($user->timezone === null) {
            $user->timezone = $tz;
            $user->save();
        }
    }
}