<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('channel-name', function ($user) {
    return true;
});

Broadcast::channel('my-channel', function () {
    return true; // Autorise tout le monde à écouter ce channel
});
