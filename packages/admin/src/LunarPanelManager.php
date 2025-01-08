<?php

namespace Lunar\Admin;

use App\Constants\AnnouncementPlacement;
use App\Constants\TenancyPermissionConstants;
use App\Filament\Dashboard\Pages\Team;
use App\Filament\Dashboard\Pages\TenantSettings;
use App\Filament\Dashboard\Resources\InvitationResource;
use App\Filament\Dashboard\Resources\OrderResource;
use App\Filament\Dashboard\Resources\SubscriptionResource;
use App\Filament\Dashboard\Resources\TransactionResource;
use App\Models\Tenant;
use App\Services\TenantPermissionManager;
use Filament\Facades\Filament;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\MenuItem;
use Filament\Navigation\NavigationGroup;
use Filament\Panel;
use Filament\Support\Assets\Css;
use Filament\Support\Colors\Color;
use Filament\Support\Facades\FilamentColor;
use Filament\Support\Facades\FilamentIcon;
use Filament\Tables\Table;
use Filament\View\PanelsRenderHook;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Jeffgreco13\FilamentBreezy\BreezyCore;
use Lunar\Admin\Filament\AvatarProviders\GravatarProvider;
use Lunar\Admin\Filament\Pages;
use Lunar\Admin\Filament\Resources;
use Lunar\Admin\Filament\Widgets\Dashboard\Orders\AverageOrderValueChart;
use Lunar\Admin\Filament\Widgets\Dashboard\Orders\LatestOrdersTable;
use Lunar\Admin\Filament\Widgets\Dashboard\Orders\NewVsReturningCustomersChart;
use Lunar\Admin\Filament\Widgets\Dashboard\Orders\OrdersSalesChart;
use Lunar\Admin\Filament\Widgets\Dashboard\Orders\OrderStatsOverview;
use Lunar\Admin\Filament\Widgets\Dashboard\Orders\OrderTotalsChart;
use Lunar\Admin\Filament\Widgets\Dashboard\Orders\PopularProductsTable;
use Lunar\Admin\Http\Controllers\DownloadPdfController;
use Lunar\Admin\Support\Facades\LunarAccessControl;
use RalphJSmit\Filament\Onboard\Widgets\OnboardTrackWidget;

class LunarPanelManager
{
    protected ?\Closure $closure = null;

    protected array $extensions = [];

    protected string $panelId = 'lunar';

    protected static $resources = [
        Resources\ActivityResource::class,
        Resources\AttributeGroupResource::class,
        Resources\BrandResource::class,
        Resources\ChannelResource::class,
        Resources\CollectionGroupResource::class,
        Resources\CollectionResource::class,
        Resources\CurrencyResource::class,
        Resources\CustomerGroupResource::class,
        Resources\CustomerResource::class,
        Resources\DiscountResource::class,
        Resources\LanguageResource::class,
        Resources\OrderResource::class,
        Resources\ProductOptionResource::class,
        Resources\ProductResource::class,
        Resources\ProductTypeResource::class,
        Resources\ProductVariantResource::class,
        Resources\StaffResource::class,
        Resources\TagResource::class,
        Resources\TaxClassResource::class,
        Resources\TaxZoneResource::class,
        Resources\TaxRateResource::class,

        //Custom
        InvitationResource::class,
        OrderResource::class,
        SubscriptionResource::class,
        TransactionResource::class
    ];

    protected static $pages = [
        Pages\Dashboard::class,
        Team::class,
        TenantSettings::class
    ];

    protected static $widgets = [
        OnboardTrackWidget::class,
        AccountWidget::class,
        OrderStatsOverview::class,
        OrderTotalsChart::class,
        OrdersSalesChart::class,
        AverageOrderValueChart::class,
        NewVsReturningCustomersChart::class,
        PopularProductsTable::class,
        LatestOrdersTable::class,
    ];

