<?php

namespace Lunar\Admin\Support\FieldTypes;

use Filament\Forms\Components\RichEditor as FilamentRichEditor;
use Lunar\Admin\Support\Synthesizers\RichEditorSynth;
use Lunar\Models\Attribute;

class RichEditor extends BaseFieldType
{
    protected static string $synthesizer = RichEditorSynth::class;

    public static function getFilamentComponent(Attribute $attribute): FilamentRichEditor
    {
        $component = FilamentRichEditor::make($attribute->handle)
            ->toolbarButtons([
                'attachFiles',
                'blockquote',
                'bold',
                'bulletList',
                'codeBlock',
                'h2',
                'h3',
                'italic',
                'link',
                'orderedList',
                'redo',
                'strike',
                'underline',
                'undo',
            ])
            ->fileAttachmentsDisk('public')
            ->fileAttachmentsDirectory('attachments')
            ->fileAttachmentsVisibility('public');

        if (filled($attribute->validation_rules)) {
            $component->rules($attribute->validation_rules);
        }

        if ($attribute->required) {
            $component->required();
        }

        if ($description = $attribute->translate('description')) {
            $component->helperText($description);
        }

        return $component;
    }

    public static function getConfigurationFields(): array
    {
        return [
            \Filament\Forms\Components\KeyValue::make('toolbar')
                ->label('Toolbar Buttons')
                ->keyLabel('Button')
                ->valueLabel('Enabled')
                ->default([
                    'attachFiles' => true,
                    'blockquote' => true,
                    'bold' => true,
                    'bulletList' => true,
                    'codeBlock' => true,
                    'h2' => true,
                    'h3' => true,
                    'italic' => true,
                    'link' => true,
                    'orderedList' => true,
                    'redo' => true,
                    'strike' => true,
                    'underline' => true,
                    'undo' => true,
                ]),
            \Filament\Forms\Components\TextInput::make('fileAttachmentsDisk')
                ->label('File Attachments Disk')
                ->default('public'),
            \Filament\Forms\Components\TextInput::make('fileAttachmentsDirectory')
                ->label('File Attachments Directory')
                ->default('attachments'),
            \Filament\Forms\Components\Select::make('fileAttachmentsVisibility')
                ->label('File Attachments Visibility')
                ->options([
                    'public' => 'Public',
                    'private' => 'Private',
                ])
                ->default('public'),
        ];
    }
} 