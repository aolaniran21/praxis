<?php

namespace App\Models;

use App\Enums\SemesterType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Course extends Model
{
    use HasFactory;

    protected $fillable = [
        'department_id',
        'code',
        'title',
        'credit_units',
        'level',
        'semester_type',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'credit_units' => 'integer',
            'level' => 'integer',
            'semester_type' => SemesterType::class,
            'is_active' => 'boolean',
        ];
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function prerequisites(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'course_prerequisites', 'course_id', 'prerequisite_course_id');
    }

    public function coursesRequiringThis(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'course_prerequisites', 'prerequisite_course_id', 'course_id');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(CourseAssignment::class);
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(CourseRegistration::class);
    }
}
