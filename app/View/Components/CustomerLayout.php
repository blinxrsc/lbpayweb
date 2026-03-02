<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class CustomerLayout extends Component
{
    public bool $showNav;
    /**
     * Create a new component instance.
     */
    public function __construct($showNav = true)
    {
        $this->showNav = $showNav;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.customer-layout');
    }
}
