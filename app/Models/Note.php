<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @OA\Schema(
 *     schema="Note",
 *     required={"full_name", "company", "phone", "email", "birth_date"},
 *     @OA\Property(property="id", type="integer", format="int64"),
 *     @OA\Property(property="full_name", type="string", maxLength=255),
 *     @OA\Property(property="company", type="string", maxLength=255),
 *     @OA\Property(property="phone", type="string", maxLength=255),
 *     @OA\Property(property="email", type="string", format="email", maxLength=255),
 *     @OA\Property(property="birth_date", type="string", format="date"),
 *     @OA\Property(property="photo", type="string", nullable=true),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time"),
 *     @OA\Property(property="deleted_at", type="string", format="date-time", nullable=true)
 * )
 */

class Note extends Model
{
    use SoftDeletes;
    use HasFactory;

    protected $guarded = false;
    protected $table = 'notes';
    protected $fillable = [
        'full_name',
        'company',
        'phone',
        'email',
        'birth_date',
        'photo'
    ];
}
