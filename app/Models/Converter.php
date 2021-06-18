<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class Converter extends Model
{
    use HasFactory;

    protected $fillable = [
        'file_name', 'after_name', 'user_id'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getCreatedAtAttribute($time)
    {
        return Carbon::parse($time)->format("Y-m-d H:i");
    }
}
