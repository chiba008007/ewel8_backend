<?php

namespace App\Libraries;


class LineBreak
{

    public function insert_line_breaks($string, $line_length) {

        $result = ''; // 結果を格納する変数

        // 文字列の長さを取得
        $length = mb_strlen($string, 'UTF-8');

        // 指定の文字数ごとに改行を挿入
        for ($i = 0; $i < $length; $i += $line_length) {
            // 指定の位置までの文字列を取得
            $result .= mb_substr($string, $i, $line_length, 'UTF-8');

            // 改行コードを追加（最終位置でない場合に追加）
            if ($i + $line_length < $length) {
                $result .= "\n";
            }
        }
        return $result;

    }
}
