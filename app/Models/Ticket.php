<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    use HasFactory;
    protected $guarded = ['id'];

    // protected $cast = [
    //     'iamge'=> 'array'
    // ];

    public function getImageAttribute($image)
    {
        $images = json_decode($image, true) ?? [];
        return array_map(fn($img) => asset('uploads/ticket_images/' . $img), $images);
    }

    // Accessor for Videos
    public function getVideoAttribute($video)
    {
        $videos = json_decode($video, true) ?? [];
        return array_map(fn($vid) => asset('uploads/ticket_videos/' . $vid), $videos);
    }
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function asset()
    {
        return $this->belongsTo(Asset::class, 'asset_id');
    }
    public function technician()
    {
        return $this->belongsTo(User::class, 'technician_id');
    }

}
