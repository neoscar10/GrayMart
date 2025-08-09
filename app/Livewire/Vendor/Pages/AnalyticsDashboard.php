<?php

namespace App\Livewire\Vendor\Pages;

use Livewire\Component;

class AnalyticsDashboard extends Component
{
    public function render()
    {
        return view('livewire.vendor.pages.analytics-dashboard')->layout('components.layouts.vendor');
    }
}
