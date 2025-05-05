<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InspectionSheet extends Model
{
    use HasFactory;
    protected $guarded = ['id'];
    public function technician()
    {
        return $this->belongsTo(User::class, 'technician_id');
    }

    public function assigned()
    {
        return $this->belongsTo(User::class, 'support_agent_id');
    }

    public function asset()
    {
        return $this->belongsTo(Asset::class, 'asset_id');
    }
    public function ticket()
    {
        return $this->belongsTo(Ticket::class, 'ticket_id');
    }
    public function getImageAttribute($image)
    {
        $images = json_decode($image, true);

        if (is_array($images) && count($images) > 0) {
            return array_map(function ($img) {
                return asset('uploads/sheet_images/' . $img);
            }, $images);
        }
        return [];
    }

    public function getVideoAttribute($video)
    {
        $videos = json_decode($video, true);
        if (is_array($videos) && count($videos) > 0) {
            return array_map(function ($vdo) {
                return asset('uploads/sheet_videos/' . $vdo);
            }, $videos);
        }
        return [];
    }

}
