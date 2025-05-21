<?php

namespace Lunar\Models;

use App\Models\Fulfilment\SupplierProduct;
use App\Suppliers\Contracts\ImportableSupplier;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Lunar\Base\BaseModel;
use App\Models\ChannelSupplier;
use App\Models\SupplierUser;
use App\Models\User;

class Supplier extends BaseModel implements HasMedia
{
    use InteractsWithMedia;
    use SoftDeletes;
    use HasFactory;

    protected $fillable = [
        'name',
        'uuid',
        'identifier',
        'site_url',
        'api_class',
        'is_name_auto_generated',
        'created_by',
        'settings',
        'import_handler',
        'fulfillment_handler',
    ];

    protected $casts = [
        'settings' => 'json',
    ];

    protected static function boot()
    {
        parent::boot();

        static::created(function ($supplier) {
            dispatch(new \App\Jobs\AddFaviconToModelJob($supplier));
        });
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->using(SupplierUser::class)->withPivot('id')->withTimestamps();
    }

    public function products(): HasMany
    {
        return $this->hasMany(SupplierProduct::class);
    }

    /**
     * Get the channels associated with the supplier.
     */
    public function channels()
    {
        $prefix = config('lunar.database.table_prefix');

        return $this->belongsToMany(
            Channel::class,
            "{$prefix}channel_supplier"
        )
        ->withPivot(['integration_settings', 'enabled'])
        ->using(ChannelSupplier::class)
        ->withTimestamps();
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('favicon')->singleFile();
    }

    public function getDomainWithoutProtocol()
    {
        $parsedUrl = parse_url($this->site_url);
        return $parsedUrl['host'] ?? '';
    }



    public function getApi(?array $settings = null)
    {
        if (!$this->api_class || !class_exists($this->api_class)) {
            throw new \Exception("Invalid or missing API class for supplier {$this->name}");
        }

        return new $this->api_class($settings ?? $this->settings);
    }

    public function getImporter(): ImportableSupplier
    {
        if (!$this->import_handler || !class_exists($this->import_handler)) {
            throw new \Exception("Invalid or missing import handler for supplier {$this->name}");
        }

        $importer = new $this->import_handler($this->settings ?? []);
        
        if (!($importer instanceof ImportableSupplier)) {
            throw new \Exception("Import handler {$this->import_handler} must implement ImportableSupplier interface");
        }

        return $importer;
    }

    public function getFulfillmentHandler(): ?\Lunar\Contracts\FulfillmentHandler
    {
        if (!$this->fulfillment_handler || !class_exists($this->fulfillment_handler)) {
            return null;
        }

        $handler = new $this->fulfillment_handler($this->settings ?? []);
        
        if (!($handler instanceof \Lunar\Contracts\FulfillmentHandler)) {
            throw new \Exception("Fulfillment handler {$this->fulfillment_handler} must implement FulfillmentHandler interface");
        }

        return $handler;
    }
}
