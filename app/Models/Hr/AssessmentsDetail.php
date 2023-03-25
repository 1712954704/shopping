<?php

namespace App\Models\Hr;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AssessmentsDetail extends Model
{
    use HasFactory;

    use HasFactory;
    use HasFactory;
    use SoftDeletes;
    protected $connection = 'mysql_hr';
    protected $guarded = [];

    protected $casts = [
        'updated_at'  => 'date:Y-m-d',
        'created_at' => 'datetime:Y-m-d',
    ];
    protected $fillable = ['*'];
}
