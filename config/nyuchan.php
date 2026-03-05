<?php

return [
    'attachments_disk' => env('ATTACHMENTS_DISK', env('FILESYSTEM_DISK', 'local')),
    'attachments_fallback_disks' => array_values(array_filter(array_map(
        static fn (string $v): string => trim($v),
        explode(',', (string) env('ATTACHMENTS_FALLBACK_DISKS', 'local'))
    ))),
    'attachments_input_max_bytes' => (int) env('ATTACHMENTS_INPUT_MAX_BYTES', 20 * 1024 * 1024),
    'attachments_target_max_bytes' => (int) env('ATTACHMENTS_TARGET_MAX_BYTES', 5 * 1024 * 1024),
    'attachments_max_files' => (int) env('ATTACHMENTS_MAX_FILES', 4),
    'attachments_auto_compress_mimes' => [
        'image/jpeg',
        'image/png',
        'image/webp',
    ],
    'board_nav_order' => ['a', 'b', 'rf', 'nsfw'],

    'profile_colors' => [
        '#ff6fb0' => 'ui.color_rose',
        '#FF6600' => 'ui.color_orange',
        '#5fc7ff' => 'ui.color_cyan',
        '#59c65f' => 'ui.color_green',
        '#ffd84d' => 'ui.color_yellow',
        '#a88bff' => 'ui.color_violet',
        '#ff7a7a' => 'ui.color_red',
        '#7d8b99' => 'ui.color_steel',
        '#2b2230' => 'ui.color_ink',
        '#ffffff' => 'ui.color_white',
    ],
];
