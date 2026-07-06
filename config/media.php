<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Menu image storage
    |--------------------------------------------------------------------------
    |
    | Use "gcs" in production/staging. Tests override MENU_IMAGE_DISK=public.
    |
    */

    'menu_image_disk' => env('MENU_IMAGE_DISK', 'gcs'),

    'menu_image_max_kb' => (int) env('MENU_IMAGE_MAX_KB', 5120),

];
