<?php

namespace App\Traits;

trait EnumHelper
{
    public function label(): string
    {
        // Use the name() method if it exists, otherwise fallback to processing the enum case name
        if (method_exists($this, 'name')) {
            return $this->name();
        }
        
        return str($this->name)
            ->title()
            ->headline();
    }

    public function toArray(): array
    {
        return [
            'value' => $this->value,
            'label' => $this->label(),
            'color' => method_exists($this, 'color') ? $this->color() : null,
            'icon' => method_exists($this, 'icon') ? $this->icon() : null,
        ];
    }
}
