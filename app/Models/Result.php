<?php

namespace App\Models;

use App\Enums\ResultStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Result extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_registration_id',
        'ca_score',
        'exam_score',
        'total_score',
        'grade',
        'grade_point',
        'status',
        'latest_feedback',
        'submitted_at',
        'approved_at',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'ca_score' => 'integer',
            'exam_score' => 'integer',
            'total_score' => 'integer',
            'grade_point' => 'float',
            'status' => ResultStatus::class,
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
            'published_at' => 'datetime',
        ];
    }

    public function courseRegistration(): BelongsTo
    {
        return $this->belongsTo(CourseRegistration::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(ResultAuditLog::class)->orderBy('created_at');
    }
}