    public function register(): self
    {
        $panel = $this->defaultPanel();

        if ($this->closure instanceof \Closure) {
            $fn = $this->closure;
            $panel = $fn($panel);
        }

        Filament::registerPanel($panel);

        FilamentIcon::register([
            // Filament
            'panels::topbar.global-search.field' => 'lucide-search',
            'actions::view-action' => 'lucide-eye',
            'actions::edit-action' => 'lucide-edit',
            'actions::delete-action' => 'lucide-trash-2',
            'actions::make-collection-root-action' => 'lucide-corner-left-up',

            // Lunar
            'lunar::activity' => 'lucide-activity',
            'lunar::attributes' => 'lucide-pencil-ruler',
            'lunar::availability' => 'lucide-calendar',
            'lunar::basic-information' => 'lucide-edit',
            'lunar::brands' => 'lucide-badge-check',
            'lunar::channels' => 'lucide-store',
            'lunar::collections' => 'lucide-blocks',
            'lunar::sub-collection' => 'lucide-square-stack',
            'lunar::move-collection' => 'lucide-move',
            'lunar::currencies' => 'lucide-circle-dollar-sign',
            'lunar::customers' => 'lucide-users',
            'lunar::customer-groups' => 'lucide-users',
            'lunar::dashboard' => 'lucide-bar-chart-big',
            'lunar::discounts' => 'lucide-percent-circle',
            'lunar::discount-limitations' => 'lucide-list-x',
            'lunar::info' => 'lucide-info',
            'lunar::languages' => 'lucide-languages',
            'lunar::media' => 'lucide-image',
            'lunar::orders' => 'lucide-inbox',
            'lunar::product-pricing' => 'lucide-coins',
            'lunar::product-associations' => 'lucide-cable',
            'lunar::product-inventory' => 'lucide-combine',
            'lunar::product-options' => 'lucide-list',
            'lunar::product-shipping' => 'lucide-truck',
            'lunar::product-variants' => 'lucide-shapes',
            'lunar::products' => 'lucide-tag',
            'lunar::staff' => 'lucide-shield',
            'lunar::tags' => 'lucide-tags',
            'lunar::tax' => 'lucide-landmark',
            'lunar::urls' => 'lucide-globe',
            'lunar::product-identifiers' => 'lucide-package-search',
            'lunar::reorder' => 'lucide-grip-vertical',
            'lunar::chevron-right' => 'lucide-chevron-right',
            'lunar::image-placeholder' => 'lucide-image',
            'lunar::trending-up' => 'lucide-trending-up',
            'lunar::trending-down' => 'lucide-trending-down',
            'lunar::exclamation-circle' => 'lucide-alert-circle',
        ]);

        FilamentColor::register([
            'chartPrimary' => Color::Blue,
            'chartSecondary' => Color::Green,
        ]);

        if (app('request')->is($panel->getPath().'*')) {
            app('config')->set('livewire.inject_assets', true);
        }

        Table::configureUsing(function (Table $table): void {
            $table
                ->paginationPageOptions([10, 25, 50, 100])
                ->defaultPaginationPageOption(25);
        });

        return $this;
    }

    public function panel(\Closure $closure): self
    {
        $this->closure = $closure;

        return $this;
    }

    public function getPanel(): Panel
    {
        return Filament::getPanel($this->panelId);
    }

