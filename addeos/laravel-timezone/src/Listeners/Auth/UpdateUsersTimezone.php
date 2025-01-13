<?php

namespace Addeos\LaravelTimezone\Listeners\Auth;

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
        var_dump('ddd'); exit;
        $user = $event->user;
        $tz = Helper::getLocalTimeZone();
        if ($user->timezone != $tz) {
            $user->timezone = $tz;
            $user->save();
        }
    }
}