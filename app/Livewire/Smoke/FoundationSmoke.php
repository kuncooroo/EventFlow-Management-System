<?php

namespace App\Livewire\Smoke;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.public')]
#[Title('Livewire smoke')]
class FoundationSmoke extends Component
{
    public bool $alpineReady = false;

    public function markAlpineReady(): void
    {
        $this->alpineReady = true;
    }

    public function render()
    {
        return view('livewire.smoke.foundation-smoke');
    }
}
