<?php

namespace App\Models;

use App\Enums\SemesterType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Semester extends Model
{
    use HasFactory;

    protected $fillable = [
        'academic_session_id',
        'type',
        'starts_on',
        'ends_on',
        'registration_opens_at',
        'registration_closes_at',
        'max_credit_units',
        'is_current',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'type' => SemesterType::class,
            'starts_on' => 'date',
            'ends_on' => 'date',
            'registration_opens_at' => 'datetime',
            'registration_closes_at' => 'datetime',
            'max_credit_units' => 'integer',
            'is_current' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function scopeCurrent(Builder $query): Builder
    {
        return $query->where('is_current', true)->where('is_active', true);
    }

    public function registrationIsOpen(): bool
    {
        return $this->is_active
            && $this->is_current
            && $this->registration_opens_at?->isPast()
            && $this->registration_closes_at?->isFuture();
    }

    public function academicSession(): BelongsTo
    {
        return $this->belongsTo(AcademicSession::class);
    }

    public function courseAssignments(): HasMany
    {
        return $this->hasMany(CourseAssignment::class);
    }

    public function courseRegistrations(): HasMany
    {
        return $this->hasMany(CourseRegistration::class);
    }
}
