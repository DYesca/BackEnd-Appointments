<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subcategory extends Model
{
    protected $fillable = [
        'category_id',
        'name',
        'img',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    /**
     * Relación directa con Provider (uno a muchos).
     * Una subcategoría puede tener muchos proveedores principales.
     */
    public function primaryProviders()
    {
        return $this->hasMany(Provider::class, 'subcategory_id');
    }

    public function providers()
    {
        return $this->belongsToMany(Provider::class, 'providers_subcategories')
            ->using(ProvidersSubcategories::class)
            ->withPivot('father_category')
            ->withTimestamps();
    }
}
