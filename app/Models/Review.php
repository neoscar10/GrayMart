<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    protected $casts = [
      'reported' => 'boolean',
      'visible'  => 'boolean',
    ];
    protected $fillable = [
        'user_id',
        'rateable_type',
        'rateable_id',
        'rating',
        'comment',
        'reported',
        'status',
        'visible',
        'rejection_reason',
    ];

    public function reports()
    {
        return $this->hasMany(ReviewReport::class);
    }

   

    public function user()
    {
      return $this->belongsTo(User::class);
    }

    public function rateable()
    {
      return $this->morphTo();
    }
}