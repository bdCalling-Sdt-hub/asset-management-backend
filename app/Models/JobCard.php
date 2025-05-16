<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobCard extends Model
{
    use HasFactory;
    protected $guarded = ['id'];
    public function supportAgent()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function inspectionSheet()
    {
        return $this->belongsTo(InspectionSheet::class, 'inspection_sheet_id');
    }
    public function technician()
    {
        return $this->belongsTo(User::class, 'technician_id');
    }
    public function ticket()
    {
        return $this->belongsTo(Ticket::class, 'ticket_id');
    }
    public function assigned()
    {
        return $this->belongsTo(User::class, 'support_agent_id');
    }

    // app/Models/JobCard.php
    public function getImageAttribute($images)
    {
        if (! is_array($images)) {
            return [];
        }

        return array_map(function ($image) {
            return asset('uploads/job_card_images/' . $image);
        }, $images);
    }
    public function getVideoAttribute($videos)
    {
        if (! is_array($videos)) {
            return [];
        }

        return array_map(function ($video) {
            return asset('uploads/job_card_videos/' . $video);
        }, $videos);
    }

}
