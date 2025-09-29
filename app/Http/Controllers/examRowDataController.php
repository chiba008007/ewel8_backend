<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\exampfs;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Libraries\Age;
use App\Models\Element;
use App\Http\Controllers\TestController;
use App\Libraries\Pfs;

class examRowDataController extends Controller
{
    public $age;
    public $columns = [
        '番号',
        '受検者ID',
        '受検者名',
        '受検者名カナ',
        '生年月日',
        '年齢',
        '性別',
        '合否',
        'メモ1',
        'メモ2',
        '受検日',
        '受検開始時間',
        '受検時間'
    ];
    public function __construct(Age $age)
    {
        $this->age = $age;
    }
    //
    public function index(Request $request)
    {
        $pawahara = config('const.consts.PAWAHARA');
        // elements情報取得
        $elements = Element::all()->toArray();
        $code = $request->route('code');
        // テーブルの指定
        $lists = [];
        $results = [];
        switch ($code) {
            case "PFS":
                for ($i = 1;$i <= 36;$i++) {
                    $this->columns[] = "回答".$i;
                }
                $this->columns[] = "ストレス共生レベル";
                $this->columns[] = "ストレス共生スコア";
                $this->columns[] = "適合度レベル";
                $this->columns[] = "適合度スコア";
                foreach ($elements as $value) {
                    if ($value[ 'code' ] <= 12) {
                        $this->columns[] = $value['note'];
                    }
                }
                $this->columns[] = "最大偏差値の要素名";
                $this->columns[] = "総合";
                foreach ($pawahara as $value) {
                    $this->columns[] = $value;
                }
                $lists = Exampfs::with([
                    'testpart' => function ($query) {
                        $query->where('code', 'PFS')
                            ->select('id', 'code', 'threeflag');
                    }
                ])->get();
                foreach ($lists as $key => $list) {
                    for ($i = 1;$i <= 36;$i++) {
                        $q = "q".$i;
                        $results[$key][] = $list->$q;
                    }
                    // ストレス共生レベル
                    // ストレス共生スコア
                    if ($list->testpart->threeflag) {
                        list($lv, $score) = TestController::getStress2($list->dev1, $list->dev2, $list->dev6);
                    } else {
                        list($lv, $score) = TestController::getStress($list->dev1, $list->dev2);
                    }
                    $stress_level = $list->dev1 ? $lv : "";
                    $stress_score = $list->dev1 ? $score : "";
                    $results[$key][] = $stress_level;
                    $results[$key][] = $stress_score;
                    // 適合度レベル
                    // 適合度スコア
                    $results[$key][] = $list->level;
                    $results[$key][] = $list->score;

                    for ($i = 1;$i <= 12;$i++) {
                        $dev = "dev".$i;
                        $results[$key][] = $list->$dev;
                    }
                    // 最大偏差値の要素名
                    $max_yoso = array_filter($elements, function ($item) use ($list) {
                        return $item['code'] == $list->soyo;
                    });
                    $first = reset($max_yoso);
                    $results[$key][] = (isset($first[ 'note' ])) ? $first[ 'note' ] : "";

                    // 総合
                    $sougo = Pfs::getSougoPoint($list);
                    $results[$key][] = $first ? $sougo : "";

                    // PAWAHARA_RISK
                    $pawahara_risk = Pfs::getPawaharaRiskRowCalc($list);
                    for ($i = 1;$i <= 6;$i++) {
                        $results[$key][] = $first ? $pawahara_risk[$i] : "";
                    }

                }
                break;
        }

        $fileName = $code.'_'.date('YmdHis').'.csv';
        $headers = [
            "Content-type"        => "text/csv; charset=UTF-8",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $callback = function () use ($lists, $results) {
            $handle = fopen('php://output', 'w');

            // Excelで文字化け防止（UTF-8 BOM）
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            // ヘッダー行（定数配列を想定）
            fputcsv($handle, $this->columns);
            $passwd = config('const.consts.PASSWORD');
            $genders = config('const.consts.GENDERS');
            $passflag = config('const.consts.passflag');
            $num = 1;
            foreach ($lists as $key => $list) {
                $pwd = openssl_decrypt($list->exam->password, 'aes-256-cbc', $passwd['key'], 0, $passwd['iv']);
                $age = $this->age->getAge($list->exam->started_at, $pwd);
                // 必要なカラムを選んで出力
                fputcsv($handle, array_merge([
                    $num,
                    $list->exam->email ?? '',
                    $list->exam->name ?? '',
                    $list->exam->kana ?? '',
                    $pwd ?? '',
                    $age ?? '',
                    $genders[$list->exam->gender] ?? '',
                    $passflag[$list->exam->passflag] ?? '',
                    $list->exam->memo1 ?? '',
                    $list->exam->memo2 ?? '',
                    $list->start_date ?? '',
                    $list->start_time ?? '',
                    $list->duration ?? '',
                ], $results[$key]));
                $num++;
            }

            fclose($handle);
        };
        return new StreamedResponse($callback, 200, $headers);

    }
}
