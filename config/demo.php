<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Demo Mode
    |--------------------------------------------------------------------------
    |
    | When enabled, the application seeds synthetic demo data and constrains
    | destructive system actions so a shared public demo can never destroy its
    | own baseline or send real mail. See docs/DEMO.md for setup and usage.
    |
    */

    'enabled' => filter_var(env('DEMO_MODE', false), FILTER_VALIDATE_BOOL),

    'organization_slug' => env('DEMO_ORGANIZATION_SLUG', 'demo-acme'),
];
