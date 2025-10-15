<?php

return [
    'locations' => [
        'Pulau Pinang' => 'A',
        'Kedah' => 'A',
        'Perlis' => 'A',
        'Perak' => 'B',
        'Selangor' => 'C',
        'W.P. Kuala Lumpur' => 'C',
        'Melaka' => 'C',
        'Negeri Sembilan' => 'C',
        'Johor' => 'D',
        'Pahang' => 'E',
        'Kelantan' => 'F',
        'Terengganu' => 'F',
    ],

    'regions' => [
        'Sabah' => [
            'Kota Kinabalu' => 'G',
            'Sandakan' => 'H',
            'Tawau' => 'I',
        ],
        'Sarawak' => [
            'Kuching' => 'J',
            'Sibu' => 'K',
            'Miri' => 'L',
        ],
    ],

    'structures' => [
        'Single Storey (R.C.) Building' => '1',
        '2-4 Storey (R.C.) Building (Flat Roof)' => '2',
        '2-4 Storey (R.C.) Building (Pitched Roof)' => '3',
        '5 Storey and Above (R.C.) Building (For Accommodation)' => '4',
        '5 Storey and Above (R.C.) Building (For Office)' => '5',
        'Timber Building' => '6',
        'Timber Piling' => '7',
        'R.C. Piling' => '8',
        'Single Storey Steel (Building)' => '9',
        'Single Storey Steel (Tower Only)' => '10',
    ],

    'structure_availability' => [
        'RNC' => [
            'all_states' => [
                'Single Storey (R.C.) Building',
                '2-4 Storey (R.C.) Building (Flat Roof)',
                '2-4 Storey (R.C.) Building (Pitched Roof)',
                '5 Storey and Above (R.C.) Building (For Accommodation)'
            ]
        ],
        'NRNC' => [
            'base_structures' => [
                '5 Storey and Above (R.C.) Building (For Office)',
                'Timber Building',
                'Timber Piling',
                'R.C. Piling'
            ],
            'special_states' => [
                'Sabah' => [
                    '5 Storey and Above (R.C.) Building (For Office)',
                    'Timber Building',
                    'Timber Piling',
                    'R.C. Piling',
                    'Single Storey Steel (Building)',
                    'Single Storey Steel (Tower Only)'
                ],
                'Sarawak' => [
                    '5 Storey and Above (R.C.) Building (For Office)',
                    'Timber Building',
                    'Timber Piling',
                    'R.C. Piling',
                    'Single Storey Steel (Building)'
                ]
            ]
        ]
    ],

    'rating_scales' => [
        'Platinum (86 - 100)' => 'Platinum',
        'Gold (76 - 85)' => 'Gold',
        'Silver (66 - 75)' => 'Silver',
        'Certified (50 - 65)' => 'Certified',
    ]
];
