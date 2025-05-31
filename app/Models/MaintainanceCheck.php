<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MaintainanceCheck extends Model
{
    protected $guarded = ['id'];

    public function maintainance()
    {
        return $this->belongsTo(Maintainance::class);
    }
}
