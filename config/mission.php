<?php
return [
    'lines' => [
        'RER A' => [
            'branches' => [
                'saint-germain' => [
                    'outbound' => 'S',
                    'inbound'  => 'G',
                ],
                'cergy' => [
                    'outbound' => 'C',
                    'inbound'  => 'Y',
                ],
                'poissy' => [
                    'outbound' => 'P',
                    'inbound'  => 'Y',
                ],
                'boissy' => [
                    'outbound' => 'B',
                    'inbound'  => 'O',
                ],
                'marne' => [
                    'outbound' => 'Q',
                    'inbound'  => 'M',
                ],
            ],
        ],
        'RER B' => [
            'branches' => [
                'cdg2' => [
                    'outbound' => 'K',
                    'inbound'  => 'K',
                ],
                'mitry' => [
                    'outbound' => 'M',
                    'inbound'  => 'M',
                ],
                'robinson' => [
                    'outbound' => 'R',
                    'inbound'  => 'R',
                ],
                'saint-remy' => [
                    'outbound' => 'S',
                    'inbound'  => 'S',
                ],
            ],
        ],
        'RER C' => [
            'branches' => [
                'versailles' => [
                    'outbound' => 'V',
                    'inbound'  => 'W',
                ],
                'pontoise' => [
                    'outbound' => 'P',
                    'inbound'  => 'O',
                ],
                'massy' => [
                    'outbound' => 'M',
                    'inbound'  => 'N',
                ],
            ],
        ],
        'RER D' => [
            'branches' => [
                'melun' => [
                    'outbound' => 'M',
                    'inbound'  => 'L',
                ],
                'malesherbes' => [
                    'outbound' => 'H',
                    'inbound'  => 'E',
                ],
            ],
        ],
        'RER E' => [
            'branches' => [
                'tournan' => [
                    'outbound' => 'T',
                    'inbound'  => 'N',
                ],
                'chelles' => [
                    'outbound' => 'C',
                    'inbound'  => 'L',
                ],
            ],
        ],
        'RER H' => [
            'branches' => [
                'persan-beaumont' => [
                    'outbound' => 'P',
                    'inbound'  => 'B',
                ],
                'pontoise' => [
                    'outbound' => 'P',
                    'inbound'  => 'Z',
                ],
            ],
        ],
        'RER J' => [
            'branches' => [
                'mantes-la-jolie' => [
                    'outbound' => 'M',
                    'inbound'  => 'J',
                ],
                'gisors' => [
                    'outbound' => 'G',
                    'inbound'  => 'R',
                ],
            ],
        ],
        'RER K' => [
            'branches' => [
                'crepy' => [
                    'outbound' => 'C',
                    'inbound'  => 'K',
                ],
            ],
        ],
        'RER L' => [
            'branches' => [
                'versailles-rd' => [
                    'outbound' => 'V',
                    'inbound'  => 'R',
                ],
                'saint-nom' => [
                    'outbound' => 'S',
                    'inbound'  => 'N',
                ],
            ],
        ],
        'RER N' => [
            'branches' => [
                'rambouillet' => [
                    'outbound' => 'R',
                    'inbound'  => 'T',
                ],
                'dreux' => [
                    'outbound' => 'D',
                    'inbound'  => 'X',
                ],
            ],
        ],
        'RER U' => [
            'branches' => [
                'versailles-chantiers' => [
                    'outbound' => 'C',
                    'inbound'  => 'H',
                ],
            ],
        ],
        'RER V' => [
            'branches' => [
                'verneuil' => [
                    'outbound' => 'V',
                    'inbound'  => 'U',
                ],
            ],
        ],
    ],

    'serviceTypes' => [
        'omnibus'      => 'O',
        'semi-direct'  => 'D',
        'direct'       => 'X',
    ],

    'timeSlots' => [
        'pointe' => 'P',
        'creuse' => 'F',
        'matin'  => 'M',
        'soir'   => 'R',
    ],

    'lastLetters' => ['A', 'E', 'I', 'O', 'U'],
];
