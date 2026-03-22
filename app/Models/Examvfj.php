<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Examvfj extends Model
{
    use HasFactory;

    protected $table = 'examvfj';

    protected $fillable = [
        'testparts_id',
        'exam_id',
        'starttime',
        'endtime',
        'status',
        'avg',
        'std',
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->fillable = array_merge(
            $this->fillable,
            array_map(fn ($i) => "q{$i}", range(1, 66)),
            array_map(fn ($i) => "w{$i}", range(1, 12)),
            array_map(fn ($i) => "dev{$i}", range(1, 12)),
        );
    }

    protected $casts = [
        'starttime' => 'datetime',
        'endtime'   => 'datetime',
        'avg'       => 'decimal:4',
        'std'       => 'decimal:4',
    ];
}
