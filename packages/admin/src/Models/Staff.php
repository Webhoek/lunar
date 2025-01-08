<?php

namespace Lunar\Admin\Models;

use App\Models\Address;
use App\Models\Order;
use App\Models\RoadmapItem;
use App\Models\Subscription;
use App\Models\Supplier;
use App\Models\SupplierUser;
use App\Models\Tenant;
use App\Models\TenantUser;
use App\Models\Transaction;
use App\Models\UserParameter;
use App\Models\UserStripeData;
use App\Notifications\Auth\QueuedVerifyEmail;
use App\Services\OrderManager;
use App\Services\SubscriptionManager;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasName;
use Filament\Models\Contracts\HasTenants;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
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
use Spatie\Permission\Traits\HasRoles;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;
use Stancl\Tenancy\Database\Concerns\CentralConnection;
use Stancl\Tenancy\Database\Concerns\TenantConnection;

class Staff extends Authenticatable implements FilamentUser, HasName,  MustVerifyEmail, HasTenants
{
    use HasFactory;
    use HasRoles;
    use Notifiable;
    use SoftDeletes;

    /**
     * Return a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return StaffFactory::new();
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'firstname',
        'lastname',
        'admin',
        'email',
        'password',
        'is_admin',
        'public_name',
        'is_blocked',
    ];

    protected $guard_name = 'staff';

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    /**
     * Append attributes to the model.
     *
     * @var array
     */
    protected $appends = ['fullName'];

    /**
     * Create a new instance of the Model.
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->setTable(config('lunar.database.table_prefix').$this->getTable());

        if ($connection = config('lunar.database.connection')) {
            $this->setConnection($connection);
        }
    }

    /**
     * Retrieve the model for a bound value.
     *
     * Currently Livewire doesn't support route bindings for
     * soft deleted models so we need to rewire it here.
     *
     * @param  mixed  $value
     * @param  string|null  $field
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function resolveRouteBinding($value, $field = null)
    {
        return $this->resolveSoftDeletableRouteBinding($value, $field);
    }

    /**
     * Apply the basic search scope to a given Eloquent query builder.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $term
     * @return void
     */
    public function scopeSearch($query, $term)
    {
        if ($term) {
            $parts = explode(' ', $term);

            foreach ($parts as $part) {
                $query->whereAny(['email', 'firstname', 'lastname'], 'LIKE', "%$part%");
            }
        }
    }

    /**
     * Get staff member's full name.
     */
    public function getFullNameAttribute(): string
    {
        return $this->firstname.' '.$this->lastname;
    }


    public function getFilamentName(): string
    {
        return $this->fullName;
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

    public function canAccessPanel(Panel $panel): bool
    {
        if ($panel->getId() == 'admin' && ! $this->is_admin) {
            return false;
        }

        return true;
    }

    public function getPublicName()
    {
        return $this->public_name ?? $this->name;
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
        $subscriptionManager = app(SubscriptionManager::class);

        return $subscriptionManager->isUserSubscribed($this, $productSlug, $tenant);
    }

    public function isTrialing(?string $productSlug = null, ?Tenant $tenant = null): bool
    {
        /** @var SubscriptionManager $subscriptionManager */
        $subscriptionManager = app(SubscriptionManager::class);

        return $subscriptionManager->isUserTrialing($this, $productSlug, $tenant);
    }

    public function hasPurchased(?string $productSlug = null, ?Tenant $tenant = null): bool
    {
        /** @var OrderManager $orderManager */
        $orderManager = app(OrderManager::class);

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
            'tenant_user', // Specify the pivot table
            'user_id',     // Foreign key on the pivot table for the user
            'tenant_id'    // Foreign key on the pivot table for the tenant
        )->using(TenantUser::class)->withPivot('id')->withTimestamps();
    }

    public function getTenants(Panel $panel): Collection
    {
        return $this->tenants;
    }

    public function canAccessTenant(Model $tenant): bool
    {
        if($tenant instanceof Supplier){
            return $this->suppliers()->whereKey($tenant)->exists();

        }

        return $this->tenants()->whereKey($tenant)->exists();
    }

    public function suppliers(): BelongsToMany
    {
        return $this->belongsToMany(
            Supplier::class,
            'supplier_user', // Specify the pivot table
            'user_id',     // Foreign key on the pivot table for the user
            'supplier_id'    // Foreign key on the pivot table for the tenant
        )->using(SupplierUser::class)->withPivot('id')->withTimestamps();
    }

}
