<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class ReviewReport extends Model
{
    protected $fillable = [
        'review_id',
        'reporter_id',
        'message',
    ];

    public function reporter()
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }
}
