<?php

namespace Lunar\Contracts;

use Filament\Forms\Form;

interface FulfillmentHandler
{
    /**
     * Get the form schema for the fulfillment handler
     */
    public function getFormSchema(): array;
} 