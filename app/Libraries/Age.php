<?php

namespace App\Libraries;


class Age
{

    public function getAge($currentDate,$birthDate)
    {
        $birthDate = preg_replace("/\//","-",$birthDate);
        $birthDateObj = new \DateTime($birthDate);
        $currentDateObj = new \DateTime($currentDate);
        // 年齢を計算
        $age = $birthDateObj->diff($currentDateObj)->y;
        return $age;
    }
}
