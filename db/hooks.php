<?php

$callbacks = [
    [
        'hook' => \core\hook\output\before_http_headers::class,
        'callback' => [\filter_edusharing\hook_callbacks::class,'before_http_headers'],
        'priority' => 500
    ]
];
