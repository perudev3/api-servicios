<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceRequest extends Model
{
    protected $fillable = [
        'client_id',
        'category_id',
        'service_id',
        'description',
        'address',
        'city_id',
        'service_date',
        'service_time',
        'budget',
        'status',
        'professional_id',
        'completion_code',
        'completion_code_expires_at',
        'people_names'
    ];

    protected $casts = [
        'people_names' => 'array',
    ];

    public function client()
    {
        return $this->belongsTo(User::class,'client_id');
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function city()
    {
        return $this->belongsTo(City::class);
    }

    public function professional()
    {
        return $this->belongsTo(Professional::class);
    }

}