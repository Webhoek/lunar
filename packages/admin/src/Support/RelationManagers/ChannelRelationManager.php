<?php

namespace Lunar\Admin\Support\RelationManagers;

use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Actions\Action as ActionsAction;
use Filament\Notifications\Notification;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use Illuminate\Http\Client\HttpClientException;
use Lunar\Admin\Events\ModelChannelsUpdated;
use Lunar\Models\Channel;
use Lunar\Models\Product;

class ChannelRelationManager extends BaseRelationManager
{
    protected static bool $isLazy = false;

    protected static string $relationship = 'channels';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Form $form): Form
    {
        return $form->schema(
            static::getFormInputs()
        );
    }

    protected static function getFormInputs(): array
    {
        return [
            Forms\Components\Toggle::make('enabled')->label(
                __('lunarpanel::relationmanagers.channels.form.enabled.label')
            )->hint(fn (bool $state): string => match ($state) {
                false => __('lunarpanel::relationmanagers.channels.form.enabled.helper_text_false'),
                true => '',
            })->hintColor('danger')->live()->columnSpan(2),
            Forms\Components\Grid::make(2)->schema([
                Forms\Components\DateTimePicker::make('starts_at')->label(
                    __('lunarpanel::relationmanagers.channels.form.starts_at.label')
                )->helperText(
                    __('lunarpanel::relationmanagers.channels.form.starts_at.helper_text')
                ),
                Forms\Components\DateTimePicker::make('ends_at')->label(
                    __('lunarpanel::relationmanagers.channels.form.ends_at.label')
                )->helperText(
                    __('lunarpanel::relationmanagers.channels.form.ends_at.helper_text')
                ),
            ]),
            Forms\Components\Grid::make('Publish Settings')->statePath('sync_settings')->schema(function ($record) {
                // dd($record);
                if (!$record || !$record->integration) {
                    return [];
                }

                return $record->integration->handler_class::syncSchema();
            }),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->description(
                __('lunarpanel::relationmanagers.channels.table.description')
            )->paginated(false)
            ->headerActions([
                Tables\Actions\AttachAction::make()->form(fn (Tables\Actions\AttachAction $action): array => [
                    $action->recordSelectOptionsQuery(function ($query) {
                        if (Filament::getTenant()) {
                            return $query->where('tenant_id', Filament::getTenant()->id);
                        }
                        return $query; // No filter if no tenant is set
                    })->getRecordSelect(),
                    ...static::getFormInputs(),
                ])->recordTitle(function ($record) {
                    return $record->name;
                })->after(
                    fn () => sync_with_search(
                        $this->getOwnerRecord()
                    )
                )->preloadRecordSelect()
                    ->label(
                        __('lunarpanel::relationmanagers.channels.actions.attach.label')
                    ),
            ])
            ->columns([
                Tables\Columns\TextColumn::make('name')->label(
                    __('lunarpanel::relationmanagers.channels.table.name.label')
                ),
                Tables\Columns\IconColumn::make('enabled')->label(
                    __('lunarpanel::relationmanagers.channels.table.enabled.label')
                )
                    ->color(fn (bool $state): string => match ($state) {
                        true => 'success',
                        false => 'warning',
                    })->icon(fn (bool $state): string => match ($state) {
                        false => 'heroicon-o-x-circle',
                        true => 'heroicon-o-check-circle',
                    }),
                Tables\Columns\TextColumn::make('starts_at')->label(
                    __('lunarpanel::relationmanagers.channels.table.starts_at.label')
                )->dateTime(),
                Tables\Columns\TextColumn::make('ends_at')->label(
                    __('lunarpanel::relationmanagers.channels.table.ends_at.label')
                )->dateTime(),
                Tables\Columns\IconColumn::make('is_published')
                    ->label(__('lunarpanel::relationmanagers.channels.table.is_published.label'))
                    ->getStateUsing(fn ($record) => $this->getOwnerRecord()->isPublishedTo($record))
                    ->color(fn (bool $state): string => match ($state) {
                        true => 'success',
                        false => 'warning',
                    })
                    ->icon(fn (bool $state): string => match ($state) {
                        false => 'heroicon-o-x-circle',
                        true => 'heroicon-o-check-circle',
                    }),
            ])->actions([
                Action::make('publish')->button()->action(function(Channel $record){
                    try {
                        $this->ownerRecord->publish()->to($record);

                        Notification::make()
                            ->title('Product Synced')
                            ->success()
                            ->icon('heroicon-o-check-circle')
                            ->body('Your product has been successfully synced to the channel.')
                            ->persistent()
                            ->actions([
                                ActionsAction::make('view')
                                    ->button()
                                    ->url($this->getOwnerRecord()->publishedProductFor($record)?->pivot->preview_url)
                                    ->openUrlInNewTab(),
                            ])
                            ->send();
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Publishing Failed')
                            ->danger()
                            ->icon('heroicon-o-x-circle')
                            ->body('Could not publish to the shop. Please try again later.')
                            ->persistent()
                            ->send();
                        return;
                    }
                }),
                
                Action::make('view_in_shop')
                    ->label(__('lunarpanel::relationmanagers.channels.actions.view_in_shop.label'))
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (Channel $record) => $this->getOwnerRecord()->publishedProductFor($record)?->pivot->preview_url)
                    ->openUrlInNewTab()
                    ->visible(fn (Channel $record) => $this->getOwnerRecord()->isPublishedTo($record)),
                
                Tables\Actions\EditAction::make()->after(
                    fn () => ModelChannelsUpdated::dispatch(
                        $this->getOwnerRecord()
                    )
                ),
            ]);
    }
}
