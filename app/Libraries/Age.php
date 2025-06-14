<?php

namespace App\Libraries;

class Age
{
    public function getAge($currentDate, $birthDate)
    {

        $birthDate = preg_replace("/\//", "-", $birthDate);
        $birthDate = trim($birthDate); // 前後の空白や改行除去
        $birthDate = preg_replace('/[^\x20-\x7E]/', '', $birthDate); // 制御文字除去
        $birthDateObj = new \DateTime($birthDate);
        $currentDateObj = new \DateTime($currentDate);
        // 年齢を計算
        $age = $birthDateObj->diff($currentDateObj)->y;
        return $age;
    }
}
