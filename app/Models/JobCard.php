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

}
