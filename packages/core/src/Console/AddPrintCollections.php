<?php

namespace Lunar\Console;

use App\Models\Tenant;
use Filament\Facades\Filament;
use Illuminate\Console\Command;
use Lunar\Models\Collection;
use Lunar\Models\CollectionGroup;
use Lunar\FieldTypes\TranslatedText;
use App\Scopes\TenantScope;

class AddPrintCollections extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lunar:add-print-collections';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add print collections for all tenants';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $tenants = Tenant::all();

        $tenants->each(function($tenant) {
            $this->components->info('Adding print collections for tenant: ' . $tenant->name);
            Filament::setTenant($tenant, true);
            $this->addPrintCollections();
        });

        $this->components->info('Print collections added successfully for all tenants 🚀');
    }

    /**
     * Add print collections to the database.
     */
    private function addPrintCollections(): void
    {
        // Get or create a collection group for print categories
        $printGroup = CollectionGroup::firstOrCreate(
            ['handle' => 'print-categories', 'tenant_id' => Filament::getTenant()->id],
            ['name' => 'Print Categories']
        );

        $this->components->info('Adding print categories');

        // Define print categories with English and Dutch translations
        $printCategories = [
            'business-cards' => [
                'name' => 'Business Cards',
                'description' => 'Professional business cards for your company',
                'nl_name' => 'Visitekaartjes',
                'nl_description' => 'Professionele visitekaartjes voor uw bedrijf',
                'children' => [
                    'standard-business-cards' => [
                        'name' => 'Standard Business Cards',
                        'description' => 'Classic business cards with your company information',
                        'nl_name' => 'Standaard Visitekaartjes',
                        'nl_description' => 'Klassieke visitekaartjes met uw bedrijfsinformatie'
                    ],
                    'premium-business-cards' => [
                        'name' => 'Premium Business Cards',
                        'description' => 'High-quality business cards with special finishes',
                        'nl_name' => 'Premium Visitekaartjes',
                        'nl_description' => 'Hoogwaardige visitekaartjes met speciale afwerkingen'
                    ],
                    'metal-business-cards' => [
                        'name' => 'Metal Business Cards',
                        'description' => 'Durable metal business cards for a unique look',
                        'nl_name' => 'Metalen Visitekaartjes',
                        'nl_description' => 'Duurzame metalen visitekaartjes voor een unieke uitstraling'
                    ],
                ]
            ],
            'flyers-brochures' => [
                'name' => 'Flyers & Brochures',
                'description' => 'Marketing materials to promote your business',
                'nl_name' => 'Flyers & Brochures',
                'nl_description' => 'Marketingmateriaal om uw bedrijf te promoten',
                'children' => [
                    'single-sided-flyers' => [
                        'name' => 'Single-Sided Flyers',
                        'description' => 'Cost-effective flyers for promotions and events',
                        'nl_name' => 'Enkelzijdige Flyers',
                        'nl_description' => 'Kosteneffectieve flyers voor promoties en evenementen'
                    ],
                    'double-sided-flyers' => [
                        'name' => 'Double-Sided Flyers',
                        'description' => 'Flyers with information on both sides',
                        'nl_name' => 'Dubbelzijdige Flyers',
                        'nl_description' => 'Flyers met informatie aan beide zijden'
                    ],
                    'tri-fold-brochures' => [
                        'name' => 'Tri-Fold Brochures',
                        'description' => 'Professional brochures with multiple panels',
                        'nl_name' => 'Drievoudige Brochures',
                        'nl_description' => 'Professionele brochures met meerdere panelen'
                    ],
                    'booklet-brochures' => [
                        'name' => 'Booklet Brochures',
                        'description' => 'Multi-page brochures for detailed information',
                        'nl_name' => 'Boekje Brochures',
                        'nl_description' => 'Meerbladige brochures voor gedetailleerde informatie'
                    ],
                ]
            ],
            'banners-signs' => [
                'name' => 'Banners & Signs',
                'description' => 'Large format printing for outdoor and indoor use',
                'nl_name' => 'Banners & Borden',
                'nl_description' => 'Grootformaat drukwerk voor binnen- en buitengebruik',
                'children' => [
                    'vinyl-banners' => [
                        'name' => 'Vinyl Banners',
                        'description' => 'Durable banners for outdoor advertising',
                        'nl_name' => 'Vinyl Banners',
                        'nl_description' => 'Duurzame banners voor buitenshuis advertenties'
                    ],
                    'x-stand-banners' => [
                        'name' => 'X-Stand Banners',
                        'description' => 'Portable banners with stand for events',
                        'nl_name' => 'X-Stand Banners',
                        'nl_description' => 'Draagbare banners met standaard voor evenementen'
                    ],
                    'roll-up-banners' => [
                        'name' => 'Roll-Up Banners',
                        'description' => 'Retractable banners for exhibitions',
                        'nl_name' => 'Roll-Up Banners',
                        'nl_description' => 'Intrekbare banners voor tentoonstellingen'
                    ],
                    'window-signs' => [
                        'name' => 'Window Signs',
                        'description' => 'Signs designed for window display',
                        'nl_name' => 'Ruitborden',
                        'nl_description' => 'Borden ontworpen voor etalageweergave'
                    ],
                ]
            ],
            'stickers-labels' => [
                'name' => 'Stickers & Labels',
                'description' => 'Custom stickers and labels for various applications',
                'nl_name' => 'Stickers & Labels',
                'nl_description' => 'Op maat gemaakte stickers en labels voor verschillende toepassingen',
                'children' => [
                    'vinyl-stickers' => [
                        'name' => 'Vinyl Stickers',
                        'description' => 'Durable stickers for outdoor use',
                        'nl_name' => 'Vinyl Stickers',
                        'nl_description' => 'Duurzame stickers voor buitenshuis gebruik'
                    ],
                    'product-labels' => [
                        'name' => 'Product Labels',
                        'description' => 'Labels for product packaging',
                        'nl_name' => 'Product Labels',
                        'nl_description' => 'Labels voor productverpakkingen'
                    ],
                    'custom-die-cut-stickers' => [
                        'name' => 'Custom Die-Cut Stickers',
                        'description' => 'Uniquely shaped stickers for branding',
                        'nl_name' => 'Op Maat Gesneden Stickers',
                        'nl_description' => 'Uniek gevormde stickers voor branding'
                    ],
                ]
            ],
            'photo-prints' => [
                'name' => 'Photo Prints',
                'description' => 'High-quality photo printing services',
                'nl_name' => 'Fotoafdrukken',
                'nl_description' => 'Hoogwaardige fotoprintservices',
                'children' => [
                    'standard-photo-prints' => [
                        'name' => 'Standard Photo Prints',
                        'description' => 'Classic photo prints in various sizes',
                        'nl_name' => 'Standaard Fotoafdrukken',
                        'nl_description' => 'Klassieke fotoafdrukken in verschillende formaten'
                    ],
                    'canvas-prints' => [
                        'name' => 'Canvas Prints',
                        'description' => 'Photos printed on canvas for wall display',
                        'nl_name' => 'Canvas Afdrukken',
                        'nl_description' => 'Foto\'s afgedrukt op canvas voor wandweergave'
                    ],
                    'photo-books' => [
                        'name' => 'Photo Books',
                        'description' => 'Custom photo books for your memories',
                        'nl_name' => 'Fotoboeken',
                        'nl_description' => 'Op maat gemaakte fotoboeken voor uw herinneringen'
                    ],
                    'photo-calendars' => [
                        'name' => 'Photo Calendars',
                        'description' => 'Personalized calendars with your photos',
                        'nl_name' => 'Foto Kalenders',
                        'nl_description' => 'Gepersonaliseerde kalenders met uw foto\'s'
                    ],
                ]
            ],
        ];

        // Create collections for each category
        foreach ($printCategories as $handle => $categoryData) {
            // First check if collection exists by handle
            $collection = Collection::where('collection_group_id', $printGroup->id)
                ->tenant()
                ->where('handle', $handle)
                ->first();

            $this->components->info('Adding print category: ' . $handle);

            // If not found, create it
            if (!$collection) {
                $collection = Collection::withGlobalScope(TenantScope::class, new TenantScope)->create([
                    'collection_group_id' => $printGroup->id,
                    'handle' => $handle,
                    'attribute_data' => [
                        'name' => new TranslatedText([
                            'en' => $categoryData['name'],
                            'nl' => $categoryData['nl_name'],
                        ]),
                        'description' => new TranslatedText([
                            'en' => $categoryData['description'],
                            'nl' => $categoryData['nl_description'],
                        ]),
                    ],
                ]);
            }

            // Create child collections if they exist
            if (isset($categoryData['children'])) {
                foreach ($categoryData['children'] as $childHandle => $childData) {
                    // Check if child collection exists by handle
                    $childCollection = Collection::where('collection_group_id', $printGroup->id)
                        ->where('handle', $childHandle)
                        ->first();

                    // If not found, create it and append to parent
                    if (!$childCollection) {
                        $childCollection = new Collection([
                            'collection_group_id' => $printGroup->id,
                            'handle' => $childHandle,
                            'attribute_data' => [
                                'name' => new TranslatedText([
                                    'en' => $childData['name'],
                                    'nl' => $childData['nl_name'],
                                ]),
                                'description' => new TranslatedText([
                                    'en' => $childData['description'],
                                    'nl' => $childData['nl_description'],
                                ]),
                            ],
                        ]);
                        
                        $collection->appendNode($childCollection);
                    }
                }
            }
        }
    }
} 