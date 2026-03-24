<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdminTodo extends Model
{
    use HasFactory;

    protected $table = 'admin_todos';

    protected $fillable = [
        'user_id',
        'text',
        'completed',
    ];

    protected $casts = [
        'completed' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
