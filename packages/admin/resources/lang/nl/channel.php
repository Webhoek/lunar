<?php

return [

    'label' => 'Kanaal',

    'plural_label' => 'Kanalen',

    'table' => [
        'name' => [
            'label' => 'Naam',
        ],
        'handle' => [
            'label' => 'Handvat',
        ],
        'url' => [
            'label' => 'URL',
        ],
        'default' => [
            'label' => 'Standaard',
        ],
    ],

    'form' => [
        'name' => [
            'label' => 'Naam',
        ],
        'handle' => [
            'label' => 'Handvat',
        ],
        'url' => [
            'label' => 'URL',
        ],
        'default' => [
            'label' => 'Standaard',
        ],
    ],

    'services' => [
        'woocommerce' => [
            'sync_description' => [
                'label' => 'Beschrijving Synchroniseren',
                'helper_text' => 'Schakel in om productbeschrijvingen te synchroniseren met WooCommerce',
            ],
            'sync_images' => [
                'label' => 'Afbeeldingen Synchroniseren',
                'helper_text' => 'Schakel in om productafbeeldingen te synchroniseren met WooCommerce',
            ],
            'sync_price' => [
                'label' => 'Prijs Synchroniseren',
                'helper_text' => 'Schakel in om productprijzen te synchroniseren met WooCommerce',
            ],
            'sync_variants' => [
                'label' => 'Varianten Synchroniseren',
                'helper_text' => 'Schakel in om productvarianten te synchroniseren met WooCommerce',
            ],
            'sync_auto_delete' => [
                'label' => 'Automatisch Verwijderen',
                'helper_text' => 'Schakel in om producten automatisch uit WooCommerce te verwijderen wanneer ze uit het systeem worden verwijderd',
            ],
        ],
    ],

];
