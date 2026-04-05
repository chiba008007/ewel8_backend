<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Exambea extends Model
{
    use HasFactory;

    protected $table = 'exam_bea';

    protected $fillable = [
        'testparts_id',
        'exam_id',
        'starttime',
        'endtime',
        'limittime',
        'status',
        'sougo',
        'yomitori',
        'rikai',
        'sentaku',
        'kirikae',
        'jyoho',
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->fillable = array_merge(
            $this->fillable,
        );
    }

    protected $casts = [
        'starttime' => 'datetime',
        'endtime'   => 'datetime',
        'limittime'   => 'datetime',
    ];
}
