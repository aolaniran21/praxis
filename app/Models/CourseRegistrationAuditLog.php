<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourseRegistrationAuditLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'course_registration_id',
        'actor_id',
        'action',
        'from_status',
        'to_status',
        'feedback',
        'created_at',
    ];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    public function courseRegistration(): BelongsTo
    {
        return $this->belongsTo(CourseRegistration::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
