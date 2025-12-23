<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Star extends Model
{
    protected $fillable = ['user_id', 'path', 'is_directory'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
