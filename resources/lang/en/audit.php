<?php

return [
    'navigation_label' => 'Lock Audit Log',
    'plural_label' => 'Lock Audit Logs',
    'locked' => 'Locked',
    'unlocked' => 'Unlocked',
    'expired' => 'Expired',
    'force_unlocked' => 'Force Unlocked',
    'columns' => [
        'action' => 'Action',
        'lockable_type' => 'Resource Type',
        'lockable_id' => 'Resource ID',
        'user_id' => 'Lock Owner',
        'actor_user_id' => 'Performed By',
        'created_at' => 'Occurred At',
    ],
    'filters' => [
        'action' => 'Action',
        'created_at' => 'Date Range',
        'from' => 'From',
        'until' => 'Until',
    ],
];
