<?php

namespace App\Services\Export;

use Carbon\Carbon;
use App\Libraries\Age;

class UserExportService
{

    public function getStatus($value, $status)
    {
        if ($value->ended_at) return $status[2];
        if ($value->started_at) return $status[1];
        return $status[0];
    }

    public function decryptPassword($password, $passwd)
    {

        return openssl_decrypt(
            $password,
            'aes-256-cbc',
            $passwd['key'],
            0,
            $passwd['iv']
        );
    }

    public function formatDate($date)
    {
        return $date
            ? (new Carbon($date))->format('Y/m/d')
            : '';
    }

    public function getAgeValue($value, $passwd)
    {
        if (!$value->started_at) return '';

        $pwd = $this->decryptPassword($value->password, $passwd);

        return (new Age())->getAge($value->started_at, $pwd);
    }
}
