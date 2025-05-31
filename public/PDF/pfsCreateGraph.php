<?php

require_once(__DIR__ .'../../vendor/jpgraph/src/jpgraph.php');
require_once(__DIR__ .'../../vendor/jpgraph/src/jpgraph_radar.php');

function createRadarChart($filePath, $result)
{

    $titles = [
        "",
        "",
        "",
        "",
        "",
        "",
        "",
        "",
        "",
        "",
        "",
        "",
        "",
        "",
    ];

    $data = [
        $result->dev1,
        $result->dev12,
        $result->dev11,
        $result->dev10,
        $result->dev9,
        $result->dev8,
        $result->dev7,
        $result->dev6,
        $result->dev5,
        $result->dev4,
        $result->dev3,
        $result->dev2,
    ];

    $graph = new RadarGraph(800, 460);
    $graph->SetScale('lin', 20, 80);
    $graph->SetMarginColor('white');
    $graph->SetFrame(false);
    $graph->title->SetFont(FF_GOTHIC, FS_NORMAL, 9);

    $graph->SetTitles($titles);
    $graph->SetCenter(0.5, 0.53);
    $graph->HideTickMarks();
    $graph->axis->HideLabels();
    $graph->axis->SetColor('darkgray');
    $graph->grid->SetColor('darkgray');
    $graph->grid->Show();

    $graph->axis->title->SetFont(FF_GOTHIC, FS_NORMAL, 9);
    $graph->axis->title->SetMargin(5);
    $graph->SetGridDepth(DEPTH_BACK);
    $graph->SetSize(0.7);

    $plot = new RadarPlot($data);
    $plot->SetColor('blue@0.2');
    $plot->SetLineWeight(3);
    $plot->mark->SetType(MARK_IMG, public_path().'/img/custom_blue_ball.png');
    $plot->mark->SetSize(40);

    $graph->Add($plot);

    $graph->Stroke($filePath);

    $graph->img->SetImgFormat('png');
    $graph->SetMarginColor('white');
    $graph->SetColor('white');
    $graph->Stroke($filePath);

    // 透過処理（PHP GD後処理）
    $im = imagecreatefrompng($filePath);
    $white = imagecolorallocate($im, 255, 255, 255);
    imagecolortransparent($im, $white);
    imagepng($im, $filePath);
    imagedestroy($im);
}
