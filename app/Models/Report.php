<?php
namespace App\Models;

use App\Notifications\InspectionSheetNotification;
use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    protected $guarded = ['id'];

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }
    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }
    public function inspectionSheet()
    {
        return $this->belongsTo(InspectionSheet::class);
    }
    public function jobCard()
    {
        return $this->belongsTo(JobCard::class);
    }
}

