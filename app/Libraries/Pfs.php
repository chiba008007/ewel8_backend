<?php

namespace App\Libraries;

use App\Models\exampfs;

class Pfs
{
    public function getPfs($exam_id)
    {
        $where = [
                ["exam_id","=",$exam_id]
        ];
        $result = exampfs::select(["exampfses.*"])
        ->selectRaw('FORMAT(ROUND(CAST(dev1 AS FLOAT),1),1) as dev1n')
        ->selectRaw('FORMAT(ROUND(CAST(dev2 AS FLOAT),1),1) as dev2n')
        ->selectRaw('FORMAT(ROUND(CAST(dev3 AS FLOAT),1),1) as dev3n')
        ->selectRaw('FORMAT(ROUND(CAST(dev4 AS FLOAT),1),1) as dev4n')
        ->selectRaw('FORMAT(ROUND(CAST(dev5 AS FLOAT),1),1) as dev5n')
        ->selectRaw('FORMAT(ROUND(CAST(dev6 AS FLOAT),1),1) as dev6n')
        ->selectRaw('FORMAT(ROUND(CAST(dev7 AS FLOAT),1),1) as dev7n')
        ->selectRaw('FORMAT(ROUND(CAST(dev8 AS FLOAT),1),1) as dev8n')
        ->selectRaw('FORMAT(ROUND(CAST(dev9 AS FLOAT),1),1) as dev9n')
        ->selectRaw('FORMAT(ROUND(CAST(dev10 AS FLOAT),1),1) as dev10n')
        ->selectRaw('FORMAT(ROUND(CAST(dev11 AS FLOAT),1),1) as dev11n')
        ->selectRaw('FORMAT(ROUND(CAST(dev12 AS FLOAT),1),1) as dev12n')
        ->selectRaw('DATE_FORMAT(starttime, "%Y/%m/%d") AS startdate')
        ->where($where)
        ->whereNotNull('endtime')->first();
        return $result;
    }

    public function getStrong($array, $value)
    {
        $list = [];
        for ($i = 1;$i <= 12;$i++) {
            $list[$i] = $array["dev".$i];
        }
        arsort($list);
        // 上位2つの値を取得
        $top_two = array_slice($list, 0, 2, true);
        $PFS3 = config('const.PFS3.PFS3');
        $title = [];
        $value = get_object_vars($value);
        for ($i = 1;$i <= 12;$i++) {
            $title[$i] = $value['element'.$i];
        }
        $return = [];
        $i = 0;
        foreach ($top_two as $key => $val) {
            $return[$i][ 'title' ] = $title[$key];
            $return[$i][ 'note' ] = $PFS3[$key][4];
            $i++;
        }
        return $return;
    }

    public function getRiskPoint($array)
    {
        // 対人共感リスク
        $return = [];
        $return[1]['point'] = number_format(round(100 - $array[ 'dev7' ], 1), 1);
        $return[2]['point'] = number_format(round(100 - $array[ 'dev8' ], 1), 1);
        $return[3]['point'] = number_format($array[ 'dev2' ], 1);
        $return[4]['point'] = number_format(round(100 - $array[ 'dev4' ], 1), 1);
        $return[5]['point'] = number_format($array[ 'dev6' ], 1);
        $return[6]['point'] = number_format($array[ 'dev3' ], 1);
        $sougo = $this->getSougoPoint($array);
        $return[7]['point'] = $sougo;
        $return['tate'] = preg_replace("/\./", "", $sougo);
        // パワハラリスクの全体傾向
        $return["pawahararisk"] = $this->getPawaharaRisk($sougo);
        // バーの長さ
        $return[1]['width'] = $this->getBarWidth($return[1][ 'point' ]);
        $return[2]['width'] = $this->getBarWidth($return[2][ 'point' ]);
        $return[3]['width'] = $this->getBarWidth($return[3][ 'point' ]);
        $return[4]['width'] = $this->getBarWidth($return[4][ 'point' ]);
        $return[5]['width'] = $this->getBarWidth($return[5][ 'point' ]);
        $return[6]['width'] = $this->getBarWidth($return[6][ 'point' ]);
        $return[7]['width'] = $this->getBarWidth($return[7][ 'point' ]);

        // 領域
        $return[1]['text'] = $this->getJpPoint($return[1][ 'point' ]);
        $return[2]['text'] = $this->getJpPoint($return[2][ 'point' ]);
        $return[3]['text'] = $this->getJpPoint($return[3][ 'point' ]);
        $return[4]['text'] = $this->getJpPoint($return[4][ 'point' ]);
        $return[5]['text'] = $this->getJpPoint($return[5][ 'point' ]);
        $return[6]['text'] = $this->getJpPoint($return[6][ 'point' ]);
        $return[7]['text'] = $this->getJpPoint($return[7][ 'point' ]);

        // 一番留意すべき項目の傾向値配列の作成
        $return['pattern'] = $this->getMostPattern($array);
        return $return;
    }

