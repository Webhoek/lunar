<?php

namespace Lunar\Admin\Filament\Resources\ProductResource\Pages;

use Filament\Forms\Form;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Illuminate\Contracts\Support\Htmlable;
use Lunar\Admin\Filament\Resources\ProductResource;
use Lunar\Admin\Support\Pages\BaseEditRecord;
use Lunar\Models\Supplier;
use Illuminate\Support\Collection;
use Filament\Forms\Components\Component;
use Filament\Actions\Action;
use Filament\Support\Facades\FilamentIcon;
use Illuminate\Database\Eloquent\Model;
use Filament\Forms;
use Filament\Notifications\Notification;

class ManageProductFulfillment extends BaseEditRecord
{
    protected static string $resource = ProductResource::class;

    public function getOwnerRecord()
    {
        return $this->getRecord();
    }

    public function getTitle(): string|Htmlable
    {
        return __('lunarpanel::product.pages.fulfillment.title');
    }

    public static function getNavigationLabel(): string
    {
        return __('lunarpanel::product.pages.fulfillment.title');
    }

    public static function shouldRegisterNavigation(array $parameters = []): bool
    {
        return $parameters['record']->variants()->count() == 1;// && $parameters['record']->type !== 'probo-dynamic';
    }

    public static function getNavigationIcon(): ?string
    {
        return FilamentIcon::resolve('lunar::product-fulfillment');
    }

    protected function getDefaultHeaderActions(): array
    {
        return [];
    }

    protected function getCancelFormAction(): Action
    {
        return parent::getCancelFormAction()->url(function (Model $record) {
            return ProductResource::getUrl('edit', [
                'record' => $record,
            ]);
        });
    }

    public function getBreadcrumbs(): array
    {
        return [
            ProductResource::getUrl() => ProductResource::getModelLabel(),
            ProductResource::getUrl('edit', ['record' => $this->getRecord()]) => $this->getRecord()->translateAttribute('name'),
            ProductResource::getUrl('fulfillment', ['record' => $this->getRecord()]) => $this->getTitle(),
        ];
    }

    public function getRelationManagers(): array
    {
        return [];
    }

    public function form(Form $form): Form
    {
        $suppliers = Supplier::all();
    
        $supplierSections = [];
        foreach ($suppliers as $supplier) {
            if ($supplier->getFulfillmentHandler()) {
                $supplierSections[] = Forms\Components\Section::make($supplier->name . ' Settings')
                    ->schema(fn () => $supplier->getFulfillmentHandler()->getFormSchema($form))
                    ->visible(fn (callable $get) => $get('supplier_id') == $supplier->id)
                    ->statePath('fulfillment_data')
                    ->key('dynamicFulfillmentFields')
                    ->collapsible();
            }
        }

        return $form->schema(array_merge([
            Forms\Components\Section::make()
                ->schema([
                    Forms\Components\Select::make('supplier_id')
                        ->label(__('lunarpanel::product.pages.fulfillment.form.supplier.label'))
                        ->options($suppliers->pluck('name', 'id'))
                        ->searchable()
                        ->preload()
                        ->native(false)
                        ->placeholder(__('lunarpanel::product.pages.fulfillment.form.supplier.placeholder'))
                        ->helperText(__('lunarpanel::product.pages.fulfillment.form.supplier.helper_text'))
                        ->required()
                        ->reactive()
                        ->afterStateUpdated(function ($state) {
                            if (!$state) {
                                return;
                            }
                            $supplier = Supplier::find($state);
                            if ($supplier) {
                                Notification::make()
                                    ->success()
                                    ->title(__('lunarpanel::product.pages.fulfillment.title'))
                                    ->body(__('lunarpanel::product.pages.fulfillment.form.supplier.selected', [
                                        'name' => $supplier->name
                                    ]))
                                    ->send();
                            }
                        })
                        ->prefixIcon('heroicon-m-building-storefront')
                        ->loadingMessage(__('lunarpanel::product.pages.fulfillment.form.supplier.loading'))
                        ->searchPrompt(__('lunarpanel::product.pages.fulfillment.form.supplier.search_prompt'))
                        ->noSearchResultsMessage(__('lunarpanel::product.pages.fulfillment.form.supplier.no_results'))
                        ->searchingMessage(__('lunarpanel::product.pages.fulfillment.form.supplier.searching'))
                        ->searchDebounce(500)
                        ->optionsLimit(15)
                        ->afterStateUpdated(function (Select $component) {
                            $container = $component->getContainer();
                            if (!$container) {
                                return;
                            }

                            $dynamicFields = $container->getComponent('dynamicFulfillmentFields');
                            if (!$dynamicFields) {
                                return;
                            }

                            $childContainer = $dynamicFields->getChildComponentContainer();
                            if ($childContainer) {
                                $childContainer->fill();
                            }
                        })
                ])
                ->columns(1)
        ], $supplierSections));
    }
} 