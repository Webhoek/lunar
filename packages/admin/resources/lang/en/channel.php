<?php

return [

    'label' => 'Channel',

    'plural_label' => 'Channels',

    'table' => [
        'name' => [
            'label' => 'Name',
        ],
        'handle' => [
            'label' => 'Handle',
        ],
        'url' => [
            'label' => 'URL',
        ],
        'default' => [
            'label' => 'Default',
        ],
    ],

    'form' => [
        'name' => [
            'label' => 'Name',
        ],
        'handle' => [
            'label' => 'Handle',
        ],
        'url' => [
            'label' => 'URL',
        ],
        'default' => [
            'label' => 'Default',
        ],
    ],

    'services' => [
        'woocommerce' => [
            'sync_description' => [
                'label' => 'Sync Description',
                'helper_text' => 'Enable to sync product descriptions with WooCommerce',
            ],
            'sync_images' => [
                'label' => 'Sync Images',
                'helper_text' => 'Enable to sync product images with WooCommerce',
            ],
            'sync_price' => [
                'label' => 'Sync Price',
                'helper_text' => 'Enable to sync product prices with WooCommerce',
            ],
            'sync_variants' => [
                'label' => 'Sync Variants',
                'helper_text' => 'Enable to sync product variants with WooCommerce',
            ],
            'sync_auto_delete' => [
                'label' => 'Auto Delete',
                'helper_text' => 'Enable to automatically delete products from WooCommerce when deleted in the system',
            ],
        ],
    ],

];
