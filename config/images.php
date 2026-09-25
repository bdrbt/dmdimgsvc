<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Image Upload Restrictions
    |--------------------------------------------------------------------------
    |
    | max_size_kb: max image filesize
    | daily_limit: daiyli images limit per user
    |
    */

    'max_size_kb' => (int) env('IMAGE_MAX_SIZE_KB', 10240),
    'daily_limit' => (int) env('IMAGE_DAILY_LIMIT', 100000),
    'allowed_mimes' => ['image/jpeg', 'image/png'],
    'allowed_extensions' => ['jpeg', 'jpg', 'png'],
];
