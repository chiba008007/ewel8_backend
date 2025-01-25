<?php

namespace App\Http\Controllers\PDF;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use ConsoleTVs\Charts\Classes\Chartjs\Chart;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;


class PfsController extends Controller
{
    //
    function index(Request $request,$id)
    {



        /*
// データをセット（例）
$data = array(5, 7, 6, 9, 8);

// ラベル（軸のラベルを設定）
$labels = array('A', 'B', 'C', 'D', 'E');

// 新しいグラフを作成
$graph = new RadarGraph(400, 400); // グラフのサイズを指定

// タイトル設定
$graph->title->Set('Radar Chart Example');

// データセットの作成
$plot = new RadarPlot($data);

// 軸ラベルを設定
$plot->SetLabels($labels);

// レーダーチャートにデータセットを追加
$graph->Add($plot);

// 画像として保存
$graph->Stroke('radar_chart.png');
*/

//        return view('PDF/PFS');
/*

        // 画像生成のためのHTMLの準備（Chart.jsのコードやCanvas要素を使用）
        $chartJsCode = "
            <canvas id='radarChart' width='400' height='400'></canvas>
            <script>
                var ctx = document.getElementById('radarChart').getContext('2d');
                var radarChart = new Chart(ctx, {
                    type: 'radar',
                    data: {
                        labels: ['Label1', 'Label2', 'Label3', 'Label4', 'Label5'],
                        datasets: [{
                            label: 'Dataset 1',
                            data: [10, 20, 30, 40, 50],
                            borderColor: 'rgba(0, 123, 255, 1)',
                            backgroundColor: 'rgba(0, 123, 255, 0.2)'
                        }]
                    }
                });
            </script>";

        // HTMLから画像に変換するロジック
        $img = Image::canvas(500, 500); // 必要なサイズでキャンバス作成

        // 他の生成処理、保存処理

        $img->save(storage_path('app/public/radar2_chart.png'));

*/

 //       return response("success", 200);
    }


    public function saveRadarImage(Request $request)
    {
        // 画像データを取得
        $imageData = $request->input('image');

        // データURLから画像部分を取り出す
        list($type, $data) = explode(';', $imageData);
        list(, $data) = explode(',', $data);

        // 画像をデコード
        $image = base64_decode($data);

        // 保存先のパス
        $path = 'images/radar_chart_' . time() . '.png';

        // 画像をストレージに保存
        Storage::put($path, $image);

        return response()->json(['message' => 'Image saved successfully', 'path' => $path]);
    }
}
