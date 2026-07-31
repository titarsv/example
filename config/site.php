<?php

return [
    'modules' => [
        'blog' => [
            'active' => true
        ],
        'shop' => [
            'active' => true,
            'currencies' => [
                'gpb' => [
                    'name' => 'British Pound',
                    'symbol' => '£',
                    'pricing_template' => '£%.2f',
                ],
            ],
            'multi_currency' => [
                'public' => false,
                'admin' => false
            ],
            'main_currency' => 'gpb',
            'modules' => [
                'products' => [
                    'active' => true
                ],
                'categories' => [
                    'active' => true
                ],
                'attributes' => [
                    'active' => true
                ],
                'variations' => [
                    'active' => true
                ],
                'orders' => [
                    'active' => true
                ],
                'cart' => [
                    'active' => true
                ],
                'coupons' => [
                    'active' => true
                ],
                'imports' => [
                    'active' => true
                ],
                'exports' => [
                    'active' => true
                ],
                'products_reviews' => [
                    'active' => true
                ],
                'facebook_pixel' => [

                ],
                'mcc' => [
                    'active' => true,
                    'key' => env('MCC_API_KEY'),
                    'wallets' => [
                        'BTC' => [
                            '15cEMJGoHZrnWkAY8pp6ewkfLkj38AgkSL',
                            '16nnPzUqTsksXa1197YALwW9BzwiBTmTNS',
                            '16qUJtpDJGR7E11kuTjsxs96wLvTsXRrpH',
                            '1G6xuwcR1YhLD7ZmJsJobPEjbWria8W91z',
                            '1M4vMcrbTfCM5VeNfTwZhNmbANn4mrBuGE',
                            '1MctXH4ZvxQii5rf1U1BUtVVg6e2EpKQLR',
                            '1PVVfgbgQCgGveUn4e5g5iYK8vRyEizocw',
                            'bc1q7qw3gxr039486rl4pl4tp3zxtjxrdkkc22shqy',
                        ],
                        'ETH' => [
                            '0x12C8652311A002728fD22FA0adda4ab3821EeC42',
                            '0x7D89c3b85D21d0aac997273Ea91cFfb2269779a5',
                            '0x8C27d856444cd6E80Bc9A505c49d96BFeB41f47A',
                            '0xd08FAb222872e5be4D647daDA124C00604905910',
                        ],
                        'LTC' => [
                            'ltc1qlm9cysqschdgd7ux55dz4qkvku7lgthqusc80k'
                        ],
                        'XMR' => [
                            '499Frpbw6LiGKprepot4YC2uZNGZoTde91BNqoXniGbLD9WAvqhRKKPKoNPCSD5ewVTk5cKfny2NrZGoUs7vn5Wg9VvsUvr'
                        ],
                        'MATIC' => [
                            '0x86453f8458200BCA5d6DaAcB02b9C1d30328d4EE'
                        ],
                        'USDT_TRON' => [
                            'TGGbdJ8g8ZfhxGZuag6UYE61zzG1oDttCU',
                            'TQV6khjXTZAMfv3heDwFUphA9aR63VA4Ue',
                            'TYMMxRrkoBkEJYZzQPMriDNpLgMNX36ruT'
                        ],
                        'XRP' => [
                            'rDstgoi2kTXHtKMW9Tw6X4uwUMYDk9tMYv'
                        ]
                    ]
                ]
            ]
        ],
        'blocks' => [
            'active' => true
        ],
        'users' => [
            'active' => true
        ],
        'redirects' => [
            'active' => true
        ],
        'requests' => [
            'active' => true
        ],
        'sitemap' => [
            'active' => true
        ],
        'site_reviews' => [
            'active' => true
        ],
        'telegram' => [
            'active' => true
        ],
    ]
];
