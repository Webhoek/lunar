<?php

namespace Lunar\Models;

use App\Models\Trait\HasTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Lunar\Base\BaseModel;
use Lunar\Base\Traits\HasAttributes;
use Lunar\Base\Traits\HasMacros;
use Lunar\Database\Factories\ProductTypeFactory;

/**
 * @property int $id
 * @property string $name
 * @property ?\Illuminate\Support\Carbon $created_at
 * @property ?\Illuminate\Support\Carbon $updated_at
 */
class ProductType extends BaseModel implements Contracts\ProductType
{
    use HasTenant;
    use HasAttributes;
    use HasFactory;
    use HasMacros;

    /**
     * Return a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return ProductTypeFactory::new();
    }

    /**
     * Define which attributes should be
     * protected from mass assignment.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'manageable_relations' => 'array'
    ];

    public function mappedAttributes(): MorphToMany
    {
        $prefix = config('lunar.database.table_prefix');

        return $this->morphToMany(
            Attribute::modelClass(),
            'attributable',
            "{$prefix}attributables"
        )->withTimestamps();
    }

    public function productAttributes(): MorphToMany
    {
        return $this->mappedAttributes()->whereAttributeType(
            Product::morphName()
        );
    }

    public function variantAttributes(): MorphToMany
    {
        return $this->mappedAttributes()->whereAttributeType(
            ProductVariant::morphName()
        );
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::modelClass());
    }

    /**
     * Check if a specific relation is manageable for this product type.
     *
     * @param string $relation
     * @return bool
     */
    public function isManageableRelation(string $relation): bool
    {
        $manageable = $this->manageable_relations ?? [];
        
        // If '*' is in the array, all relations are manageable
        if (in_array('*', $manageable)) {
            return true;
        }

        return in_array($relation, $manageable);
    }
}
