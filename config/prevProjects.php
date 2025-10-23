<?php

return [
    'Light duty factories' => [
        'location' => 'A',
        'year' => 2024,
        'cost_breakdown' => [
            'A' => [
                'description' => 'SUBSTRUCTURE',
                'children' => [
                    '1' => [
                        'description' => 'Piling',
                        'cost_per_gfa' => 381.09,
                    ],
                    '2' => [
                        'description' => 'Work Below Lowest Floor Finish',
                        'cost_per_gfa' => 564.14,
                    ],
                ],
            ],
            'B' => [
                'description' => 'SUPERSTRUCTURE',
                'children' => [
                    '1' => [
                        'description' => 'Frame',
                        'cost_per_gfa' => 20.03,
                    ],
                    '2' => [
                        'description' => 'Roof',
                        'cost_per_gfa' => 418.34,
                    ],
                    '3' => [
                        'description' => 'External Wall',
                        'cost_per_gfa' => 87.14,
                    ],
                    '4' => [
                        'description' => 'Windows',
                        'cost_per_gfa' => 8.17,
                    ],
                    '5' => [
                        'description' => 'Internal Walls & Partitions',
                        'cost_per_gfa' => 78.13,
                    ],
                    '6' => [
                        'description' => 'Doors',
                        'cost_per_gfa' => 147.61,
                    ]
                ],
            ],
            'C' => [
                'description' => 'FINISHES',
                'children' => [
                    '1' => [
                        'description' => 'Wall Finishes',
                        'cost_per_gfa' => 127.23,
                    ],
                    '2' => [
                        'description' => 'Floor Finishes',
                        'cost_per_gfa' => 73.89,
                    ],
                    '3' => [
                        'description' => 'Ceiling Finishes',
                        'cost_per_gfa' => 164.32,
                    ],
                ],
            ],
            'D' => [
                'description' => 'FITTINGS AND FURNISHINGS',
                'cost_per_gfa' => 7.99,
            ],
            'E' => [
                'description' => 'SERVICES',
                'children' => [
                    '1' => [
                        'description' => 'Sanitary Appliances',
                        'cost_per_gfa' => 63.63,
                    ],
                    '2' => [
                        'description' => 'Plumbing Installation',
                        'cost_per_gfa' => 72.61,
                    ],
                    '3' => [
                        'description' => 'Electrical Installation',
                        'cost_per_gfa' => 319.15,
                    ],
                    '4' => [
                        'description' => 'Fire Protection Installation',
                        'cost_per_gfa' => 19.71,
                    ],
                    '5' => [
                        'description' => 'Communication Installation',
                        'cost_per_gfa' => 16.65,
                    ],
                ],
            ],
            'F' => [
                'description' => 'EXTERNAL WORKS',
                'children' => [
                    '1' => [
                        'description' => 'Site Work',
                        'cost_per_gfa' => 285.21,
                    ],
                    '2' => [
                        'description' => 'Drainage',
                        'cost_per_gfa' => 84.74,
                    ],
                    '3' => [
                        'description' => 'External Services',
                        'cost_per_gfa' => 68.67,
                    ],
                    '4' => [
                        'description' => 'Ancillary Buildings',
                        'cost_per_gfa' => 43.94,
                    ],
                    '5' => [
                        'description' => 'Piling',
                        'cost_per_gfa' => 123.63,
                    ]
                ],
            ],
            'F' => [
                'description' => 'PRELIMINARIES',
                'cost_per_gfa' => 202.44,
            ],
        ]
    ],
    'Warehouses' => [
        'location' => 'B',
        'year' => 2024,
    ],
    '3-storey offices, owner operated' => [
        'location' => 'C',
        'year' => 2024,
    ],
    'Shop offices' => [
        'location' => 'D',
        'year' => 2024,
    ],
    'Restaurants' => [
        'location' => 'E',
        'year' => 2024,
    ],
    'Multi-purpose halls' => [
        'project_name' => 'UNIMAS Student Pavilion',
        'location' => 'J',
        'year' => 2011,
        'cost_breakdown' => [
            'A' => [
                'description' => 'SUBSTRUCTURE',
                'children' => [
                    '1' => [
                        'description' => 'Piling',
                        'cost_per_gfa' => 114.42,
                    ],
                    '2' => [
                        'description' => 'Work Below Lowest Floor Finish',
                        'cost_per_gfa' => 305.75,
                    ],
                ],
            ],
            'B' => [
                'description' => 'SUPERSTRUCTURE',
                'children' => [
                    '1' => [
                        'description' => 'Stairs',
                        'cost_per_gfa' => 20.03,
                    ],
                    '2' => [
                        'description' => 'External Wall',
                        'cost_per_gfa' => 42.01,
                    ],
                    '3' => [
                        'description' => 'Doors',
                        'cost_per_gfa' => 2.53,
                    ],
                    '4' => [
                        'description' => 'Ironmongery',
                        'cost_per_gfa' => 50.62,
                    ],
                ],
            ],
            'C' => [
                'description' => 'FINISHES',
                'children' => [
                    '1' => [
                        'description' => 'Wall Finishes',
                        'cost_per_gfa' => 105.75,
                    ],
                    '2' => [
                        'description' => 'Floor Finishes',
                        'cost_per_gfa' => 45.62,
                    ],
                    '3' => [
                        'description' => 'Ceiling Finishes',
                        'cost_per_gfa' => 166.08,
                    ],
                ],
            ],
            'D' => [
                'description' => 'SERVICES',
                'children' => [
                    '1' => [
                        'description' => 'Sanitary Appliances',
                        'cost_per_gfa' => 8.77,
                    ],
                    '2' => [
                        'description' => 'Builder\'s Work in Connection with Services',
                        'cost_per_gfa' => 54.72,
                    ],

                ],
            ],
            'E' => [
                'description' => 'EXTERNAL WORKS',
                'children' => [
                    '1' => [
                        'description' => 'Site Work',
                        'cost_per_gfa' => 14.15,
                    ],
                    '2' => [
                        'description' => 'Drainage',
                        'cost_per_gfa' => 29.25,
                    ],
                    '3' => [
                        'description' => 'Ancillary Buildings',
                        'cost_per_gfa' => 37.74,
                    ],
                    '4' => [
                        'description' => 'Septic Tank',
                        'cost_per_gfa' => 16.98,
                    ]
                ],
            ],
            'F' => [
                'description' => 'PROVISIONAL SUMS',
                'cost_per_gfa' => 23.58,
            ],
        ]
    ],
    'Mosques' => [
        'location' => 'G',
        'year' => 2024,
    ],
    '2-storey bungalows' => [
        'location' => 'H',
        'year' => 2024,
    ],
    '2-storey terrace houses' => [
        'location' => 'I',
        'year' => 2024,
    ],
    'Luxury apartments' => [
        'location' => 'J',
        'year' => 2024,
    ],
    'Elevated car parks' => [
        'location' => 'K',
        'year' => 2024,
    ],
];
