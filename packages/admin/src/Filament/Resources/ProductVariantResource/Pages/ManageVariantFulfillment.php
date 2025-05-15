<?php

namespace Lunar\Admin\Filament\Resources\ProductVariantResource\Pages;

use Filament\Forms\Form;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Illuminate\Contracts\Support\Htmlable;
use Lunar\Admin\Filament\Resources\ProductVariantResource;
use Lunar\Admin\Support\Pages\BaseEditRecord;
use Lunar\Models\Supplier;
use Illuminate\Support\Collection;
use Filament\Forms\Components\Component;
use Filament\Actions\Action;
use Filament\Support\Facades\FilamentIcon;
use Illuminate\Database\Eloquent\Model;
use Lunar\Admin\Filament\Resources\ProductResource;

use App\Filament\Resources\YourResource;
use Filament\Forms;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

class ManageVariantFulfillment extends BaseEditRecord
{
    protected static string $resource = ProductVariantResource::class;

    public function getOwnerRecord()
    {
        return $this->getRecord();
    }

    public function getTitle(): string|Htmlable
    {
        return __('lunarpanel::productvariant.pages.fulfillment.title');
    }

    public static function getNavigationLabel(): string
    {
        return __('lunarpanel::productvariant.pages.fulfillment.title');
    }

    public static function getNavigationIcon(): ?string
    {
        return FilamentIcon::resolve('lunar::product-fulfillment');
    }

    protected function getDefaultHeaderActions(): array
    {
        return [
            ProductVariantResource::getVariantSwitcherWidget(
                $this->getRecord()
            ),
        ];
    }

    protected function getCancelFormAction(): Action
    {
        return parent::getCancelFormAction()->url(function (Model $record) {
            return ProductResource::getUrl('variants', [
                'record' => $record->product,
            ]);
        });
    }

    public function getBreadcrumbs(): array
    {
        return [
            ...ProductVariantResource::getBaseBreadcrumbs(
                $this->getRecord()
            ),
            ProductVariantResource::getUrl('fulfillment', [
                'record' => $this->getRecord(),
            ]) => $this->getTitle(),
        ];
    }

    public function getRelationManagers(): array
    {
        return [];
    }

    public function form(Form $form): Form
    {
        $suppliers = Supplier::all();
    
        //dd($this->getRecord());
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
                        ->label(__('lunarpanel::productvariant.pages.fulfillment.form.supplier.label'))
                        ->options($suppliers->pluck('name', 'id'))
                        ->searchable()
                        ->preload()
                        ->native(false)
                        ->placeholder(__('lunarpanel::productvariant.pages.fulfillment.form.supplier.placeholder'))
                        ->helperText(__('lunarpanel::productvariant.pages.fulfillment.form.supplier.helper_text'))
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
                                    ->title('Supplier Selected')
                                    ->body(__('lunarpanel::productvariant.pages.fulfillment.form.supplier.selected', [
                                        'name' => $supplier->name
                                    ]))
                                    ->send();
                            }
                        })
                        ->prefixIcon('heroicon-m-building-storefront')
                        ->loadingMessage(__('lunarpanel::productvariant.pages.fulfillment.form.supplier.loading'))
                        ->searchPrompt(__('lunarpanel::productvariant.pages.fulfillment.form.supplier.search_prompt'))
                        ->noSearchResultsMessage(__('lunarpanel::productvariant.pages.fulfillment.form.supplier.no_results'))
                        ->searchingMessage(__('lunarpanel::productvariant.pages.fulfillment.form.supplier.searching'))
                        ->searchDebounce(500)
                        ->optionsLimit(15)
                        ->afterStateUpdated(fn (Select $component) => $component
                            ->getContainer()
                            ->getComponent('dynamicFulfillmentFields')
                            ->getChildComponentContainer()
                            ->fill())
                ])
                ->columns(1)
        ], $supplierSections));
    }
}
