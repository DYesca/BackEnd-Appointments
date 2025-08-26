<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProvidersSubcategories extends Model
{
    protected $table = 'providers_subcategories';

    protected $fillable = [
        'provider_id',
        'subcategory_id',
        'father_category',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class, 'father_category');
    }

    public function subcategory()
    {
        return $this->belongsTo(Subcategory::class, 'subcategory_id');
    }
}
