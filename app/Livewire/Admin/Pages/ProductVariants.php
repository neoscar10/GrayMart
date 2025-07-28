<?php

namespace App\Livewire\Admin\Pages;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\VariantAttribute;
use App\Models\VariantAttributeValue;
use Illuminate\Validation\Rule;

class ProductVariants extends Component
{
    use WithPagination;

    // Attribute form state
    public $attributeId    = null;
    public $attributeName  = '';

    // Value form state
    public $valueId        = null;
    public $valueName      = '';

    // Confirm modal state
    public $confirmType    = '';
    public $confirmId      = null;

    protected $listeners = [
        'deleteConfirmed'
    ];

    public function render()
    {
        $attributes = VariantAttribute::orderBy('name')->paginate(10);
        $values = $this->attributeId
            ? VariantAttributeValue::where('attribute_id', $this->attributeId)
                ->orderBy('value')
                ->get()
            : collect();

        return view('livewire.admin.pages.product-variants', compact('attributes','values'))
            ->layout('components.layouts.admin');
    }

    // -- ATTRIBUTE CRUD --

    public function openCreateAttributeModal()
    {
        $this->resetAttributeForm();
        $this->dispatch('show-modal', 'attributeModal');
    }

    public function openEditAttributeModal(int $id)
    {
        $attr = VariantAttribute::findOrFail($id);
        $this->attributeId   = $attr->id;
        $this->attributeName = $attr->name;
        $this->dispatch('show-modal', 'attributeModal');
    }

    public function saveAttribute()
    {
        $this->validate([
            'attributeName' => [
                'required',
                'string',
                Rule::unique('variant_attributes','name')
                    ->ignore($this->attributeId),
            ],
        ]);

        if ($this->attributeId) {
            VariantAttribute::find($this->attributeId)
                ->update(['name' => $this->attributeName]);
            session()->flash('success','Attribute updated.');
        } else {
            VariantAttribute::create(['name' => $this->attributeName]);
            session()->flash('success','Attribute created.');
        }

        $this->dispatch('hide-modal', 'attributeModal');
        $this->resetAttributeForm();
    }

    public function confirmDeleteAttribute(int $id)
    {
        $this->confirmType = 'attribute';
        $this->confirmId   = $id;
        $this->dispatch('show-modal', 'confirmModal');
    }

    // -- VALUE CRUD --

    public function selectAttribute(int $id)
    {
        $this->attributeId = $id;
        $this->resetValueForm();
    }

    public function openCreateValueModal()
    {
        $this->resetValueForm();
        $this->dispatch('show-modal', 'valueModal');
    }

    public function openEditValueModal(int $id)
    {
        $val = VariantAttributeValue::findOrFail($id);
        $this->valueId   = $val->id;
        $this->valueName = $val->value;
        $this->dispatch('show-modal', 'valueModal');
    }

    public function saveValue()
    {
        $this->validate([
            'valueName' => [
                'required',
                'string',
                Rule::unique('variant_attribute_values','value')
                    ->ignore($this->valueId)
                    ->where('attribute_id',$this->attributeId),
            ],
        ]);

        if ($this->valueId) {
            VariantAttributeValue::find($this->valueId)
                ->update(['value' => $this->valueName]);
            session()->flash('success','Value updated.');
        } else {
            VariantAttributeValue::create([
                'attribute_id' => $this->attributeId,
                'value'        => $this->valueName,
            ]);
            session()->flash('success','Value created.');
        }

        $this->dispatch('hide-modal','valueModal');
        $this->resetValueForm();
    }

    public function confirmDeleteValue(int $id)
    {
        $this->confirmType = 'value';
        $this->confirmId   = $id;
        $this->dispatch('show-modal','confirmModal');
    }

    // -- DELETE HANDLER --

    public function deleteConfirmed()
    {
        if ($this->confirmType === 'attribute') {
            VariantAttribute::destroy($this->confirmId);
            if ($this->attributeId === $this->confirmId) {
                $this->attributeId = null;
            }
            session()->flash('success','Attribute deleted.');
        } else {
            VariantAttributeValue::destroy($this->confirmId);
            session()->flash('success','Value deleted.');
        }

        $this->confirmType = '';
        $this->confirmId   = null;
        $this->dispatch('hide-modal','confirmModal');
    }

    // -- RESET HELPERS --

    protected function resetAttributeForm()
    {
        $this->reset(['attributeId','attributeName']);
        $this->resetValidation(['attributeName']);
    }

    protected function resetValueForm()
    {
        $this->reset(['valueId','valueName']);
        $this->resetValidation(['valueName']);
    }
}