    protected function defaultPanel(): Panel
    {
        $brandAsset = function ($asset) {
            $vendorPath = 'vendor/lunarpanel/';

            if (file_exists(public_path($vendorPath.$asset))) {
                return asset($vendorPath.$asset);
            } else {
                $type = str($asset)
                    ->endsWith('.png') ? 'image/png' : 'image/svg+xml';

                return "data:{$type};base64,".base64_encode(file_get_contents(__DIR__.'/../public/'.$asset));
            }
        };

        $panelMiddleware = [
            EncryptCookies::class,
            AddQueuedCookiesToResponse::class,
            StartSession::class,
            AuthenticateSession::class,
            ShareErrorsFromSession::class,
            VerifyCsrfToken::class,
            SubstituteBindings::class,
            DisableBladeIconComponents::class,
            DispatchServingFilamentEvent::class,
        ];

        if (config('lunar.panel.pdf_rendering', 'download') == 'stream') {
            Route::get('lunar/pdf/download', DownloadPdfController::class)
                ->name('lunar.pdf.download')->middleware($panelMiddleware);
        }

        return Panel::make()
            ->spa()
            ->default()
            ->id($this->panelId)
            ->brandName('lunar')
            ->brandLogo($brandAsset('lunar-logo.svg'))
            ->darkModeBrandLogo($brandAsset('lunar-logo-dark.svg'))
            ->favicon($brandAsset('lunar-icon.png'))
            ->brandLogoHeight('2rem')
            ->path('dashboard')
            ->authGuard('staff')
            ->defaultAvatarProvider(GravatarProvider::class)
            ->login()
            ->tenantMenu()
            ->tenant(Tenant::class, 'uuid')
            ->colors([
                'primary' => Color::Sky,
            ])
            ->font('Poppins')
            ->middleware($panelMiddleware)
            ->assets([
                Css::make('lunar-panel', __DIR__.'/../resources/dist/lunar-panel.css'),
            ], 'lunarphp/panel')
            ->pages(
                static::getPages()
            )
            ->resources(
                static::getResources()
            )
            ->discoverClusters(
                in: realpath(__DIR__.'/Filament/Clusters'),
                for: 'Lunar\Admin\Filament\Clusters'
            )
            ->widgets(
                static::getWidgets()
            )
            ->userMenuItems([
                MenuItem::make()
                    ->label('Admin Panel')
                    ->visible(
                        fn () => auth()->user()->isAdmin()
                    )
                    ->url(fn () => route('filament.admin.pages.dashboard'))
                    ->icon('heroicon-s-cog-8-tooth'),
                MenuItem::make()
                    ->label('Workspace Settings')
                    ->visible(
                        function () {
                            $tenantPermissionManager = app(TenantPermissionManager::class);

                            return $tenantPermissionManager->tenantUserHasPermissionTo(
                                Filament::getTenant(),
                                auth()->user(),
                                TenancyPermissionConstants::PERMISSION_UPDATE_TENANT_SETTINGS
                            );
                        }
                    )
                    ->icon('heroicon-s-cog-8-tooth')
                    ->url(fn () => TenantSettings::getUrl()),
            ])
            ->viteTheme('resources/css/filament/dashboard/theme.css')
            ->discoverWidgets(in: app_path('Filament/Dashboard/Widgets'), for: 'App\\Filament\\Dashboard\\Widgets')
            ->authMiddleware([
                Authenticate::class,
            ])->plugins([
                \Leandrocfe\FilamentApexCharts\FilamentApexChartsPlugin::make(),
                BreezyCore::make()
                    ->myProfile(
                        shouldRegisterUserMenu: true, // Sets the 'account' link in the panel User Menu (default = true)
                        shouldRegisterNavigation: false, // Adds a main navigation item for the My Profile page (default = false)
                        hasAvatars: false, // Enables the avatar upload form component (default = false)
                        slug: 'my-profile' // Sets the slug for the profile page (default = 'my-profile')
                    )
                    ->myProfileComponents([
                        \App\Livewire\AddressForm::class,
                    ]),
            ])

            ->renderHook('panels::head.start', function () {
                return view('components.layouts.partials.analytics');
            })
            ->renderHook(PanelsRenderHook::BODY_START,
                fn (): string => Blade::render("@livewire('announcement.view', ['placement' => '".AnnouncementPlacement::USER_DASHBOARD->value."'])")
            )
            ->discoverLivewireComponents(__DIR__.'/Livewire', 'Lunar\\Admin\\Livewire')
            ->livewireComponents([
                Resources\OrderResource\Pages\Components\OrderItemsTable::class,
                \Lunar\Admin\Filament\Resources\CollectionGroupResource\Widgets\CollectionTreeView::class,
            ])
            ->navigationGroups([
                'Catalog',
                'Sales',
                NavigationGroup::make()
                    ->label('Team')
                    ->icon('heroicon-s-users')
                    ->collapsed(),
                NavigationGroup::make()
                    ->label('Settings')
                    ->collapsed(),
            ])->sidebarCollapsibleOnDesktop();
    }

    public function extensions(array $extensions): self
    {
        foreach ($extensions as $class => $extension) {
            $this->extensions[$class][] = new $extension;
        }

        return $this;
    }

    /**
     * @return array<class-string<\Filament\Resources\Resource>>
     */
    public static function getResources(): array
    {
        return static::$resources;
    }

    /**
     * @return array<class-string<\Filament\Pages\Page>>
     */
    public static function getPages(): array
    {
        return static::$pages;
    }

    /**
     * @return array<class-string<\Filament\Widgets\Widget>>
     */
    public static function getWidgets(): array
    {
        return static::$widgets;
    }

    public function useRoleAsAdmin(string|array $roleHandle): self
    {
        LunarAccessControl::useRoleAsAdmin($roleHandle);

        return $this;
    }

    public function callHook(string $class, ?object $caller, string $hookName, ...$args): mixed
    {
        if (isset($this->extensions[$class])) {
            foreach ($this->extensions[$class] as $extension) {
                if (method_exists($extension, $hookName)) {
                    $extension->setCaller($caller);
                    $args[0] = $extension->{$hookName}(...$args);
                }
            }
        }

        return $args[0];
    }
}
