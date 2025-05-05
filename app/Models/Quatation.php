<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Quatation extends Model
{
    protected $guarded = ['id'];

    public function inspectionSheet()
    {
        return $this->belongsTo(InspectionSheet::class, 'sheet_id', 'id');
    }

    public function ticket()
    {
        return $this->belongsTo(Ticket::class, 'ticket_id', 'id');
    }

}
