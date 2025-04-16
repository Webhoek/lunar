<?php

namespace Lunar\Models;

use App\Channel\Services\Shopify;
use App\Channel\Services\Woocommerce;
use App\Models\Trait\HasTenant;
use App\Models\Integration;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Lunar\Base\BaseModel;
use Lunar\Base\Traits\HasDefaultRecord;
use Lunar\Base\Traits\HasMacros;
use Lunar\Base\Traits\LogsActivity;
use Lunar\Database\Factories\ChannelFactory;

/**
 * @property int $id
 * @property string $name
 * @property string $handle
 * @property bool $default
 * @property ?string $url
 * @property ?int $integration_id
 * @property array $settings
 * @property ?\Illuminate\Support\Carbon $created_at
 * @property ?\Illuminate\Support\Carbon $updated_at
 * @property ?\Illuminate\Support\Carbon $deleted_at
 */
class Channel extends BaseModel implements Contracts\Channel
{
    use HasTenant;
    use HasDefaultRecord;
    use HasFactory;
    use HasMacros;
    use LogsActivity;
    use SoftDeletes;

    public $casts = [
        'enabled' => 'boolean',
        'settings' => 'array',
        'sync_default_settings' => 'array',
        'default' => 'boolean',
        'sync_settings' => 'array',
    ];

    /**
     * Return a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return ChannelFactory::new();
    }

    /**
     * Define which attributes should be
     * protected from mass assignment.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * Mutator for formatting the handle to a slug.
     */
    public function setHandleAttribute(?string $val): void
    {
        $this->attributes['handle'] = Str::slug($val);
    }

    /**
     * Get the integration associated with the channel.
     */
    public function integration(): BelongsTo
    {
        return $this->belongsTo(Integration::class);
    }

    /**
     * Get the handler instance for this channel's integration.
     */
    public function getHandler()
    {
        return $this->integration?->getHandler($this);
    }

    public function channelable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Return the discounts relationship
     */
    public function discounts(): MorphToMany
    {
        $prefix = config('lunar.database.table_prefix');

        return $this->morphedByMany(
            Discount::modelClass(),
            'channelable',
            "{$prefix}channelables"
        );
    }

    /**
     * Return the products relationship
     */
    public function products(): MorphToMany
    {
        $prefix = config('lunar.database.table_prefix');

        return $this->morphedByMany(
            Product::modelClass(),
            'channelable',
            "{$prefix}channelables"
        );
    }

    /**
     * Return the products relationship
     */
    public function collections(): MorphToMany
    {
        $prefix = config('lunar.database.table_prefix');

        return $this->morphedByMany(
            Collection::modelClass(),
            'channelable',
            "{$prefix}channelables"
        );
    }

    public function publisher()
    {
        return $this->getHandler();
        // if ($this->platform == 'woocommerce') {
        //     return new Woocommerce(channel: channel: $this);
        // }

        // if ($this->platform == 'shopify') {
        //     return new Shopify($this);
        // }

        // if ($this->platform == 'prestashop') {
        //     return new Prestashop($this);
        // }

        //throw new \Exception('Publisher not found.');
    }

    /**
     * Get the columns that should be used for distinct operations.
     *
     * @return array
     */
    public function getDistinctColumns(): array
    {
        return array_diff(
            $this->getFillable(),
            ['sync_settings', 'settings', 'sync_default_settings']
        );
    }

    /**
     * Create a new Eloquent query builder for the model.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder|static
     */
    public function newEloquentBuilder($query)
    {
        return new class($query) extends \Illuminate\Database\Eloquent\Builder {
            public function distinct()
            {
                $model = $this->getModel();
                if (method_exists($model, 'getDistinctColumns')) {
                    return $this->select($model->getDistinctColumns());
                }
                return parent::distinct();
            }
        };
    }

}
