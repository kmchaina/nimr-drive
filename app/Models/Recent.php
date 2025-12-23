<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Recent extends Model
{
    protected $fillable = ['user_id', 'path', 'is_directory', 'accessed_at'];

    protected $casts = [
        'accessed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
