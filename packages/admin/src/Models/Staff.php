<?php

namespace Lunar\Admin\Models;

use App\Models\Address;
use App\Models\Order;
use App\Models\RoadmapItem;
use App\Models\Subscription;
use App\Models\SupplierUser;
use App\Models\Tenant;
use App\Models\TenantUser;
use App\Models\Transaction;
use App\Models\UserParameter;
use App\Models\UserStripeData;
use App\Notifications\Auth\QueuedVerifyEmail;
use App\Services\OrderService;
use App\Services\SubscriptionService;
use Brightnexo\SyncApp\Models\Account;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasName;
use Filament\Models\Contracts\HasTenants;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Lunar\Admin\Database\Factories\StaffFactory;
use Lunar\Models\Supplier;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property int $id
 * @property bool $admin
 * @property string $first_name
 * @property string $last_name
 * @property string $full_name
 * @property string $email
 * @property string $password
 * @property string $remember_token
 * @property ?\Illuminate\Support\Carbon $email_verified_at
 * @property ?\Illuminate\Support\Carbon $created_at
 * @property ?\Illuminate\Support\Carbon $updated_at
 * @property ?\Illuminate\Support\Carbon $deleted_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder search(?string $terms)
 */

class Staff extends Authenticatable implements FilamentUser, HasName, MustVerifyEmail, HasTenants
{
    use HasFactory;
    use HasRoles;
    use Notifiable;
    use SoftDeletes;

    protected $guard_name = 'staff';

    protected $fillable = [
        'first_name',
        'last_name',
        'first_name',
        'last_name',
        'admin',
        'email',
        'phone',
        'password',
        'is_admin',
        'public_name',
        'is_blocked',
        'language',
        'last_activity_at',
        'legacy_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'admin' => 'bool',
        'email_verified_at' => 'datetime',
        'last_activity_at' => 'datetime',
        'password' => 'hashed',
    ];

    protected function firstname(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value, array $attributes) => $attributes['first_name'],
            set: fn (string $value) => ['first_name' => $value],
        );
    }

    protected function lastname(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value, array $attributes) => $attributes['last_name'],
            set: fn (string $value) => ['last_name' => $value],
        );
    }

    protected function fullName(): Attribute
    {
        return Attribute::get(
            fn (): string => "{$this->first_name} {$this->last_name}",
        );
    }

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->setTable(config('lunar.database.table_prefix').$this->getTable());

        if ($connection = config('lunar.database.connection')) {
            $this->setConnection($connection);
        }
    }

    /**
     * Create a new factory instance for the model.
     *
     * @return \Lunar\Admin\Database\Factories\StaffFactory
     */
    protected static function newFactory()
    {
        return StaffFactory::new();
    }

    public function scopeSearch(Builder $query, ?string $terms): void
    {
        if (! $terms) {
            return;
        }

        foreach (explode(' ', $terms) as $term) {
            $query->whereAny(['email', 'first_name', 'last_name'], 'LIKE', "%{$term}%");
        }
    }

    public function getFilamentName(): string
    {
        return $this->full_name;
    }

    public function roadmapItems(): HasMany
    {
        return $this->hasMany(RoadmapItem::class);
    }

    public function roadmapItemUpvotes(): BelongsToMany
    {
        return $this->belongsToMany(RoadmapItem::class, 'roadmap_item_user_upvotes');
    }

    public function userParameters(): HasMany
    {
        return $this->hasMany(UserParameter::class);
    }

    public function stripeData(): HasMany
    {
        return $this->hasMany(UserStripeData::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class, 'user_id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'user_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'user_id');
    }

    public function getPublicName()
    {
        return $this->public_name ?? $this->name;
    }

    public function canAccessPanel(Panel $panel): bool
    {
        if ($panel->getId() == 'admin' && ! $this->is_admin) {
            return false;
        }

        return true;
    }

    public function scopeAdmin($query)
    {
        return $query->where('is_admin', true);
    }

    public function isAdmin()
    {
        return $this->is_admin;
    }

    public function canImpersonate()
    {
        return $this->hasPermissionTo('impersonate users') && $this->isAdmin();
    }

    public function isSubscribed(?string $productSlug = null, ?Tenant $tenant = null): bool
    {
        /** @var SubscriptionManager $subscriptionManager */
        $subscriptionManager = app(SubscriptionService::class);

        return $subscriptionManager->isUserSubscribed($this, $productSlug, $tenant);
    }

    public function isTrialing(?string $productSlug = null, ?Tenant $tenant = null): bool
    {
        /** @var SubscriptionManager $subscriptionManager */
        $subscriptionManager = app(SubscriptionService::class);

        return $subscriptionManager->isUserTrialing($this, $productSlug, $tenant);
    }

    public function hasPurchased(?string $productSlug = null, ?Tenant $tenant = null): bool
    {
        /** @var OrderManager $orderManager */
        $orderManager = app(OrderService::class);

        return $orderManager->hasUserOrdered($this, $productSlug, $tenant);
    }

    public function sendEmailVerificationNotification()
    {
        $this->notify(new QueuedVerifyEmail);
    }

    public function address(): HasOne
    {
        return $this->hasOne(Address::class, 'user_id');
    }

    public function tenants(): BelongsToMany
    {
        return $this->belongsToMany(
            Tenant::class,
            'tenant_user',
            'user_id',
            'tenant_id'
        )->using(TenantUser::class)->withPivot('id')->withTimestamps();
    }

    public function getTenants(Panel $panel): Collection
    {
        return $this->tenants;
    }

    public function canAccessTenant(Model $tenant): bool
    {
        if ($tenant instanceof Supplier) {
            return $this->suppliers()->whereKey($tenant)->exists();
        }

        return $this->tenants()->whereKey($tenant)->exists();
    }

    public function suppliers(): BelongsToMany
    {
        return $this->belongsToMany(
            Supplier::class,
            'supplier_user',
            'user_id',
            'supplier_id'
        )->using(SupplierUser::class)->withPivot('id')->withTimestamps();
    }
}
