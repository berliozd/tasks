<?php

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Log;

//Broadcast::channel('my-public-channel', function () {
//    return true; // Allow everyone to listen that channel
//});

Broadcast::channel('my-private-channel', function ($user) {
    Log::info($user->id);
    return true;
});


