<?php

return [
    'Light duty factories' => [
        'location' => 'A',
        'year' => 2024,
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
                'description' => 'PRELIMINARY AND GENERALLY',
                'cost' => 133000,
                'gfa' => 3295,
            ],
            'B' => [
                'description' => 'PILING WORKS',
                'gfa' => 3295,
                'children' => [
                    '1' => [
                        'description' => 'As built drawing, piling record, equipment',
                        'cost' => 8888,
                    ],
                    '2' => [
                        'description' => '150 x 150 RC Piles',
                        'children' => [
                            '2a' => [
                                'description' => '→ Supply - 6m',
                                'cost' => 26640,
                            ],
                            '2b' => [
                                'description' => '→ Supply - 3m',
                                'cost' => 13320,
                            ],
                            '2c' => [
                                'description' => '→ Drive',
                                'cost' => 13320,
                            ],
                            '2d' => [
                                'description' => '→ Jointing',
                                'cost' => 740,
                            ],
                            '2e' => [
                                'description' => '→ Cut off excess length',
                                'cost' => 2960,
                            ],
                        ],
                    ],
                    '3' => [
                        'description' => '200 x 200 RC Piles',
                        'children' => [
                            '3a' => [
                                'description' => '→ Supply - 6m',
                                'cost' => 37800,
                            ],
                            '3b' => [
                                'description' => '→ Supply - 3m',
                                'cost' => 18900,
                            ],
                            '3c' => [
                                'description' => '→ Drive',
                                'cost' => 13608,
                            ],
                            '3d' => [
                                'description' => '→ Jointing',
                                'cost' => 882,
                            ],
                            '3e' => [
                                'description' => '→ Cut off excess length',
                                'cost' => 3780,
                            ],
                        ],
                    ],
                    '4' => [
                        'description' => 'Test pile',
                        'cost' => 5100,
                    ],
                    '5' => [
                        'description' => '100mm Dia. Bakau Pile',
                        'children' => [
                            '5a' => [
                                'description' => '→ Supply - 6m',
                                'cost' => 3960,
                            ],
                            '5b' => [
                                'description' => '→ Drive',
                                'cost' => 2640,
                            ],
                            '5c' => [
                                'description' => '→ Jointing',
                                'cost' => 120,
                            ],
                            '5d' => [
                                'description' => '→ Cut off excess length',
                                'cost' => 428,
                            ],
                        ],
                    ],
                ],
            ],
            'C' => [
                'description' => 'BUILDING WORKS',
                'gfa' => 1060,
                'children' => [
                    'C1' => [
                        'description' => 'MULTIPURPOSE HALL',
                        'children' => [
                            '1' => [
                                'description' => 'Structural Works',
                                'children' => [
                                    '1a' => ['description' => 'Excavation', 'cost' => 4000],
                                    '1b' => ['description' => 'Lean Concrete-G15', 'cost' => 6600],
                                    '1c' => ['description' => 'Concrete-G25', 'cost' => 103350],
                                    '1d' => ['description' => 'Formwork', 'cost' => 75900],
                                    '1e' => ['description' => 'Reinforcement', 'cost' => 105400],
                                    '1f' => ['description' => 'BMC A8', 'cost' => 10800],
                                    '1g' => ['description' => 'BMC A10', 'cost' => 15300],
                                    '1h' => ['description' => 'Anti-termite', 'cost' => 2750],
                                ]
                            ],
                            '2' => [
                                'description' => 'Brickwall',
                                'children' => [
                                    '2a' => ['description' => '115mm Thick, external & internal wall', 'cost' => 38500],
                                    '2b' => ['description' => '230mm Thick, ditto', 'cost' => 6030],
                                ]
                            ],
                            '3' => [
                                'description' => 'Floor Finishes',
                                'children' => [
                                    '3a' => ['description' => '300x300 Kingress HT1', 'cost' => 20800],
                                    '3b' => ['description' => '200x200 Kingress HT2', 'cost' => 2695],
                                    '3c' => ['description' => 'Cement and Sand Screed', 'cost' => 18200],
                                    '3d' => ['description' => 'Merbau solid timberboard including paint', 'cost' => 6660],
                                ]
                            ],
                            '4' => [
                                'description' => 'Wall Finishes',
                                'children' => [
                                    '4a' => ['description' => 'Plastering', 'cost' => 38500],
                                    '4b' => ['description' => '2AL3-DML BL100x20 box louvres', 'cost' => 67420],
                                    '4c' => ['description' => '200x200 Ceramic tile CT1', 'cost' => 6500],
                                ]
                            ],
                            '5' => [
                                'description' => 'Ceiling Finishes',
                                'children' => [
                                    '5a' => ['description' => 'AS1-DML 100G aluminium strip ceiling', 'cost' => 112750],
                                    '5b' => ['description' => 'AS2-DML 85L aluminium strip ceiling', 'cost' => 43400],
                                    '5c' => ['description' => 'FP1-Gypsum fibrous plaster', 'cost' => 1800],
                                    '5d' => ['description' => 'UAC-6mm thick', 'cost' => 5280],
                                    '5e' => ['description' => 'SOS-Cement render', 'cost' => 8320],
                                    '5f' => ['description' => 'CB colorbond sheet', 'cost' => 4500],
                                ]
                            ],
                            '6' => [
                                'description' => 'Door',
                                'children' => [
                                    '6a' => ['description' => 'D11-Door frame [1800x2150]', 'cost' => 660],
                                    '6b' => ['description' => 'D12-Door frame [900x2150]', 'cost' => 720],
                                    '6c' => ['description' => 'D13-Fire door', 'cost' => 1300],
                                    '6d' => ['description' => 'Supply Ironmongery', 'cost' => 53655],
                                ]
                            ],
                            '7' => [
                                'description' => 'Sanitary Fittings',
                                'children' => [
                                    '7a' => ['description' => 'WC1-Water closet', 'cost' => 2790],
                                    '7b' => ['description' => 'FV-Dual flush WC', 'cost' => 2190],
                                    '7c' => ['description' => 'UR1-Urinal', 'cost' => 750],
                                    '7d' => ['description' => 'ET-Waterless urinal Eco-trap', 'cost' => 350],
                                    '7e' => ['description' => 'WB1-Wall hung basin', 'cost' => 940],
                                    '7f' => ['description' => 'TF1-Self closing pillar tap', 'cost' => 360],
                                    '7g' => ['description' => 'SK3-S/S single bowl', 'cost' => 600],
                                    '7h' => ['description' => 'TF4-Bib Tap', 'cost' => 35],
                                    '7i' => ['description' => 'TF3-hand spray', 'cost' => 300],
                                    '7j' => ['description' => 'FT-Floor trap', 'cost' => 320],
                                    '7k' => ['description' => 'PH-Paper holder', 'cost' => 300],
                                    '7l' => ['description' => 'SDP-Soap Dispenser', 'cost' => 360],
                                ]
                            ],
                            '8' => [
                                'description' => 'Staircase',
                                'children' => [
                                    '8a' => ['description' => 'Concrete-G25', 'cost' => 1060],
                                    '8b' => ['description' => 'Form work', 'cost' => 1650],
                                    '8c' => ['description' => 'Reinforcement', 'cost' => 17000],
                                    '8d' => ['description' => 'Nosing Tile', 'cost' => 525],
                                    '8e' => ['description' => 'RR2-Hot dipped galvanised ms handrail', 'cost' => 1000],
                                ]
                            ],
                            '9' => [
                                'description' => 'Builder\'s Work',
                                'children' => [
                                    '9a' => ['description' => 'RR1-Hot dipped galvanised ms handrail', 'cost' => 52500],
                                    '9b' => ['description' => 'CL1-MS staircase 0.6m [w] x 4.125 [h]', 'cost' => 5500],
                                ]
                            ],
                        ]
                    ],
                    'C2' => [
                        'description' => 'FOOD COURT',
                        'gfa' => 645,
                        'children' => [
                            '1' => [
                                'description' => 'Structural Works',
                                'children' => [
                                    '1a' => ['description' => 'Excavation', 'cost' => 2560],
                                    '1b' => ['description' => '100mm Thick Hardcore', 'cost' => 3350],
                                    '1c' => ['description' => 'Lean Concrete-G15', 'cost' => 8640],
                                    '1d' => ['description' => 'Concrete-G25', 'cost' => 31800],
                                    '1e' => ['description' => 'Formwork', 'cost' => 6534],
                                    '1f' => ['description' => 'Reinforcement', 'cost' => 12977.80],
                                    '1g' => ['description' => 'BMC A10', 'cost' => 11826],
                                    '1h' => ['description' => 'Anti-termite', 'cost' => 3600],
                                ]
                            ],
                            '2' => [
                                'description' => 'Steel Structural Works',
                                'children' => [
                                    '2a' => ['description' => 'SHS [100mmx100mmx3.2mm thick]', 'cost' => 8614.82],
                                    '2b' => ['description' => 'SHS [100mmx50mmx2.3mm thick]', 'cost' => 1595.97],
                                    '2c' => ['description' => '500mmx500mmx8mm thick ms plate', 'cost' => 8000],
                                ]
                            ],
                            '3' => [
                                'description' => 'Brickwall',
                                'children' => [
                                    '3a' => ['description' => '115mm Thick, external & internal wall', 'cost' => 16940.82],
                                ]
                            ],
                            '4' => [
                                'description' => 'Floor Finishes',
                                'children' => [
                                    '4a' => ['description' => '300x300 Kingress HT', 'cost' => 24570],
                                    '4b' => ['description' => '200x200 Kingress HT2', 'cost' => 11704],
                                    '4c' => ['description' => 'Cement and sand screed', 'cost' => 798],
                                    '4d' => ['description' => '200x200 Ceramic Tile CT1', 'cost' => 12350],
                                ]
                            ],
                            '5' => [
                                'description' => 'Wall Finishes',
                                'children' => [
                                    '5a' => ['description' => 'Plastering', 'cost' => 10528],
                                    '5b' => ['description' => '200x200 Ceramic tile CT1', 'cost' => 21905],
                                    '5c' => ['description' => 'AL1-Z-profile ventilation louvres', 'cost' => 37440],
                                    '5d' => ['description' => 'AL4-Zincalume u-channel strip', 'cost' => 54540],
                                ]
                            ],
                            '6' => [
                                'description' => 'Ceiling Finishes',
                                'children' => [
                                    '6a' => ['description' => 'AS2-DML 85L aluminium strip ceiling', 'cost' => 56420],
                                    '6b' => ['description' => 'UAC-6mm thick', 'cost' => 22275],
                                ]
                            ],
                            '7' => [
                                'description' => 'Concrete Bench',
                                'children' => [
                                    '7a' => ['description' => '7.8m', 'cost' => 25600],
                                    '7b' => ['description' => '5.2m', 'cost' => 5200],
                                    '7c' => ['description' => '4.4m', 'cost' => 4400],
                                ]
                            ],
                            '8' => [
                                'description' => 'Sanitary Fittings',
                                'children' => [
                                    '8a' => ['description' => 'WB2-Wall hung basin', 'cost' => 1740],
                                    '8b' => ['description' => 'TF2-Self closing pillar tap', 'cost' => 1440],
                                    '8c' => ['description' => 'SK1-Double bowl Drainer', 'cost' => 940],
                                    '8d' => ['description' => 'SK2-Single bowl drainer', 'cost' => 2880],
                                    '8e' => ['description' => 'SK3-S/S single bowl', 'cost' => 600],
                                    '8f' => ['description' => 'TF4-Bib Tap', 'cost' => 35],
                                    '8g' => ['description' => 'TF5-Pillar sink', 'cost' => 1920],
                                    '8h' => ['description' => 'FT-Floor trap', 'cost' => 1480],
                                    '8i' => ['description' => 'FW-Floor grating', 'cost' => 280],
                                    '8j' => ['description' => 'SDP-Soap dispenser', 'cost' => 540],
                                ]
                            ],
                            '9' => [
                                'description' => 'Door',
                                'children' => [
                                    '9a' => ['description' => 'D11-Door frame', 'cost' => 132],
                                    '9b' => ['description' => 'D12-Door frame', 'cost' => 720],
                                    '9c' => ['description' => 'D13-Fire door', 'cost' => 1300],
                                    '9d' => ['description' => 'Supply Ironmongery', 'cost' => 11000],
                                ]
                            ],
                            '10' => [
                                'description' => "Builder's Work",
                                'children' => [
                                    '10a' => ['description' => 'New 2400x600 Stainless steel rack', 'cost' => 20000],
                                    '10b' => ['description' => 'New 1200x600 S/S cooker hook', 'cost' => 24000],
                                    '10c' => ['description' => 'New 1800x750 S/S cooker hook', 'cost' => 24000],
                                    '10d' => ['description' => 'Planter box', 'cost' => 5824.41],
                                ]
                            ],
                            '11' => [
                                'description' => 'Pond',
                                'children' => [
                                    '11a' => ['description' => 'Excavation', 'cost' => 4000],
                                    '11b' => ['description' => 'Lean Concrete-G15', 'cost' => 1200],
                                    '11c' => ['description' => 'Concrete-G25', 'cost' => 12985],
                                    '11d' => ['description' => 'Waterproofing', 'cost' => 15000],
                                    '11e' => ['description' => 'Formwork', 'cost' => 9141],
                                    '11f' => ['description' => 'Reinforcement', 'cost' => 11957.80],
                                    '11g' => ['description' => 'Cement and sand screed', 'cost' => 9996],
                                ]
                            ],
                        ],
                    ],
                    'C3' => [
                        'description' => 'DECK 1, 2 & 3 INCLUDING POND',
                        'gfa' => 960,
                        'children' => [
                            '1' => [
                                'description' => 'Structural Works',
                                'children' => [
                                    '1a' => ['description' => 'Excavation', 'cost' => 2400],
                                    '1b' => ['description' => 'Lean Concrete-G15', 'cost' => 8400],
                                    '1c' => ['description' => 'Concrete-G25', 'cost' => 24645],
                                    '1d' => ['description' => 'Formwork', 'cost' => 5115],
                                    '1e' => ['description' => 'Reinforcement', 'cost' => 6324],
                                    '1f' => ['description' => 'BMC A7', 'cost' => 8640],
                                    '1g' => ['description' => 'BMC A8', 'cost' => 2304],
                                    '1h' => ['description' => 'BMC A10', 'cost' => 3456],
                                    '1i' => ['description' => 'Anti-termite', 'cost' => 2500],
                                ]
                            ],
                            '2' => [
                                'description' => 'Steel Structural Works',
                                'children' => [
                                    '2a' => ['description' => 'CHS [219.1mm Dia. x 6.35mm thick]', 'cost' => 25592],
                                    '2b' => ['description' => 'RHS [125mm x 75mm x 3.2mm thick]', 'cost' => 8640],
                                    '2c' => ['description' => '400mm Dia. x 1.2mm thick MS plate', 'cost' => 4120.20],
                                ]
                            ],
                            '3' => [
                                'description' => 'Floor Finishes',
                                'children' => [
                                    '3a' => ['description' => 'Cement and sand screed', 'cost' => 3948],
                                    '3b' => ['description' => 'Merbau solid timberboard including paint', 'cost' => 78366],
                                ]
                            ],
                            '4' => [
                                'description' => 'Ceiling Finishes',
                                'children' => [
                                    '4a' => ['description' => 'AS1-DML 100G aluminium strip ceiling', 'cost' => 97625],
                                    '4b' => ['description' => 'AS2-DML 85L aluminium strip ceiling', 'cost' => 86645],
                                ]
                            ],
                            '5' => [
                                'description' => 'Door',
                                'children' => [
                                    '5a' => ['description' => 'Supply Ironmongery', 'cost' => 57000],
                                ]
                            ],
                        ],
                    ],
                    'C4' => [
                        'description' => 'EXISTING FOOD STORE',
                        'gfa' => 645,
                        'children' => [
                            '1' => [
                                'description' => 'Labour and Plant for Demolish Existing',
                                'children' => [
                                    '1a' => ['description' => 'Demolished brickwall with tiles', 'cost' => 8000],
                                    '1b' => ['description' => 'Demolished counter top and sink', 'cost' => 8000],
                                    '1c' => ['description' => 'Demolished RC hood', 'cost' => 8000],
                                    '1d' => ['description' => 'Demolished RC low wall', 'cost' => 8000],
                                    '1e' => ['description' => 'Demolished RC kerb', 'cost' => 7160],
                                    '1f' => ['description' => 'Dismantle roller shutter', 'cost' => 7000],
                                    '1g' => ['description' => 'Dismantle existing ceiling', 'cost' => 8000],
                                    '1h' => ['description' => 'Dismantle existing exhaust duct', 'cost' => 7000],
                                    '1i' => ['description' => 'Dismantle existing tap and valve', 'cost' => 7000],
                                ]
                            ],
                            '2' => [
                                'description' => 'Brickwall',
                                'children' => [
                                    '2a' => ['description' => '115mm Thick, external & internal wall', 'cost' => 3500],
                                ]
                            ],
                            '3' => [
                                'description' => 'Floor Finishes',
                                'children' => [
                                    '3a' => ['description' => '20mm Thick cement and sand screed', 'cost' => 7000],
                                    '3b' => ['description' => 'Merbau solid timberboard deck with paint', 'cost' => 17760],
                                    '3c' => ['description' => 'MS support for timberboard deck', 'cost' => 30400],
                                ]
                            ],
                            '4' => [
                                'description' => 'Wall Finishes',
                                'children' => [
                                    '4a' => ['description' => 'Plastering', 'cost' => 3200],
                                ]
                            ],
                            '5' => [
                                'description' => 'Ceiling Finishes',
                                'children' => [
                                    '5a' => ['description' => 'UAC-6mm thick', 'cost' => 16500],
                                ]
                            ],
                            '6' => [
                                'description' => 'Door',
                                'children' => [
                                    '6a' => ['description' => 'Supply Ironmongery', 'cost' => 13000],
                                ]
                            ],
                            '7' => [
                                'description' => 'Sanitary Fittings',
                                'children' => [
                                    '7a' => ['description' => 'FV - Dual flush WC', 'cost' => 10220],
                                    '7b' => ['description' => 'ET - Waterless urinal Eco-trap', 'cost' => 1400],
                                    '7c' => ['description' => 'TF1 - Self closing pillar tap', 'cost' => 3960],
                                ]
                            ],
                        ],
                    ],
                ],
            ],
            'D' => [
                'description' => 'EXTERNAL WORKS',
                'gfa' => 3295,
                'children' => [
                    'D1' => [
                        'description' => 'Site Preparation and Earthwork',
                        'children' => [
                            '1a' => ['description' => 'Site Preparation', 'cost' => 4400],
                            '1b' => ['description' => 'Earthwork', 'cost' => 3600],
                            '1c' => ['description' => 'Recycled construction waste as filling material (from demolition of existing footpath as hardcore)', 'cost' => 3500],
                            '1d' => ['description' => 'Excavated soil as filling material (External Work)', 'cost' => 3500],
                        ],
                    ],
                    'D2' => [
                        'description' => 'Covered Walkway, Footpath, Ramps',
                        'children' => [
                            '2a' => ['description' => 'Excavation', 'cost' => 320],
                            '2b' => ['description' => 'Lean Concrete-G15', 'cost' => 3012],
                            '2c' => ['description' => '100mm Thick hardcore', 'cost' => 1250],
                            '2d' => ['description' => 'Concrete-G25', 'cost' => 8480],
                            '2e' => ['description' => 'Formwork', 'cost' => 4290],
                            '2f' => ['description' => 'Reinforcement', 'cost' => 578],
                            '2g' => ['description' => 'Cement and sand screed', 'cost' => 1960],
                            '2h' => ['description' => 'UAC ceiling', 'cost' => 9900],
                            '2i' => ['description' => 'CHS (101.1 Dia. x 3.65mm x 8.75kg/m)', 'cost' => 6210],
                        ],
                    ],
                    'D3' => [
                        'description' => 'Surface Water Drainage',
                        'children' => [
                            '3a' => ['description' => '230mm Wide open drain', 'cost' => 5670],
                            '3b' => ['description' => '300mm Wide covered drain', 'cost' => 15480],
                            '3c' => ['description' => '230/350mm RC drain', 'cost' => 5610],
                            '3d' => ['description' => 'Cascading concrete drain', 'cost' => 2210],
                            '3e' => ['description' => 'Concrete sump', 'cost' => 1130],
                            '3f' => ['description' => 'MS grille cover', 'cost' => 900],
                        ],
                    ],
                    'D4' => [
                        'description' => 'Foul Drainage',
                        'children' => [
                            '4a' => ['description' => 'Sewage piping', 'cost' => 2511],
                            '4b' => ['description' => 'Manhole', 'cost' => 11000],
                            '4c' => ['description' => 'Septic tank PS10', 'cost' => 4489],
                        ],
                    ],
                    'D5' => [
                        'description' => 'Water Reticulation',
                        'cost' => 8518,
                    ],
                    'D6' => [
                        'description' => 'Road Works',
                        'cost' => 13396,
                    ],
                    'D7' => [
                        'description' => 'Bin Centre',
                        'cost' => 6000,
                    ],
                ],
            ],
            'E' => [
                'description' => 'REUSED AND RECYCLED WORKS',
                'gfa' => 3295,
                'children' => [
                    '1' => [
                        'description' => 'Reused Items',
                        'children' => [
                            '1a' => ['description' => '2400×600 Stainless steel rack', 'cost' => 3000],
                            '1b' => ['description' => 'Stainless steel counter top', 'cost' => 3000],
                            '1c' => ['description' => '1200×600×500 Stainless steel cooker hood', 'cost' => 6000],
                            '1d' => ['description' => 'Corridor railing', 'cost' => 3000],
                            '1e' => ['description' => 'Doors', 'cost' => 3000],
                            '1f' => ['description' => 'Timber strip', 'cost' => 3000],
                            '1g' => ['description' => 'Stainless steel sink and tap', 'cost' => 3000],
                            '1h' => ['description' => 'Mist fans', 'cost' => 3000],
                            '1i' => ['description' => 'Fire extinguishers', 'cost' => 2000],
                        ],
                    ],
                    '2' => [
                        'description' => 'Recycled debris of demolished brickwall or other approved construction debris',
                        'children' => [
                            '2a' => ['description' => 'Existing brickwall with tiles as hardcore to bin centre and road base', 'cost' => 1085],
                            '2b' => ['description' => 'Existing counter top ditto', 'cost' => 100],
                            '2c' => ['description' => 'Existing RC hood ditto', 'cost' => 100],
                            '2d' => ['description' => 'Existing RC low wall ditto', 'cost' => 50],
                            '2e' => ['description' => 'Existing RC kerb ditto', 'cost' => 25],
                            '2f' => ['description' => 'Excavated material on site [Buildings]', 'cost' => 3640],
                        ],
                    ],
                ],
            ],
            'F' => [
                'description' => 'MECHANICAL & ELECTRICAL SERVICES',
                'cost' => 980000,
                'gfa' => 3295,
            ],
            'G' => [
                'description' => 'PRIME COST SUM',
                'gfa' => 3422,
                'children' => [
                    'G1' => [
                        'description' => 'Roofing and Roof Plumbing',
                        'children' => [
                            '1a' => ['description' => 'Metal roof truss', 'cost' => 212181.71],
                            '1b' => ['description' => 'Metal roofing sheet', 'cost' => 198471.44],
                            '1c' => ['description' => 'Sisalation', 'cost' => 17200],
                            '1d' => ['description' => 'Insulation', 'cost' => 46400],
                            '1e' => ['description' => 'Metal roof accessories', 'cost' => 117500],
                            '1f' => ['description' => 'AL4 - Zincalume U-channel strip', 'cost' => 9246.85],
                        ],
                    ],
                    'G2' => [
                        'description' => 'PVDF Coated Membrane',
                        'cost' => 50000,
                    ],
                    'G3' => [
                        'description' => 'Aluminium Frame and Glazing',
                        'children' => [
                            '3a' => ['description' => 'Aluminium Works', 'cost' => 230350],
                            '3b' => ['description' => 'Glazing', 'cost' => 62150],
                        ],
                    ],
                    'G4' => [
                        'description' => 'Painting Works',
                        'cost' => 39500,
                    ],
                    'G5' => [
                        'description' => 'Sport Flooring',
                        'cost' => 35000,
                    ],
                    'G6' => [
                        'description' => 'P&A for Item G1 to G5',
                        'cost' => 50900,
                    ],
                ],
            ],
            'H' => [
                'description' => 'PROVISIONAL SUM',
                'gfa' => 3180,
                'children' => [
                    'H1' => ['description' => 'Signage', 'cost' => 5000],
                    'H2' => ['description' => 'Bus Stand', 'cost' => 15000],
                    'H3' => ['description' => 'Bicycle Stand', 'cost' => 5000],
                    'H4' => ['description' => 'Contingency', 'cost' => 5000],
                ],
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
