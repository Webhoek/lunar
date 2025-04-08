<?php

namespace Lunar\Console;

use App\Models\Tenant;
use Filament\Facades\Filament;
use Illuminate\Console\Command;
use Lunar\Models\Channel;
use Lunar\Models\CollectionGroup;
use Lunar\Models\Currency;
use Lunar\Models\CustomerGroup;
use Lunar\Models\Language;
use Lunar\Facades\DB;

class SeedTenant extends Command
{
    protected $signature = 'lunar:seed-tenant {--tenant= : The tenant ID to seed} {--all : Seed all tenants}';

    protected $description = 'Seed tenant data for Lunar';

    public function handle(): void
    {
        DB::transaction(function () {
            if ($tenantId = $this->option('tenant')) {
                $tenants = Tenant::where('id', $tenantId)->get();
            } elseif ($this->option('all')) {
                $tenants = Tenant::all();
            } else {
                $this->error('Please specify either --tenant=ID or --all option');
                return;
            }

            $tenants->each(function($tenant) {
                $this->components->info('Start Importing tenant '. $tenant->name);
                Filament::setTenant($tenant, true);

                if (! Channel::where('tenant_id', $tenant->id)->whereDefault(true)->exists()) {
                    $this->components->info('Setting up default channel');

                    Channel::create([
                        'name' => 'Webstore',
                        'handle' => 'brightnexo-test-shop.local',
                        'default' => true,
                        'integration_id' => 1,
                        'settings' => [
                            'api_key' => 'ck_5aeca9fab95fc09223bd77512c8aaf865332f8ae',
                            'api_secret' => 'cs_9fc1b14510d82564ed49db833f4d6a9cf3fa47de',
                        ],
                        'url' => 'https://brightnexo-test-shop.local',
                    ]);
                }

                if (! Language::where('tenant_id', $tenant->id)->count()) {
                    $this->components->info('Adding default language');

                    Language::create([
                        'code' => 'en',
                        'name' => 'English',
                        'default' => true,
                    ]);
                }

                if (! Currency::where('tenant_id', $tenant->id)->whereDefault(true)->exists()) {
                    $this->components->info('Adding a default currency (USD)');

                    Currency::create([
                        'code' => 'USD',
                        'name' => 'US Dollar',
                        'exchange_rate' => 1,
                        'decimal_places' => 2,
                        'default' => true,
                        'enabled' => true,
                    ]);
                }

                if (! CustomerGroup::where('tenant_id', $tenant->id)->whereDefault(true)->exists()) {
                    $this->components->info('Adding a default customer group.');

                    CustomerGroup::create([
                        'name' => 'Retail',
                        'handle' => 'retail',
                        'default' => true,
                    ]);
                }

                if (! CollectionGroup::where('tenant_id', $tenant->id)->count()) {
                    $this->components->info('Adding an initial collection group');

                    CollectionGroup::create([
                        'name' => 'Main',
                        'handle' => 'main',
                        'tenant_id' => $tenant->id,
                    ]);
                }

                $this->components->info('Finished seeding tenant '. $tenant->name);
            });
        });
    }
} 