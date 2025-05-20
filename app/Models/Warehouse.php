<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Warehouse extends Model
{
    protected $fillable = ['name'];

    public $timestamps = false;

    public function stocks()
    {
        return $this->hasMany(Stock::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}