    public function getMostPattern($array)
    {
        $devArray1 = [
            [
                "id" => 1
                ,"val" => 100 - $array[ 'dev7' ]
                ,"name" => "対人共感リスク"
            ],
            [
                "id" => 2
                ,"val" => 100 - $array[ 'dev8' ]
                ,"name" => "状況察知リスク"
            ],
            [
                "id" => 3
                ,"val" => $array[ 'dev2' ]
                ,"name" => "業務分担リスク"
            ],
            [
                "id" => 4
                ,"val" => 100 - $array[ 'dev4' ]
                ,"name" => "感情コントロールリスク"
            ],
            [
                "id" => 5
                ,"val" => $array[ 'dev6' ]
                ,"name" => "ポジティブ思考リスク"
            ],
            [
                "id" => 6
                ,"val" => $array[ 'dev3' ]
                ,"name" => "自己肯定リスク"
            ],
        ];
        $key_id = [];
        $key_val = [];
        foreach ($devArray1 as $key => $value) {
            $key_id[$key] = $value['id'];
            $key_val[$key] = $value['val'];
        }
        array_multisort($key_val, SORT_DESC, $key_id, SORT_ASC, $devArray1);
        $ryui  = $devArray1[0];
        $ryui2 = $devArray1[1];

        $rid = $ryui[ 'id' ];
        $rval = $ryui[ 'val' ];
        $rname = $ryui[ 'name' ];
        $rid2 = $ryui2[ 'id' ];
        $rval2 = $ryui2[ 'val' ];
        $rname2 = $ryui2[ 'name' ];

        $aryRyui = config('const.PFS3.aryRyui');
        $aryJyog = config('const.PFS3.aryJyog');
        $aryLang = config('const.PFS3.aryLang');

        $val4 = "";
        $val5 = "";
        $val6 = "";
        $val7 = "";

        if ($rval >= 55) {
            $val4 = $aryRyui[ $rid ][3];
            $val5 = $aryJyog[ $rid ][3];
            $val6 = $aryLang[ $rid ][3];

        } elseif ($rval > 45 && $rval < 55) {
            $val4 = $aryRyui[ $rid ][2];
            $val5 = $aryJyog[ $rid ][2];
            $val6 = $aryLang[ $rid ][2];

        } else {
            $val4 = $aryRyui[ $rid ][1];
            $val5 = $aryJyog[ $rid ][1];
            $val6 = $aryLang[ $rid ][1];
        }

        if ($rval2 >= 55) {
            $val7 = $aryLang[ $rid2 ][3];
        } elseif ($rval2 > 45 && $rval2 < 55) {
            $val7 = $aryLang[ $rid2 ][2];
        } else {
            $val7 = $aryLang[ $rid2 ][1];
        }
        $return = [];
        $return[4] = $val4;
        $return[5] = $val5;
        $return[6] = $val6;
        $return[7] = $val7;
        $return['remember'][1] = $rname;
        $return['remember'][2] = $rname2;
        return $return;
    }
    public function getPawaharaRisk($sougo)
    {
        $key = 0;
        if ($sougo >= 0.5 && $sougo < 4) {
            $key = 1;
        }
        if ($sougo >= 4 && $sougo < 8) {
            $key = 2;
        }
        if ($sougo >= 8) {
            $key = 3;
        }
        $pawahararisk = config('const.PFS3.pawahararisk');
        return $pawahararisk[$key];
    }
    public function getJpPoint($int)
    {
        if ($int < 45) {
            $str = "-";
        } elseif ($int >= 45 && $int < 52) {
            $str = "注意";
        } elseif ($int >= 52 && $int < 60) {
            $str = "要注意";
        } elseif ($int >= 60) {
            $str = "危険";
        }
        return $str;
    }
    public function getBarWidth($point)
    {
        $width = 0;
        if ($point == 20) {
            $width = 0.1;
        }
        if ($point > 20 && $point <= 35) {
            $width = ($point - 20) * 5.7;
        }
        if ($point > 35 && $point <= 44) {
            $width = (($point - 30) * 6) + 60;
        }
        if ($point > 44 && $point <= 49) {
            $width = (($point - 40) * 8) + 114;
        }
        if ($point > 49 && $point <= 52) {
            $width = (($point - 40) * 7.5) + 112;
        }
        if ($point > 52 && $point <= 59) {
            $width = (($point - 50) * 6.5) + 188;
        }
        if ($point > 59 && $point <= 64) {
            $width = (($point - 50) * 7) + 183;
        }
        if ($point > 64 && $point <= 71) {
            $width = (($point - 50) * 6.5) + 186;
        }
        if ($point > 71 && $point <= 80) {
            $width = (($point - 50) * 6.5) + 186;
        }
        return $width;
    }
    public static function getSougoPoint($array)
    {
        $pt = 0.5;
        $p1 = $array[ 'dev6' ] - $array[ 'dev7' ];
        $p2 = $array[ 'dev3' ] - $array[ 'dev4' ];
        $p3 = $array[ 'dev8' ];
        $p4 = $array[ 'dev2' ];
        $p5 = $array[ 'dev11' ] - $array[ 'dev7' ];

        if ($p1 >= 5 && $p1 < 10) {
            $pt += 3;
        } elseif ($p1 >= 10) {
            $pt += 4;
        }
        if ($p2 >= 5 && $p2 < 10) {
            $pt += 2;
        } elseif ($p2 >= 10) {
            $pt += 3;
        }
        if ($p3 < 45) {
            $pt += 1;
        }
        if ($p4 >= 52) {
            $pt += 0.5;
        }
        if ($p5 >= 5) {
            $pt += 1;
        }

        if ($pt == 10.0) {
            return '10.0';
        } else {
            return number_format($pt, 1);
        }
    }
    public static function getPawaharaRiskRowCalc($list)
    {
        $calc = config('const.consts.PAWAHARA_RISK_CALC');
        $result = [];
        foreach ($calc as $key => $value) {
            if ($value[0]) {
                $result[$key] = $value[0] - $list[$value[1]];
            } else {
                $result[$key] = $list[$value[1]];
            }
        }
        return $result;
    }
}
