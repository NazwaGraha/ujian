<?php

namespace App\Models;

use Database\Factories\StudentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Student extends Model
{
    /** @use HasFactory<StudentFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'nis',
        'full_name',
        'class_name',
    ];

    /**
     * @return HasMany<StudentSession, $this>
     */
    public function sessions(): HasMany
    {
        return $this->hasMany(StudentSession::class);
    }
}
