<div class="flex items-center gap-2">
    <span>{{ $supplierOrder->id }}</span>
    <x-filament::button
        wire:click="viewSupplierOrder('{{ $supplierOrder->id }}')"
        size="sm"
        color="gray"
    >
        View Order
    </x-filament::button>
</div> 