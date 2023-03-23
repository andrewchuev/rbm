<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Orchid\Screen\AsSource;

class Place extends Model
{
    use HasFactory, AsSource;

    protected $fillable = [
        'name',
        'area_id',
    ];

    public function area()
    {
        return $this->hasOne('\App\Models\Area', 'id', 'area_id');
    }
}
