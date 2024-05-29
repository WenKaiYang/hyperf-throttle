<?php

use function Hyperf\Support\env;

return [
    'redis' => env('THROTTLE_REDIS_POOL_NAME', 'default'),
];