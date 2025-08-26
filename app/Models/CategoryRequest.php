<?php

namespace App\Models;

use App\Enums\CategoryRequestStatus;
use App\Enums\CategoryRequestType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CategoryRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'provider_id',
        'type',
        'current_category_id',
        'current_subcategory_id',
        'justification',
        'status',
        'reviewed_by',
        'reviewed_at',
        'admin_comment',
    ];

    protected $casts = [
        'type'        => CategoryRequestType::class,
        'status'      => CategoryRequestStatus::class,
        'reviewed_at' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relaciones
    |--------------------------------------------------------------------------
    */
    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function currentCategory(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'current_category_id');
    }

    public function currentSubcategory(): BelongsTo
    {
        return $this->belongsTo(Subcategory::class, 'current_subcategory_id');
    }

    public function scopePending($query)
    {
        return $query->where('status', CategoryRequestStatus::PENDING);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', CategoryRequestStatus::APPROVED);
    }

    public function scopeRejected($query)
    {
        return $query->where('status', CategoryRequestStatus::REJECTED);
    }

    public function markApproved(User $admin, ?string $comment = null): void
    {
        $this->forceFill([
            'status'        => CategoryRequestStatus::APPROVED,
            'reviewed_by'   => $admin->id,
            'reviewed_at'   => now(),
            'admin_comment' => $comment,
        ])->save();
    }

    public function markRejected(User $admin, ?string $comment = null): void
    {
        $this->forceFill([
            'status'        => CategoryRequestStatus::REJECTED,
            'reviewed_by'   => $admin->id,
            'reviewed_at'   => now(),
            'admin_comment' => $comment,
        ])->save();
    }
}
