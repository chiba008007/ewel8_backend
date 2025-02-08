<?php

// 画像の幅と高さを設定
$width = 150;
$height = 20;
$filename = $dir."bar_image1.png";
setImage($width,$height,$filename);
$width = 150;
$height = 20;
$filename = $dir."bar_image2.png";
setImage($width,$height,$filename);
$width = 150;
$height = 20;
$filename = $dir."bar_image3.png";
setImage($width,$height,$filename);
$width = 150;
$height = 20;
$filename = $dir."bar_image4.png";
setImage($width,$height,$filename);
$width = 150;
$height = 20;
$filename = $dir."bar_image5.png";
setImage($width,$height,$filename);
$width = 150;
$height = 20;
$filename = $dir."bar_image6.png";
setImage($width,$height,$filename);
$width = 20;
$height = 120;
$filename = $dir."bar_image7.png";
setImage($width,$height,$filename,"tate");


function setImage($width,$height,$filename,$type="yoko"){
    // 画像を作成
    $image = imagecreatetruecolor($width, $height);
    // 色を設定
    $background_color = imagecolorallocate($image, 255, 255, 255); // 白
    if($type == "tate"){
        $bar_color = imagecolorallocate($image, 255, 0, 0); // 赤
    }else{
        $bar_color = imagecolorallocate($image, 0, 128, 0); // 緑
    }
    // 背景色を塗りつぶす
    imagefill($image, 0, 0, $background_color);
    // 棒を描画（x, y, 幅, 高さ）
    $bar_width = $width;
    $bar_height = $height;
    $bar_x = 0;
    $bar_y = ($height - $bar_height) / 2; // 中央に配置
    // 棒を描画
    imagefilledrectangle($image, $bar_x, $bar_y, $bar_x + $bar_width, $bar_y + $bar_height, $bar_color);
    // 画像をファイルとして保存
    $image_path = $filename;
    imagepng($image, $image_path);
    // メモリ解放
    imagedestroy($image);
}
