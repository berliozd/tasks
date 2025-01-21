<?php

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Log;

Broadcast::channel('my-public-channel', function ($user) {
    Log::info('PUB : ' . $user->id);
});

Broadcast::channel('my-private-channel', function ($user) {
    Log::info('PRIV : ' . $user->id);
    return true;
});


