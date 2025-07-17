<?php

namespace App\Livewire\Admin\Pages;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Category;
use Illuminate\Support\Str;
use Livewire\WithFileUploads;


class CategoryManagement extends Component
{
    use WithPagination;
    use WithFileUploads;

    public $image;
    public $image_url;


    public $search = '';
    public $showActive = '';
    public $parentFilter = '';

    public $name;
    public $slug;
    public $parent_id;
    public $is_active = true;
    public $selectedCategoryId;

    protected $queryString = ['search','showActive','parentFilter'];

    public function updatingSearch()      { $this->resetPage(); }
    public function updatingShowActive()  { $this->resetPage(); }
    public function updatingParentFilter(){ $this->resetPage(); }

    private function getDescendantIds(Category $cat)
    {
        $ids = collect([$cat->id]);
        foreach ($cat->children as $child) {
            $ids = $ids->merge($this->getDescendantIds($child));
        }
        return $ids->all();
    }

    protected function rules()
    {
        $uniqueSlug = $this->selectedCategoryId
            ? 'unique:categories,slug,'.$this->selectedCategoryId
            : 'unique:categories,slug';

        return [
            'name'      => 'required|string|min:3',
            'slug'      => 'nullable|string|'.$uniqueSlug,
            'parent_id' => 'nullable|exists:categories,id',
            'image' => 'nullable|image|max:10240',
            'is_active' => 'boolean',
        ];
    }

    public function resetForm()
    {
        $this->reset(['name','slug','parent_id','is_active','selectedCategoryId']);
    }

    public function openCreateModal()
    {
        $this->resetForm();
        $this->dispatch('showCategoryModal');
    }

    public function createCategory()
    {
        $this->validate();
        $path = $this->image ? $this->image->store('categories', 'public') : null;

        /** @var Category $c */
        $c = Category::create([
            'name'      => $this->name,
            'slug'      => $this->slug ?: Str::slug($this->name),
            'parent_id' => $this->parent_id,
            'is_active' => $this->is_active,
            'image'     => $path,
        ]);

        // No descendants yet on create.

        $this->dispatch('hideCategoryModal');
        session()->flash('success','Category created.');
    }

    public function openEditModal($id)
    {
        $c = Category::findOrFail($id);
        $this->selectedCategoryId = $c->id;
        $this->name      = $c->name;
        $this->slug      = $c->slug;
        $this->parent_id = $c->parent_id;
        $this->is_active = $c->is_active;
        $this->image_url = $c->image;
        $this->image = null;

        $this->dispatch('showCategoryModal');
    }

    public function updateCategory()
    {
        $this->validate();

        /** @var Category $c */
        $c = Category::findOrFail($this->selectedCategoryId);

        if ($this->image) {
            $path = $this->image->store('categories', 'public');
            $c->image = $path;
        }

        $c->update([
            'name'      => $this->name,
            'slug'      => $this->slug ?: Str::slug($this->name),
            'parent_id' => $this->parent_id,
            'is_active' => $this->is_active,
            'image'     => $c->image, 
        ]);

        // Cascade new status to all descendants
        $ids = $this->getDescendantIds($c);
        Category::whereIn('id', $ids)
                ->update(['is_active' => $this->is_active]);

        $this->dispatch('hideCategoryModal');
        session()->flash(
            'success',
            $this->is_active
              ? 'Category Updated.'
              : 'Category and its subcategories deactivated.'
        );

        $this->resetForm();
    }

    public function confirmDelete($id)
    {
        $this->selectedCategoryId = $id;
        $this->dispatch('showDeleteModal');
    }

    public function deleteCategory()
    {
        Category::findOrFail($this->selectedCategoryId)->delete();
        $this->dispatch('hideDeleteModal');
        session()->flash('success','Category deleted.');
    }

    public function render()
    {
        $query = Category::query()
            ->when($this->search, fn($q) =>
                $q->where('name','like',"%{$this->search}%"))
            ->when($this->showActive!=='', fn($q) =>
                $q->where('is_active',$this->showActive));

        if ($this->parentFilter) {
            if ($parent = Category::find($this->parentFilter)) {
                $ids = $this->getDescendantIds($parent);
                $query->whereIn('id', $ids);
            }
        }

        $categories   = $query->orderBy('order_column')->paginate(10);
        $allCategories= Category::all();

        return view('livewire.admin.pages.category-management', compact('categories','allCategories'))
             ->layout('components.layouts.admin');
    }
}
