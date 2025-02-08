<?php

namespace App\Libraries;

use App\Models\exampfs;
use Ramsey\Uuid\Type\Decimal;

class Pfs
{

    public function getPfs($exam_id)
    {
        $where = [
                ["exam_id","=",$exam_id]
        ];
        $result = exampfs::
        select(["exampfses.*"])
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

    public function getStrong($array,$value){
        $list = [];
        for($i=1;$i<=12;$i++){
            $list[$i] = $array["dev".$i];
        }
        arsort($list);
        // 上位2つの値を取得
        $top_two = array_slice($list, 0, 2, true);
        $PFS3 = config('const.consts.PFS3');
        $title = [];
        $value = get_object_vars($value);
        for($i=1;$i<=12;$i++){
            $title[$i] = $value['element'.$i];
        }
        $return = [];
        $i=0;
        foreach($top_two as $key=>$val){
            $return[$i][ 'title' ] = $title[$key];
            $return[$i][ 'note' ] = $PFS3[$key][4];
            $i++;
        }
        return $return;
    }
}
