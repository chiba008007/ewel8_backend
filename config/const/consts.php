<?php

return[
    'alpha' => 'abcdefghijklmnopqrstuvwxyz23456789',
    'PASSWORD' => [
        'key' => "ewel_secret_key",
        'key16' => "ewel_secret_key!",
        'iv' => "1234567890123456",
    ],
    'adminMail' => 'admin@newtestsvr.sakura.ne.jp',
    'status' => [
        0 => "未受検",
        1 => "受検中",
        2 => "受検済",
    ],
    'passflag' => [
        0 => "未指定",
        1 => "合格",
        2 => "不合格",
    ],
    'LISENCE' => [
        1 => [
            'code' => "BA",
            'text' => "行動価値検査",
            'list' => [
                // 1=>[
                //     "code"=>"BA-J1",
                //     "text"=>"行動価値検査1"
                // ],
                // 2=>[
                //     "code"=>"BA-J2",
                //     "text"=>"行動価値検査2"
                // ],
                3 => [
                    "code" => "BA-J3",
                    "text" => "行動価値検査3"
                ],
                4 => [
                    "code" => "BA-J4",
                    "text" => "行動価値検査4"
                ],
                5 => [
                    "code" => "PFS",
                    "text" => "PFS検査"
                ],
                6 => [
                    "code" => "PFS2",
                    "text" => "PFS検査2"
                ],
                7 => [
                    "code" => "PFS3",
                    "text" => "PFS検査3"
                ],
            ]
        ],
        2 => [
            'code' => "EA",
            'text' => "感情能力検査",
            'list' => [
                // 1=>[
                //     "code"=>"EA-S",
                //     "text"=>"感情能力検査"
                // ],
                2 => [
                    "code" => "EA-BJ",
                    "text" => "感情能力検査"
                ],
                3 => [
                    "code" => "EA-S2",
                    "text" => "感情能力検査"
                ],
                4 => [
                    "code" => "感情能力検査Ib",
                    "text" => "EA-Ib"
                ],
                5 => [
                    "code" => "EA-BJ2",
                    "text" => "感情能力検査"
                ],
                6 => [
                    "code" => "感情能力検査Ia",
                    "text" => "EA-Ia"
                ]
            ]
        ],
        3 => [
            'code' => "FS",
            'text' => "Welcome Fs検査",
            'list' => [
                1 => [
                    "code" => "Fs",
                    "text" => "Welcome Fs検査"
                ]
            ]
        ],
        4 => [
            'code' => "VF",
            'text' => "VF検査",
            'list' => [
                1 => [
                    "code" => "VF-J",
                    "text" => "VF検査"
                ],
                2 => [
                    "code" => "VF-J2",
                    "text" => "VF検査"
                ]
            ]
        ],
        5 => [
            'code' => "SA",
            'text' => "行動意識検査",
            'list' => [
                1 => [
                    "code" => "SA-J",
                    "text" => "行動意識検査"
                ],
                2 => [
                    "code" => "SA-J2",
                    "text" => "行動意識検査"
                ]
            ]
        ],
        6 => [
            'code' => "TH",
            'text' => "多面評価検査",
            'list' => [
                1 => [
                    "code" => "TH",
                    "text" => "多面評価検査"
                ],
            ]
        ],
        7 => [
            'code' => "IQ",
            'text' => "知的能力検査",
            'list' => [
                1 => [
                    "code" => "IQ",
                    "text" => "知的能力検査"
                ],
            ]
        ],
        8 => [
            'code' => "BMS",
            'text' => "数学検定検査",
            'list' => [
                1 => [
                    "code" => "BMS",
                    "text" => "数学検定検査"
                ],
                2 => [
                    "code" => "BMS2",
                    "text" => "数学検定検査"
                ],
                3 => [
                    "code" => "BMS3",
                    "text" => "数学検定検査"
                ],
            ]
        ],
        9 => [
            'code' => "OCS",
            'text' => "職場研究",
            'list' => [
                1 => [
                    "code" => "OCS",
                    "text" => "職場研究"
                ],
            ]
        ],
        10 => [
            'code' => "nl",
            'text' => "NL検査",
            'list' => [
                1 => [
                    "code" => "NL-J",
                    "text" => "NL検査"
                ],
                2 => [
                    "code" => "NL-J2",
                    "text" => "NL検査"
                ],
                3 => [
                    "code" => "NL-J3",
                    "text" => "NL検査"
                ],
            ]
        ],
        11 => [
            'code' => "pa",
            'text' => "親用検査",
            'list' => [
                1 => [
                    "code" => "PA-J",
                    "text" => "親用検査"
                ],
            ]
        ],
        12 => [
            'code' => "sp",
            'text' => "共感力アセスメント",
            'list' => [
                1 => [
                    "code" => "SP-J",
                    "text" => "共感力アセスメント"
                ],
            ]
        ],
        13 => [
            'code' => "met",
            'text' => "モンスター社員タイプ診断",
            'list' => [
                1 => [
                    "code" => "MET",
                    "text" => "モンスター社員タイプ診断"
                ],
            ]
        ],
        14 => [
            'code' => "bav",
            'text' => "行動価値検査(ベトナム語)",
            'list' => [
                1 => [
                    "code" => "BA-V3",
                    "text" => "行動価値検査(ベトナム語)"
                ],
            ]
        ],
        15 => [
            'code' => "lcp",
            'text' => "LCP検査",
            'list' => [
                1 => [
                    "code" => "LCP",
                    "text" => "LCP検査"
                ],
            ]
        ],
        16 => [
            'code' => "crt",
            'text' => "添削",
            'list' => [
                1 => [
                    "code" => "CRT",
                    "text" => "添削"
                ],
                2 => [
                    "code" => "CRT2",
                    "text" => "添削"
                ],
            ]
        ],
        17 => [
            'code' => "esa",
            'text' => "経済感度力アセスメント",
            'list' => [
                1 => [
                    "code" => "ESA",
                    "text" => "経済感度力アセスメント"
                ],
            ]
        ],
        18 => [
            'code' => "mms",
            'text' => "メンタルヘルス対策実施状況診断",
            'list' => [
                1 => [
                    "code" => "MMS",
                    "text" => "メンタルヘルス対策実施状況診断"
                ],
            ]
        ],
        19 => [
            'code' => "elan",
            'text' => "人権ラーニング",
            'list' => [
                1 => [
                    "code" => "ELAN",
                    "text" => "人権e-ラーニング"
                ],
                2 => [
                    "code" => "ELAN2",
                    "text" => "人権e-ラーニング2"
                ],
                3 => [
                    "code" => "ELAN3",
                    "text" => "人権e-ラーニング3"
                ],
                4 => [
                    "code" => "ELAN4",
                    "text" => "人権e-ラーニング4"
                ],
                5 => [
                    "code" => "ELAN5",
                    "text" => "人権e-ラーニング5"
                ],
                6 => [
                    "code" => "ELAN6",
                    "text" => "人権e-ラーニング6"
                ],
                7 => [
                    "code" => "ELAN7",
                    "text" => "人権e-ラーニング7"
                ],
                8 => [
                    "code" => "ELAN8",
                    "text" => "人権e-ラーニング8"
                ],
            ]
        ],
        20 => [
            'code' => "mea",
            'text' => "MEA",
            'list' => [
                1 => [
                    "code" => "MEA",
                    "text" => "MEA"
                ],
            ]
        ],
        21 => [
            'code' => "bsa",
            'text' => "BSA",
            'list' => [
                1 => [
                    "code" => "BSA",
                    "text" => "BSA"
                ],
                2 => [
                    "code" => "BCO",
                    "text" => "BCO"
                ],
            ]
        ],
        22 => [
            'code' => "jug",
            'text' => "評価検査",
            'list' => [
                1 => [
                    "code" => "JUG",
                    "text" => "評価検査"
                ],
                2 => [
                    "code" => "JUG2",
                    "text" => "評価検査2"
                ],
                3 => [
                    "code" => "JUG3",
                    "text" => "評価検査3"
                ],
                4 => [
                    "code" => "JUG4",
                    "text" => "評価検査4"
                ],
                5 => [
                    "code" => "JUG5",
                    "text" => "評価検査5"
                ],
                6 => [
                    "code" => "JUG6",
                    "text" => "評価検査6"
                ],
                7 => [
                    "code" => "JUG7",
                    "text" => "評価検査7"
                ],
            ]
        ],
        23 => [
            'code' => "ch",
            'text' => "CH",
            'list' => [
                1 => [
                    "code" => "BA-C",
                    "text" => "BA-C"
                ],
                2 => [
                    "code" => "CBA-C",
                    "text" => "CBA-C"
                ],
                3 => [
                    "code" => "AAC-T",
                    "text" => "AAC-T"
                ],
                4 => [
                    "code" => "AAP-T",
                    "text" => "AAP-T"
                ],
            ]
        ],
        24 => [
            'code' => "elans",
            'text' => "ELANS",
            'list' => [
                1 => [
                    "code" => "elans1",
                    "text" => "ELANS1"
                ],
                2 => [
                    "code" => "elans1",
                    "text" => "ELANS2"
                ],
            ]
        ],
        25 => [
            'code' => "bea",
            'text' => "BEA",
            'list' => [
                1 => [
                    "code" => "bea",
                    "text" => "BEA"
                ],
            ]
        ],
        26 => [
            'code' => "cres",
            'text' => "Cres",
            'list' => [
                1 => [
                    "code" => "cres",
                    "text" => "Cres"
                ],
            ]
        ],
        27 => [
            'code' => "nspe",
            'text' => "ISO9001と品質ﾏﾆｭｱﾙ",
            'list' => [
                1 => [
                    "code" => "nspe1",
                    "text" => "ISO9001と品質ﾏﾆｭｱﾙ"
                ],
                2 => [
                    "code" => "nspe2",
                    "text" => "不適合とその対応"
                ],
                3 => [
                    "code" => "nspe3",
                    "text" => "文書管理"
                ],
                4 => [
                    "code" => "nspe4",
                    "text" => "その他共通要領書、手順書"
                ],
            ]
        ],
        28 => [
            'code' => "amp",
            'text' => "AMP",
            'list' => [
                1 => [
                    "code" => "AMP",
                    "text" => "AMP"
                ],
            ]
        ],
        29 => [
            'code' => "mhq",
            'text' => "MHQ",
            'list' => [
                1 => [
                    "code" => "MHQ",
                    "text" => "MHQ"
                ],
            ]
        ],
    ],

];
