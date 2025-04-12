<?php

namespace Lunar\Admin\Support\Synthesizers;

use Lunar\FieldTypes\RichEditor;

class RichEditorSynth extends AbstractFieldSynth
{
    public static $key = 'lunar_rich_editor_field';

    protected static $targetClass = RichEditor::class;

    public function dehydrate($target)
    {
        $value = $target->getValue();
        return [$value === null ? '' : $value, []];
    }

    public function hydrate($value)
    {
        $instance = new static::$targetClass;
        $instance->setValue($value === '' ? null : $value);
        return $instance;
    }
} 