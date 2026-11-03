<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CourseRegistration extends Model
{
    use HasFactory;

    protected $fillable = ['student_id', 'course_id', 'semester_id', 'status', 'dropped_at'];

    protected function casts(): array
    {
        return ['dropped_at' => 'datetime'];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class);
    }

    public function result(): HasOne
    {
        return $this->hasOne(Result::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(CourseRegistrationAuditLog::class)->orderBy('created_at');
    }
}
