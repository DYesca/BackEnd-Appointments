<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $user_id
 * @property int|null $subcategory_id
 * @property string $ced
 * @property string $contact_email
 * @property string $phone_number
 * @property string $location
 * @property string $long
 * @property string $lat
 * @property int $experience_years
 * @property bool $schedule_type
 * @property int $likes
 * @property string|null $img
 * @property int $services
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class Provider extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'subcategory_id',
        'ced',
        'contact_email',
        'phone_number',
        'location',
        'long',
        'lat',
        'experience_years',
        'schedule_type',
        'likes',
        'img',
        'services',
    ];

    /**
     * Relación con el modelo User (muchos a uno).
     * Un proveedor pertenece a un usuario.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relación con el modelo Subcategory (muchos a uno).
     * Un proveedor pertenece a una subcategoría principal.
     */
    public function subcategory()
    {
        return $this->belongsTo(Subcategory::class);
    }

    public function getScheduleTypeLabelAttribute()
    {
        return $this->schedule_type ? 'Estricto' : 'Flexible';
    }

    /**
     * Relación para obtener todas las subcategorías del proveedor
     * (tanto la principal como las adicionales de la tabla pivot)
     */
    public function allSubcategories()
    {
        return $this->belongsToMany(Subcategory::class, 'providers_subcategories', 'provider_id', 'subcategory_id');
    }

    // App\Models\Provider.php

    public function subcategoryRelation()
    {
        return $this->hasOne(ProvidersSubcategories::class, 'provider_id');
    }
}
