<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PrevProjectCostsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $prevProjects = [
            'Light Duty Factories' => [
                'location' => 'J', // Kuching
                'year' => 2025,
                'cost_breakdown' => [
                    'A' => [
                        'description' => 'SUBSTRUCTURE',
                        'children' => [
                            '1' => [
                                'description' => 'Piling',
                                'cost_per_gfa' => 210.04,
                            ],
                            '2' => [
                                'description' => 'Work Below Lowest Floor Finish',
                                'cost_per_gfa' => 382.28,
                            ],
                        ],
                    ],
                    'B' => [
                        'description' => 'SUPERSTRUCTURE',
                        'children' => [
                            '1' => [
                                'description' => 'Frame',
                                'cost_per_gfa' => 102.57,
                            ],
                            '2' => [
                                'description' => 'Upper Floor',
                                'cost_per_gfa' => 31.24,
                            ],
                            '3' => [
                                'description' => 'Roof',
                                'cost_per_gfa' => 281.06,
                            ],
                            '4' => [
                                'description' => 'Stairs',
                                'cost_per_gfa' => 9.97,
                            ],
                            '5' => [
                                'description' => 'External Wall',
                                'cost_per_gfa' => 61.82,
                            ],
                            '6' => [
                                'description' => 'Windows & External Doors',
                                'cost_per_gfa' => 27.73,
                            ],
                            '7' => [
                                'description' => 'Internal Walls & Partitions',
                                'cost_per_gfa' => 45.10,
                            ],
                            '8' => [
                                'description' => 'Internal Doors',
                                'cost_per_gfa' => 80.75,
                            ]
                        ],
                    ],
                    'C' => [
                        'description' => 'FINISHES',
                        'children' => [
                            '1' => [
                                'description' => 'Internal Wall Finishes',
                                'cost_per_gfa' => 84.69,
                            ],
                            '2' => [
                                'description' => 'Internal Floor Finishes',
                                'cost_per_gfa' => 54.68,
                            ],
                            '3' => [
                                'description' => 'Internal Ceiling Finishes',
                                'cost_per_gfa' => 88.37,
                            ],
                            '4' => [
                                'description' => 'External Finishes',
                                'cost_per_gfa' => 17.54,
                            ]
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
                                'cost_per_gfa' => 34.84,
                            ],
                            '2' => [
                                'description' => 'Plumbing Installation',
                                'cost_per_gfa' => 50.96,
                            ],
                            '3' => [
                                'description' => 'Refuse Disposal',
                                'cost_per_gfa' => 0.62,
                            ],
                            '4' => [
                                'description' => 'Electrical Installation',
                                'cost_per_gfa' => 236.08,
                            ],
                            '5' => [
                                'description' => 'Fire Protection Installation',
                                'cost_per_gfa' => 36.60,
                            ],
                            '6' => [
                                'description' => 'Communication Installation',
                                'cost_per_gfa' => 9.33,
                            ],
                            '7' => [
                                'description' => 'Builder\'s Profit & Attendance on Services',
                                'cost_per_gfa' => 4.11,
                            ],
                            '8' => [
                                'description' => 'Builder\'s Work In Connection with Services',
                                'cost_per_gfa' => 6.43,
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
                ]
            ],
            'Warehouses' => [
                'location' => 'J', // Kuching
                'year' => 2025,
                'cost_breakdown' => [
                    'A' => [
                        'description' => 'SUBSTRUCTURE',
                        'children' => [
                            '1' => [
                                'description' => 'Piling',
                                'cost_per_gfa' => 156.00,
                            ],
                            '2' => [
                                'description' => 'Work Below Lowest Floor Finish',
                                'cost_per_gfa' => 231.15,
                            ],
                        ],
                    ],
                    'B' => [
                        'description' => 'SUPERSTRUCTURE',
                        'children' => [
                            '1' => [
                                'description' => 'Frame',
                                'cost_per_gfa' => 231.53,
                            ],
                            '2' => [
                                'description' => 'Upper Floor',
                                'cost_per_gfa' => 85.48,
                            ],
                            '3' => [
                                'description' => 'Roof',
                                'cost_per_gfa' => 189.00,
                            ],
                            '4' => [
                                'description' => 'Stairs',
                                'cost_per_gfa' => 39.48,
                            ],
                            '5' => [
                                'description' => 'External Walls',
                                'cost_per_gfa' => 51.99,
                            ],
                            '6' => [
                                'description' => 'Windows',
                                'cost_per_gfa' => 57.96,
                            ],
                            '7' => [
                                'description' => 'Internal Walls & Partitions',
                                'cost_per_gfa' => 54.18,
                            ],
                            '8' => [
                                'description' => 'Doors',
                                'cost_per_gfa' => 41.93,
                            ]
                        ],
                    ],
                    'C' => [
                        'description' => 'FINISHES',
                        'children' => [
                            '1' => [
                                'description' => 'Wall Finishes',
                                'cost_per_gfa' => 90.47,
                            ],
                            '2' => [
                                'description' => 'Floor Finishes',
                                'cost_per_gfa' => 56.27,
                            ],
                            '3' => [
                                'description' => 'Ceiling Finishes',
                                'cost_per_gfa' => 30.15,
                            ],
                            '4' => [
                                'description' => 'External Finishes',
                                'cost_per_gfa' => 38.32,
                            ]
                        ],
                    ],
                    'D' => [
                        'description' => 'FITTINGS AND FURNISHINGS',
                        'cost_per_gfa' => 7.17,
                    ],
                    'E' => [
                        'description' => 'SERVICES',
                        'children' => [
                            '1' => [
                                'description' => 'Sanitary Appliances',
                                'cost_per_gfa' => 19.69,
                            ],
                            '2' => [
                                'description' => 'Plumbing Installation',
                                'cost_per_gfa' => 79.60,
                            ],
                            '3' => [
                                'description' => 'Air-conditioning & Ventilation System',
                                'cost_per_gfa' => 108.00,
                            ],
                            '4' => [
                                'description' => 'Electrical Installation',
                                'cost_per_gfa' => 154.16,
                            ],
                            '5' => [
                                'description' => 'Fire Protection Installation',
                                'cost_per_gfa' => 88.36,
                            ],
                            '6' => [
                                'description' => 'Lift & Conveyor Installation',
                                'cost_per_gfa' => 37.35,
                            ],
                            '7' => [
                                'description' => 'Communication Installation',
                                'cost_per_gfa' => 18.21,
                            ],
                            '8' => [
                                'description' => 'Special Installation',
                                'cost_per_gfa' => 2.83,
                            ],
                            '9' => [
                                'description' => 'Builder\'s Profit & Attendance on Services',
                                'cost_per_gfa' => 10.59,
                            ],
                            '10' => [
                                'description' => 'Builder\'s Work In Connection with Services',
                                'cost_per_gfa' => 17.09,
                            ],
                            '11' => [
                                'description' => 'Lump Sum Services',
                                'cost_per_gfa' => 149.65,
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
                    'G' => [
                        'description' => 'MISCELLANEOUS',
                        'cost_per_gfa' => 236.06,
                    ],
                ]
            ],
            'Offices, Owner Operated' => [
                'location' => 'J', // Kuching
                'year' => 2025,
                'cost_breakdown' => [
                    'A' => [
                        'description' => 'SUBSTRUCTURE',
                        'children' => [
                            '1' => [
                                'description' => 'Piling',
                                'cost_per_gfa' => 298.97,
                            ],
                            '2' => [
                                'description' => 'Work Below Lowest Floor Finish',
                                'cost_per_gfa' => 175.62,
                            ],
                        ],
                    ],
                    'B' => [
                        'description' => 'SUPERSTRUCTURE',
                        'children' => [
                            '1' => [
                                'description' => 'Frame',
                                'cost_per_gfa' => 197.00,
                            ],
                            '2' => [
                                'description' => 'Upper Floor',
                                'cost_per_gfa' => 94.55,
                            ],
                            '3' => [
                                'description' => 'Roof',
                                'cost_per_gfa' => 131.83,
                            ],
                            '4' => [
                                'description' => 'Stairs',
                                'cost_per_gfa' => 34.18,
                            ],
                            '5' => [
                                'description' => 'External Walls',
                                'cost_per_gfa' => 117.18,
                            ],
                            '6' => [
                                'description' => 'Windows & External Doors',
                                'cost_per_gfa' => 61.42,
                            ],
                            '7' => [
                                'description' => 'Internal Walls & Partitions',
                                'cost_per_gfa' => 70.31,
                            ],
                            '8' => [
                                'description' => 'Internal Doors',
                                'cost_per_gfa' => 28.12,
                            ],
                        ],
                    ],
                    'C' => [
                        'description' => 'FINISHES',
                        'children' => [
                            '1' => [
                                'description' => 'Internal Wall Finishes',
                                'cost_per_gfa' => 92.50,
                            ],
                            '2' => [
                                'description' => 'Internal Floor Finishes',
                                'cost_per_gfa' => 51.31,
                            ],
                            '3' => [
                                'description' => 'Internal Ceiling Finishes',
                                'cost_per_gfa' => 33.97,
                            ],
                            '4' => [
                                'description' => 'External Finishes',
                                'cost_per_gfa' => 66.65,
                            ],
                        ],
                    ],
                    'D' => [
                        'description' => 'FITTINGS AND FURNISHINGS',
                        'cost_per_gfa' => 8.98,
                    ],
                    'E' => [
                        'description' => 'SERVICES',
                        'children' => [
                            '1' => [
                                'description' => 'Sanitary Appliances',
                                'cost_per_gfa' => 22.48,
                            ],
                            '2' => [
                                'description' => 'Plumbing Installations',
                                'cost_per_gfa' => 30.56,
                            ],
                            '3' => [
                                'description' => 'Air Conditionings and Ventilation Services',
                                'cost_per_gfa' => 89.85,
                            ],
                            '4' => [
                                'description' => 'Electrical Installation',
                                'cost_per_gfa' => 124.51,
                            ],
                            '5' => [
                                'description' => 'Fire Protection Installation',
                                'cost_per_gfa' => 37.89,
                            ],
                            '6' => [
                                'description' => 'Lift & Conveyor Installation',
                                'cost_per_gfa' => 138.80,
                            ],
                            '7' => [
                                'description' => 'Communication Installation',
                                'cost_per_gfa' => 11.98,
                            ],
                            '8' => [
                                'description' => 'Special Installation',
                                'cost_per_gfa' => 17.97,
                            ],
                            '9' => [
                                'description' => "Builder's Profit & Attendance on Services",
                                'cost_per_gfa' => 7.24,
                            ],
                            '10' => [
                                'description' => "Builder's Work In Connection with Services",
                                'cost_per_gfa' => 26.86,
                            ],
                        ],
                    ],
                ],
            ],
            'Shop Offices' => [
                'location' => 'J', // Kuching
                'year' => 2025,
                'cost_breakdown' => [
                    'A' => [
                        'description' => 'SUBSTRUCTURE',
                        'children' => [
                            '1' => [
                                'description' => 'Piling',
                                'cost_per_gfa' => 117.61,
                            ],
                            '2' => [
                                'description' => 'Work Below Lowest Floor Finish',
                                'cost_per_gfa' => 91.98,
                            ],
                        ],
                    ],
                    'B' => [
                        'description' => 'SUPERSTRUCTURE',
                        'children' => [
                            '1' => [
                                'description' => 'Frame',
                                'cost_per_gfa' => 84.77,
                            ],
                            '2' => [
                                'description' => 'Upper Floor',
                                'cost_per_gfa' => 46.47,
                            ],
                            '3' => [
                                'description' => 'Roof',
                                'cost_per_gfa' => 43.95,
                            ],
                            '4' => [
                                'description' => 'Stairs',
                                'cost_per_gfa' => 20.68,
                            ],
                            '5' => [
                                'description' => 'External Walls',
                                'cost_per_gfa' => 30.93,
                            ],
                            '6' => [
                                'description' => 'Windows & External Doors',
                                'cost_per_gfa' => 22.20,
                            ],
                            '7' => [
                                'description' => 'Internal Walls & Partitions',
                                'cost_per_gfa' => 26.92,
                            ],
                            '8' => [
                                'description' => 'Internal Doors',
                                'cost_per_gfa' => 23.32,
                            ]
                        ],
                    ],
                    'C' => [
                        'description' => 'FINISHES',
                        'children' => [
                            '1' => [
                                'description' => 'Internal Wall Finishes',
                                'cost_per_gfa' => 50.88,
                            ],
                            '2' => [
                                'description' => 'Internal Floor Finishes',
                                'cost_per_gfa' => 30.57,
                            ],
                            '3' => [
                                'description' => 'Internal Ceiling Finishes',
                                'cost_per_gfa' => 18.27,
                            ],
                        ],
                    ],
                    'D' => [
                        'description' => 'FITTINGS AND FURNISHINGS',
                        'cost_per_gfa' => 7.34,
                    ],
                    'E' => [
                        'description' => 'SERVICES',
                        'children' => [
                            '1' => [
                                'description' => 'Sanitary Appliances',
                                'cost_per_gfa' => 9.15,
                            ],
                            '2' => [
                                'description' => 'Plumbing Installation',
                                'cost_per_gfa' => 25.53,
                            ],
                            '3' => [
                                'description' => 'Air-conditioning & Ventilation System',
                                'cost_per_gfa' => 37.47,
                            ],
                            '4' => [
                                'description' => 'Electrical Installation',
                                'cost_per_gfa' => 85.49,
                            ],
                            '5' => [
                                'description' => 'Fire Protection Installation',
                                'cost_per_gfa' => 10.26,
                            ],
                            '6' => [
                                'description' => 'Communication Installation',
                                'cost_per_gfa' => 4.39,
                            ],
                        ],
                    ],
                    'G' => [
                        'description' => 'MISCELLANEOUS',
                        'cost_per_gfa' => 135.46,
                    ],
                ]
            ],
            'Restaurants' => [
                'location' => 'J', // Kuching
                'year' => 2025,
                'cost_breakdown' => [
                    'A' => [
                        'description' => 'SUBSTRUCTURE',
                        'children' => [
                            '1' => [
                                'description' => 'Piling',
                                'cost_per_gfa' => 145.17,
                            ],
                            '2' => [
                                'description' => 'Work Below Lowest Floor Finish',
                                'cost_per_gfa' => 296.72,
                            ],
                        ],
                    ],
                    'B' => [
                        'description' => 'SUPERSTRUCTURE',
                        'children' => [
                            '1' => [
                                'description' => 'Frame',
                                'cost_per_gfa' => 215.78,
                            ],
                            '2' => [
                                'description' => 'Upper Floor',
                                'cost_per_gfa' => 192.60,
                            ],
                            '3' => [
                                'description' => 'Roof',
                                'cost_per_gfa' => 471.79,
                            ],
                            '4' => [
                                'description' => 'Stairs',
                                'cost_per_gfa' => 43.00,
                            ],
                            '5' => [
                                'description' => 'External Walls',
                                'cost_per_gfa' => 110.34,
                            ],
                            '6' => [
                                'description' => 'Windows & External Doors',
                                'cost_per_gfa' => 131.90,
                            ],
                            '7' => [
                                'description' => 'Internal Walls & Partitions',
                                'cost_per_gfa' => 77.71,
                            ],
                            '8' => [
                                'description' => 'Internal Doors',
                                'cost_per_gfa' => 87.01,
                            ],
                        ],
                    ],
                    'C' => [
                        'description' => 'FINISHES',
                        'children' => [
                            '1' => [
                                'description' => 'Internal Wall Finishes',
                                'cost_per_gfa' => 159.25,
                            ],
                            '2' => [
                                'description' => 'Internal Floor Finishes',
                                'cost_per_gfa' => 93.98,
                            ],
                            '3' => [
                                'description' => 'Internal Ceiling Finishes',
                                'cost_per_gfa' => 59.78,
                            ],
                            '4' => [
                                'description' => 'External Finishes',
                                'cost_per_gfa' => 184.10,
                            ],
                        ],
                    ],
                    'D' => [
                        'description' => 'FITTINGS AND FURNISHINGS',
                        'cost_per_gfa' => 64.98,
                    ],
                    'E' => [
                        'description' => 'SERVICES',
                        'children' => [
                            '1' => [
                                'description' => 'Sanitary Appliances',
                                'cost_per_gfa' => 44.25,
                            ],
                            '2' => [
                                'description' => 'Plumbing Installations',
                                'cost_per_gfa' => 119.33,
                            ],
                            '3' => [
                                'description' => 'Air Conditionings and Ventilation Services',
                                'cost_per_gfa' => 113.85,
                            ],
                            '4' => [
                                'description' => 'Electrical Installation',
                                'cost_per_gfa' => 114.93,
                            ],
                            '5' => [
                                'description' => 'Fire Protection Installation',
                                'cost_per_gfa' => 11.36,
                            ],
                            '6' => [
                                'description' => 'Communication Installation',
                                'cost_per_gfa' => 39.71,
                            ],
                            '7' => [
                                'description' => 'Special Installation',
                                'cost_per_gfa' => 88.77,
                            ],
                            '8' => [
                                'description' => "Builder's Profit & Attendance on Services",
                                'cost_per_gfa' => 8.12,
                            ],
                            '9' => [
                                'description' => "Builder's Work In Connection with Services",
                                'cost_per_gfa' => 7.26,
                            ],
                        ],
                    ],
                ],
            ],
            'Multi-Purpose Halls' => [
                'location' => 'J',
                'year' => 2025,
                'cost_breakdown' => [
                    'A' => [
                        'description' => 'SUBSTRUCTURE',
                        'children' => [
                            '1' => [
                                'description' => 'Piling',
                                'cost_per_gfa' => 186.38,
                            ],
                            '2' => [
                                'description' => 'Work Below Lowest Floor Finish',
                                'cost_per_gfa' => 453.67,
                            ],
                        ],
                    ],
                    'B' => [
                        'description' => 'SUPERSTRUCTURE',
                        'children' => [
                            '1' => [
                                'description' => 'Frame',
                                'cost_per_gfa' => 389.69,
                            ],
                            '2' => [
                                'description' => 'Roof',
                                'cost_per_gfa' => 357.52,
                            ],
                            '3' => [
                                'description' => 'Stairs',
                                'cost_per_gfa' => 188.78,
                            ],
                            '4' => [
                                'description' => 'External Wall',
                                'cost_per_gfa' => 23.47,
                            ],
                            '5' => [
                                'description' => 'Windows & External Doors',
                                'cost_per_gfa' => 59.16,
                            ],
                            '6' => [
                                'description' => 'Internal Walls & Partitions',
                                'cost_per_gfa' => 69.98,
                            ],
                            '7' => [
                                'description' => 'Internal Doors',
                                'cost_per_gfa' => 27.99,
                            ],
                            '8' => [
                                'description' => 'Ironmongery',
                                'cost_per_gfa' => 50.62,
                            ],
                        ],
                    ],
                    'C' => [
                        'description' => 'FINISHES',
                        'children' => [
                            '1' => [
                                'description' => 'Internal Wall Finishes',
                                'cost_per_gfa' => 105.75,
                            ],
                            '2' => [
                                'description' => 'Internal Floor Finishes',
                                'cost_per_gfa' => 45.62,
                            ],
                            '3' => [
                                'description' => 'Internal Ceiling Finishes',
                                'cost_per_gfa' => 166.08,
                            ],
                            '4' => [
                                'description' => 'External Finishes',
                                'cost_per_gfa' => 87.03,
                            ],
                        ],
                    ],
                    'D' => [
                        'description' => 'SERVICES',
                        'children' => [
                            '1' => [
                                'description' => 'Sanitary Appliances',
                                'cost_per_gfa' => 14.37,
                            ],
                            '2' => [
                                'description' => 'Plumbing Installations',
                                'cost_per_gfa' => 29.71,
                            ],
                            '3' => [
                                'description' => 'Air Conditionings and Ventilation Services',
                                'cost_per_gfa' => 2.71,
                            ],
                            '4' => [
                                'description' => 'Electrical Installation',
                                'cost_per_gfa' => 148.96,
                            ],
                            '5' => [
                                'description' => 'Fire Protection Installation',
                                'cost_per_gfa' => 1.23,
                            ],
                            '6' => [
                                'description' => 'Builder\'s Work in Connection with Services',
                                'cost_per_gfa' => 81.9,
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
                    'G' => [
                        'description' => 'MISCELLANEOUS',
                        'cost_per_gfa' => 23.58,
                    ],
                ]
            ],
            'Mosques' => [
                'location' => 'J', // Kuching
                'year' => 2025,
                'cost_breakdown' => [
                    'A' => [
                        'description' => 'SUBSTRUCTURE',
                        'children' => [
                            '1' => [
                                'description' => 'Work Below Lowest Floor Finish',
                                'cost_per_gfa' => 206.99,
                            ],
                        ],
                    ],
                    'B' => [
                        'description' => 'SUPERSTRUCTURE',
                        'children' => [
                            '1' => [
                                'description' => 'Frame',
                                'cost_per_gfa' => 95.61,
                            ],
                            '2' => [
                                'description' => 'Upper Floor',
                                'cost_per_gfa' => 5.43,
                            ],
                            '3' => [
                                'description' => 'Roof',
                                'cost_per_gfa' => 198.70,
                            ],
                            '4' => [
                                'description' => 'Stairs',
                                'cost_per_gfa' => 12.67,
                            ],
                            '5' => [
                                'description' => 'External Walls',
                                'cost_per_gfa' => 29.74,
                            ],
                            '6' => [
                                'description' => 'Windows',
                                'cost_per_gfa' => 34.15,
                            ],
                            '7' => [
                                'description' => 'Internal Walls & Partitions',
                                'cost_per_gfa' => 15.15,
                            ],
                            '8' => [
                                'description' => 'Doors',
                                'cost_per_gfa' => 9.22,
                            ]
                        ],
                    ],
                    'C' => [
                        'description' => 'FINISHES',
                        'children' => [
                            '1' => [
                                'description' => 'Wall Finishes',
                                'cost_per_gfa' => 52.34,
                            ],
                            '2' => [
                                'description' => 'Floor Finishes',
                                'cost_per_gfa' => 49.60,
                            ],
                            '3' => [
                                'description' => 'Ceiling Finishes',
                                'cost_per_gfa' => 27.19,
                            ],
                            '4' => [
                                'description' => 'External Finishes',
                                'cost_per_gfa' => 60.18,
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
                                'cost_per_gfa' => 24.27,
                            ],
                            '2' => [
                                'description' => 'Plumbing Installation',
                                'cost_per_gfa' => 19.89,
                            ],
                            '3' => [
                                'description' => 'Electrical Installation',
                                'cost_per_gfa' => 52.24,
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
                                'description' => "Builder's Profit & Attendance on Services",
                                'cost_per_gfa' => 1.11,
                            ],
                            '8' => [
                                'description' => 'Builder\'s Work in Connection with Services',
                                'cost_per_gfa' => 4.27,
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
                    'G' => [
                        'description' => 'MISCELLANEOUS',
                        'cost_per_gfa' => 29.06,
                    ],
                ]
            ],
            'Elevated Car Parks' => [
                'location' => 'J', // Kuching
                'year' => 2025,
                'cost_breakdown' => [
                    'A' => [
                        'description' => 'SUBSTRUCTURE',
                        'children' => [
                            '1' => [
                                'description' => 'Piling',
                                'cost_per_gfa' => 119.25,
                            ],
                            '2' => [
                                'description' => 'Work Below Lowest Floor Finish',
                                'cost_per_gfa' => 72.84,
                            ],
                        ],
                    ],
                    'B' => [
                        'description' => 'SUPERSTRUCTURE',
                        'children' => [
                            '1' => [
                                'description' => 'Frame',
                                'cost_per_gfa' => 129.28,
                            ],
                            '2' => [
                                'description' => 'Upper Floor',
                                'cost_per_gfa' => 210.65,
                            ],
                            '3' => [
                                'description' => 'Roof',
                                'cost_per_gfa' => 11.32,
                            ],
                            '4' => [
                                'description' => 'Stairs',
                                'cost_per_gfa' => 42.78,
                            ],
                            '5' => [
                                'description' => 'External Walls',
                                'cost_per_gfa' => 25.94,
                            ],
                            '6' => [
                                'description' => 'Windows & External Doors',
                                'cost_per_gfa' => 1.77,
                            ],
                            '7' => [
                                'description' => 'Internal Walls & Partitions',
                                'cost_per_gfa' => 7.36,
                            ],
                            '8' => [
                                'description' => 'Internal Doors',
                                'cost_per_gfa' => 4.00,
                            ],
                        ],
                    ],
                    'C' => [
                        'description' => 'FINISHES',
                        'children' => [
                            '1' => [
                                'description' => 'Internal Wall Finishes',
                                'cost_per_gfa' => 9.93,
                            ],
                            '2' => [
                                'description' => 'Internal Floor Finishes',
                                'cost_per_gfa' => 50.33,
                            ],
                            '3' => [
                                'description' => 'Internal Ceiling Finishes',
                                'cost_per_gfa' => 14.08,
                            ],
                            '4' => [
                                'description' => 'External Finishes',
                                'cost_per_gfa' => 11.73,
                            ],
                        ],
                    ],
                    'D' => [
                        'description' => 'FITTINGS AND FURNISHINGS',
                        'cost_per_gfa' => 0.15,
                    ],
                    'E' => [
                        'description' => 'SERVICES',
                        'children' => [
                            '1' => [
                                'description' => 'Sanitary Appliances',
                                'cost_per_gfa' => 3.90,
                            ],
                            '2' => [
                                'description' => 'Plumbing Installations',
                                'cost_per_gfa' => 8.83,
                            ],
                            '3' => [
                                'description' => 'Refuse Disposal',
                                'cost_per_gfa' => 0.01,
                            ],
                            '4' => [
                                'description' => 'Air Conditionings and Ventilation Services',
                                'cost_per_gfa' => 14.33,
                            ],
                            '5' => [
                                'description' => 'Electrical Installation',
                                'cost_per_gfa' => 88.59,
                            ],
                            '6' => [
                                'description' => 'Fire Protection Installation',
                                'cost_per_gfa' => 17.94,
                            ],
                            '7' => [
                                'description' => 'Lift & Conveyor Installation',
                                'cost_per_gfa' => 25.30,
                            ],
                            '8' => [
                                'description' => 'Communication Installation',
                                'cost_per_gfa' => 7.96,
                            ],
                            '9' => [
                                'description' => 'Special Installation',
                                'cost_per_gfa' => 76.86,
                            ],
                            '10' => [
                                'description' => "Builder's Profit & Attendance on Services",
                                'cost_per_gfa' => 6.42,
                            ],
                            '11' => [
                                'description' => "Builder's Work In Connection with Services",
                                'cost_per_gfa' => 0.64,
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $categoryIds = [12, 13, 14, 15, 16, 17, 18, 19]; // Assuming these are the category IDs for the above categories

        $prevProjectsTable = DB::table('prev_projects')->whereIn('category_id', $categoryIds)->get(['id', 'category_id', 'year', 'location_id']);
        $categoryMap = [];
        foreach ($prevProjectsTable as $row) {
            $categoryMap[$row->category_id . '-' . $row->year . '-' . $row->location_id] = $row->id;
        }

        $index = 1;

        foreach ($prevProjects as $categoryName => $project) {
            $prevProjectId = $index;
            $this->seedCostBreakdown($project['cost_breakdown'], $prevProjectId);
            $index++;
        }
    }

    private function seedCostBreakdown(array $breakdown, int $prevProjectId, $parentId = null, $level = 1)
    {
        foreach ($breakdown as $code => $node) {
            $cost = [
                'prev_project_id' => $prevProjectId,
                'code' => $code,
                'description' => $node['description'] ?? '',
                'cost_per_gfa' => $node['cost_per_gfa'] ?? null,
                'parent_id' => $parentId,
                'level' => $level,
                'created_at' => now(),
                'updated_at' => now(),
            ];
            $id = DB::table('prev_project_costs')->insertGetId($cost);
            if (isset($node['children'])) {
                $this->seedCostBreakdown($node['children'], $prevProjectId, $id, $level + 1);
            }
        }
    }
}
