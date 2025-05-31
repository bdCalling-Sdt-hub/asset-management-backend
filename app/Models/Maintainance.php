<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Maintainance extends Model
{
    protected $guarded=['id'];

    public function asset(){
        return $this->belongsTo(Asset::class);
    }

    public function technician(){
        return $this->belongsTo(User::class,'technician_id');
    }

    public function user(){
        return $this->belongsTo(User::class,'user_id');
    }
    // public function getCheckedAttribute($checked)
    // {
    //     return $checked ? json_decode($checked, true) : [];
    // }

    public function maintainanceChecks()
    {
        return $this->hasMany(MaintainanceCheck::class);
    }

}
