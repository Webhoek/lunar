<?php

namespace Lunar\Admin\Filament\Resources\ProductResource\Pages;

use App\Filament\Admin\Resources\ProductResource as ResourcesProductResource;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Components\Wizard\Step;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Model;
use Lunar\Admin\Filament\Resources\ProductResource;
use Lunar\Models\Product;
use Illuminate\Support\Str;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\CheckboxList;
use Illuminate\Support\HtmlString;
use Lunar\Admin\Support\Pages\BaseEditRecord;
use Lunar\Models\Attribute;

class DuplicateProduct extends BaseEditRecord
{
    protected static string $resource = ResourcesProductResource::class;

    protected static string $view = 'lunar::filament.pages.duplicate-product';

    public ?Product $record = null;

    public ?array $data = [];

    public static function route(string $path): array
    {
        return [
            'path' => $path,
            'name' => static::getRouteName(),
        ];
    }

    public static function getRouteName(): string
    {
        return 'duplicate';
    }

    // public function mount(Product $record): void
    // {
    //     $this->record = $record;
    //     $this->form->fill([
    //         'name' => $record->translateAttribute('name') . ' (Copy)',
    //         'include_variants' => true,
    //         'include_media' => true,
    //         'include_prices' => true,
    //         'include_urls' => false,
    //         'status' => 'draft'
    //     ]);
    // }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Wizard::make([
                    Step::make('Basic Settings')
                        ->icon('heroicon-o-cog')
                        ->schema([
                            TextInput::make('name')
                                ->label('Product Name')
                                ->required()
                                ->maxLength(255),
                            Select::make('status')
                                ->options([
                                    'draft' => 'Draft',
                                    'published' => 'Published',
                                ])
                                ->default('draft')
                                ->required(),
                        ]),

                    Step::make('Components')
                        ->icon('heroicon-o-clipboard')
                        ->schema([
                            Toggle::make('include_variants')
                                ->label('Include Variants')
                                ->helperText('Copy all product variants')
                                ->default(true),
                            Toggle::make('include_media')
                                ->label('Include Media')
                                ->helperText('Copy all associated media')
                                ->default(true),
                            Toggle::make('include_prices')
                                ->label('Include Prices')
                                ->helperText('Copy all price points')
                                ->default(true),
                            Toggle::make('include_urls')
                                ->label('Include URLs')
                                ->helperText('Copy all URLs (will be modified to avoid duplicates)')
                                ->default(false),
                        ]),

                    Step::make('Attributes')
                        ->icon('heroicon-o-document-text')
                        ->schema([
                            Toggle::make('copy_attributes')
                                ->label('Copy Attributes')
                                ->helperText('Copy all product attributes')
                                ->default(true)
                                ->live(),
                            
                            CheckboxList::make('selected_attributes')
                                ->label('Select Attributes to Copy')
                                ->options(function() {
                                    $attributes = collect($this->record->attribute_data ?? []);
                                    return $attributes->mapWithKeys(function($value, $key) {
                                        return [$key => ucfirst($key)];
                                    });
                                })
                                ->columns(2)
                                ->visible(fn (callable $get) => $get('copy_attributes'))
                                ->default(function() {
                                    return collect($this->record->attribute_data ?? [])
                                        ->keys()
                                        ->toArray();
                                }),
                        ]),

                    Step::make('Review')
                        ->icon('heroicon-o-check-circle')
                        ->description('Review your duplication settings')
                        ->schema([
                            Placeholder::make('name_review')
                                ->label('New Product Name')
                                ->content(fn (callable $get) => $get('name')),
                                
                            Placeholder::make('status_review')
                                ->label('Status')
                                ->content(fn (callable $get) => ucfirst($get('status'))),
                                
                            Placeholder::make('components_review')
                                ->label('Components to Copy')
                                ->content(function (callable $get) {
                                    $components = [];
                                    if ($get('include_variants')) $components[] = 'Variants';
                                    if ($get('include_media')) $components[] = 'Media';
                                    if ($get('include_prices')) $components[] = 'Prices';
                                    if ($get('include_urls')) $components[] = 'URLs';
                                    return implode(', ', $components);
                                }),
                                
                            Placeholder::make('attributes_review')
                                ->label('Attributes to Copy')
                                ->content(function (callable $get) {
                                    if (!$get('copy_attributes')) return 'None';
                                    return implode(', ', array_map('ucfirst', $get('selected_attributes')));
                                }),
                        ]),
                ])
                ->submitAction(new HtmlString('<button type="submit">Duplicate</button>'))
                
            ])
            ->statePath('data');
    }

    public function duplicate(): void
    {
        $data = $this->form->getState();

        try {
            \DB::beginTransaction();

            // Create the new product
            $newProduct = $this->record->replicate();
            $newProduct->status = $data['status'];
            
            // Handle attribute data
            if ($data['copy_attributes']) {
                $selectedAttributes = $data['selected_attributes'] ?? [];
                $attributeData = collect($this->record->attribute_data)
                    ->only($selectedAttributes)
                    ->toArray();
                
                // Always include name attribute with new value
                $nameAttribute = $this->record->attribute_data['name'];
                $nameAttribute->setValue($data['name']);
                $attributeData['name'] = $nameAttribute;
                
                $newProduct->attribute_data = $attributeData;
            } else {
                // If not copying attributes, still need to set the name
                $nameAttribute = $this->record->attribute_data['name'];
                $nameAttribute->setValue($data['name']);
                $newProduct->attribute_data = ['name' => $nameAttribute];
            }

            $newProduct->save();

            // Handle variants
            if ($data['include_variants']) {
                foreach ($this->record->variants as $variant) {
                    $newVariant = $variant->replicate();
                    $newVariant->product_id = $newProduct->id;
                    $newVariant->save();

                    // Handle prices
                    if ($data['include_prices']) {
                        foreach ($variant->prices as $price) {
                            $newPrice = $price->replicate();
                            $newPrice->priceable_id = $newVariant->id;
                            $newPrice->save();
                        }
                    }
                }
            }

            // Handle media
            if ($data['include_media']) {
                foreach ($this->record->media as $media) {
                    $newMedia = $media->replicate();
                    $newMedia->model_id = $newProduct->id;
                    $newMedia->save();
                }
            }

            // Handle URLs
            if ($data['include_urls']) {
                foreach ($this->record->urls as $url) {
                    $newUrl = $url->replicate();
                    $newUrl->element_id = $newProduct->id;
                    $newUrl->slug = Str::slug($data['name']);
                    $newUrl->save();
                }
            }

            \DB::commit();

            Notification::make()
                ->success()
                ->title('Product duplicated successfully')
                ->send();

            $this->redirect(ProductResource::getUrl('edit', ['record' => $newProduct]));
        } catch (\Exception $e) {
            \DB::rollBack();

            Notification::make()
                ->danger()
                ->title('Error duplicating product')
                ->body($e->getMessage())
                ->send();
        }
    }

    public function getTitle(): string
    {
        return 'Duplicate Product: ' . $this->record->translateAttribute('name');
    }
} 