<?php

namespace App\Models;

use App\Http\Controllers\SupportAgent\InspectionSheetController;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable,SoftDeletes;

    protected $dates = ['deleted_at'];
    protected $guarded = ['id'];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            // 'document' => 'array',
        ];
    }
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }


    public function getImageAttribute($image)
    {
        $defaultImage = 'default_user.jpg';
        return asset('uploads/profile_images/' . ($image ?? $defaultImage));
    }
    public function getDocumentAttribute($document)
    {
        $documents = json_decode($document, true);
        if (is_array($documents)) {
            return array_map(function ($doc) {
                return asset('uploads/documents/' . $doc);
            }, $documents);
        }
    }
    public function creator()
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }
    public function inspectionSheets()
    {
        return $this->hasMany(InspectionSheetController::class);
    }


}
