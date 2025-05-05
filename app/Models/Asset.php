<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Asset extends Model
{
    protected $guarded = ['id'];
    public function organization()
    {
        return $this->belongsTo(User::class, 'organization_id');
    }
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    public function tickets()
    {
        return $this->hasMany(Ticket::class, 'asset_id');
    }
    public function reports()
    {
        return $this->hasMany(Report::class);
    }

}
