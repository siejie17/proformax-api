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
        'location' => 'J', // Kuching
        'year' => 2024,
        'cost_breakdown' => [
            'A' => [
                'description' => 'SUBSTRUCTURE',
                'children' => [
                    '1' => [
                        'description' => 'Piling',
                        'cost_per_gfa' => 148.62,
                    ],
                    '2' => [
                        'description' => 'Work Below Lowest Floor Finish',
                        'cost_per_gfa' => 233.87,
                    ],
                ],
            ],
            'B' => [
                'description' => 'SUPERSTRUCTURE',
                'children' => [
                    '1' => [
                        'description' => 'Frame',
                        'cost_per_gfa' => 358.68,
                    ],
                    '2' => [
                        'description' => 'Upper Floor',
                        'cost_per_gfa' => 129.73,
                    ],
                    '3' => [
                        'description' => 'Roof',
                        'cost_per_gfa' => 119.04,
                    ],
                    '4' => [
                        'description' => 'Stairs',
                        'cost_per_gfa' => 56.71,
                    ],
                    '5' => [
                        'description' => 'External Walls',
                        'cost_per_gfa' => 59.33,
                    ],
                    '6' => [
                        'description' => 'Windows',
                        'cost_per_gfa' => 109.27,
                    ],
                    '7' => [
                        'description' => 'Internal Walls & Partitions',
                        'cost_per_gfa' => 49.19,
                    ],
                    '8' => [
                        'description' => 'Doors',
                        'cost_per_gfa' => 61.29,
                    ]
                ],
            ],
            'C' => [
                'description' => 'FINISHES',
                'children' => [
                    '1' => [
                        'description' => 'Wall Finishes',
                        'cost_per_gfa' => 137.94,
                    ],
                    '2' => [
                        'description' => 'Floor Finishes',
                        'cost_per_gfa' => 99.94,
                    ],
                    '3' => [
                        'description' => 'Ceiling Finishes',
                        'cost_per_gfa' => 42.02,
                    ],
                ],
            ],
            'D' => [
                'description' => 'FITTINGS AND FURNISHINGS',
                'cost_per_gfa' => 7.03,
            ],
            'E' => [
                'description' => 'SERVICES',
                'children' => [
                    '1' => [
                        'description' => 'Sanitary Appliances',
                        'cost_per_gfa' => 33.14,
                    ],
                    '2' => [
                        'description' => 'Plumbing Installation',
                        'cost_per_gfa' => 124.22,
                    ],
                    '3' => [
                        'description' => 'Air-conditioning & Ventilation System',
                        'cost_per_gfa' => 104.00,
                    ],
                    '4' => [
                        'description' => 'Electrical Installation',
                        'cost_per_gfa' => 249.12,
                    ],
                    '5' => [
                        'description' => 'Fire Protection Installation',
                        'cost_per_gfa' => 70.71,
                    ],
                ],
            ],
            'F' => [
                'description' => 'EXTERNAL WORKS',
                'children' => [
                    '1' => [
                        'description' => 'Site Work',
                        'cost_per_gfa' => 521.10,
                    ],
                    '2' => [
                        'description' => 'Drainage',
                        'cost_per_gfa' => 188.84,
                    ],
                    '3' => [
                        'description' => 'Ancillary Buildings',
                        'cost_per_gfa' => 26.47,
                    ],
                    '4' => [
                        'description' => 'Piling',
                        'cost_per_gfa' => 229.28,
                    ]
                ],
            ],
            'F' => [
                'description' => 'PRELIMINARIES',
                'cost_per_gfa' => 236.06,
            ],
        ]
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
        'location' => 'D', // Johor
        'year' => 2024,
        'cost_breakdown' => [
            'A' => [
                'description' => 'SUBSTRUCTURE',
                'children' => [
                    '1' => [
                        'description' => 'Work Below Lowest Floor Finish',
                        'cost_per_gfa' => 121.97,
                    ],
                ],
            ],
            'B' => [
                'description' => 'SUPERSTRUCTURE',
                'children' => [
                    '1' => [
                        'description' => 'Frame',
                        'cost_per_gfa' => 168.72,
                    ],
                    '2' => [
                        'description' => 'Upper Floor',
                        'cost_per_gfa' => 5.43,
                    ],
                    '3' => [
                        'description' => 'Roof',
                        'cost_per_gfa' => 213.34,
                    ],
                    '4' => [
                        'description' => 'Stairs',
                        'cost_per_gfa' => 12.67,
                    ],
                    '5' => [
                        'description' => 'External Walls',
                        'cost_per_gfa' => 27.37,
                    ],
                    '6' => [
                        'description' => 'Windows',
                        'cost_per_gfa' => 17.00,
                    ],
                    '7' => [
                        'description' => 'Internal Walls & Partitions',
                        'cost_per_gfa' => 7.39,
                    ],
                    '8' => [
                        'description' => 'Doors',
                        'cost_per_gfa' => 5.47,
                    ]
                ],
            ],
            'C' => [
                'description' => 'FINISHES',
                'children' => [
                    '1' => [
                        'description' => 'Wall Finishes',
                        'cost_per_gfa' => 7.99,
                    ],
                    '2' => [
                        'description' => 'Floor Finishes',
                        'cost_per_gfa' => 30.80,
                    ],
                    '3' => [
                        'description' => 'Ceiling Finishes',
                        'cost_per_gfa' => 19.44,
                    ],
                    '4' => [
                        'description' => 'External Finishes',
                        'cost_per_gfa' => 46.17,
                    ]
                ],
            ],
            'D' => [
                'description' => 'FITTINGS AND FURNISHINGS',
                'cost_per_gfa' => 58.57,
            ],
            'E' => [
                'description' => 'SERVICES',
                'children' => [
                    '1' => [
                        'description' => 'Sanitary Appliances',
                        'cost_per_gfa' => 31.93,
                    ],
                    '2' => [
                        'description' => 'Plumbing Installation',
                        'cost_per_gfa' => 12.01,
                    ],
                    '3' => [
                        'description' => 'Electrical Installation',
                        'cost_per_gfa' => 73.37,
                    ],
                    '4' => [
                        'description' => 'Fire Protection Installation',
                        'cost_per_gfa' => 9.58,
                    ],
                    '5' => [
                        'description' => 'Communication Installation',
                        'cost_per_gfa' => 28.90,
                    ],
                    '6' => [
                        'description' => 'Special Installation',
                        'cost_per_gfa' => 3.95,
                    ],
                    '7' => [
                        'description' => 'Builder\'s Work in Connection with Services',
                        'cost_per_gfa' => 1.13,
                    ],
                ],
            ],
            'F' => [
                'description' => 'EXTERNAL WORKS',
                'children' => [
                    '1' => [
                        'description' => 'Site Work',
                        'cost_per_gfa' => 105.00,
                    ],
                    '2' => [
                        'description' => 'Drainage',
                        'cost_per_gfa' => 27.95,
                    ],
                    '3' => [
                        'description' => 'External Services',
                        'cost_per_gfa' => 32.37,
                    ],
                    '4' => [
                        'description' => 'Ancillary Buildings',
                        'cost_per_gfa' => 32.33,
                    ],
                    '5' => [
                        'description' => 'Recreational Facilities',
                        'cost_per_gfa' => 1.49,
                    ],
                    '6' => [
                        'description' => 'Piling',
                        'cost_per_gfa' => 56.67,
                    ],
                    '7' => [
                        'description' => 'Fencing & Gate',
                        'cost_per_gfa' => 6.15,
                    ]
                ],
            ],
            'F' => [
                'description' => 'PRELIMINARIES',
                'cost_per_gfa' => 50.71,
            ],
        ]
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
