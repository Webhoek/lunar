<?php

namespace Lunar\Admin\Support\Forms\Components;

use Filament\Forms\Components\CheckboxList;
use Lunar\Facades\ModelManifest;
use Lunar\Models\Attribute;
use Lunar\Models\AttributeGroup;
use Lunar\Models\Product;
use Lunar\Models\ProductType;

class AttributeSelector extends CheckboxList
{
    protected string $view = 'lunarpanel::forms.components.attribute-selector';

    protected ?string $attributableType = null;

    protected ?AttributeGroup $attributeGroup = null;

    public function withType($type)
    {
        $this->attributableType = $type;

        return $this;
    }

    public function attributeGroup(?AttributeGroup $group)
    {
        $this->attributeGroup = $group;

        return $this;
    }

    protected function setUp(): void
    {
        parent::setUp();

        if (!$this->attributeGroup) {
            $this->loadStateFromRelationships();
        }
    }

    public function relationship(string|\Closure|null $name = null, string|\Closure|null $titleAttribute = null, ?\Closure $modifyQueryUsing = null): static
    {
        if ($this->attributeGroup) {
            return $this;
        }

        parent::relationship($name, $titleAttribute, $modifyQueryUsing);

        $type = $this->attributableType;

        $this->saveRelationshipsUsing(static function (CheckboxList $component, ?array $state) use ($type) {
            // Get all current mapped attributes
            $existing = $component->getRelationship()->get();

            $actualClass = ModelManifest::guessModelClass($type);
            // Filter out any that match this attribute type but are not in the saved state.
            $attributes = $existing->reject(
                fn ($attribute) => ! in_array($attribute->id, $state ?? []) && $attribute->attribute_type == $type
            )->pluck('id')->unique()->merge($state)->toArray();

            $component->getRelationship()->sync($attributes);
        });

        return $this;
    }

    public function getAttributeGroups()
    {
        if ($this->attributeGroup) {
            return collect([$this->attributeGroup]);
        }

        $type = $this->getRelationship() ? get_class(
            $this->getRelationship()->getParent()
        ) : $this->attributableType;

        if ($type === ProductType::morphName()) {
            $type = Product::morphName();
        }

        if ($this->attributableType) {
            $type = $this->attributableType;
        }

        return AttributeGroup::whereAttributableType($type)->get();
    }

    public function getSelectedAttributes($groupId)
    {
        if ($this->attributeGroup) {
            return Attribute::where('attribute_group_id', $groupId)
                ->whereIn('id', $this->getState() ?? [])
                ->get();
        }

        return Attribute::where('attribute_group_id', $groupId)
            ->whereIn('id', $this->getState() ?? [])
            ->get();
    }

    public function getAttributes($groupId)
    {
        return Attribute::where('attribute_group_id', $groupId)->get();
    }

    public function getState(): mixed
    {
        if ($this->attributeGroup) {
            return parent::getState() ?? [];
        }

        return parent::getState();
    }
}
