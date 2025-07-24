<?php

namespace App\Traits;

trait EnumHelper
{
    public function label(): string
    {
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
