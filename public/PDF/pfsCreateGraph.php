<?php
require_once ('./vendor/jpgraph/src/jpgraph.php');
require_once ('./vendor/jpgraph/src/jpgraph_radar.php');

$titles=
[
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

$graph = new RadarGraph (700,600);

// 最大値と最小値を指定する

$graph->SetScale('lin', 20, 80);

$graph->SetMarginColor('white');
$graph->SetFrame(false);
//$graph->title->Set('Radar with marks');
$graph->title->SetFont(FF_GOTHIC,FS_NORMAL,9);

$graph->SetTitles($titles);
$graph->SetCenter(0.48,0.5);
$graph->HideTickMarks();
// $graph->SetColor('lightgreen@0.7');
$graph->axis->SetColor('darkgray');
$graph->grid->SetColor('darkgray');
$graph->grid->Show();

$graph->axis->title->SetFont(FF_GOTHIC,FS_NORMAL,9);
$graph->axis->title->SetMargin(5);
$graph->SetGridDepth(DEPTH_BACK);
$graph->SetSize(0.7);




$plot = new RadarPlot($data);
$plot->SetColor('blue@0.2');
$plot->SetLineWeight(3);
//$plot->SetFillColor('red@0.7');
//$plot->mark->SetType(MARK_IMG_SBALL,"red");
$plot->mark->SetSize(20);

$graph->Add($plot);
$filePath = "./images/PDF/radar_chart.png";
$graph->Stroke($filePath);

