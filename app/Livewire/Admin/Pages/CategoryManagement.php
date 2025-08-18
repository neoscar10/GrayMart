<?php

namespace App\Livewire\Admin\Pages;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use App\Models\Category;
use Illuminate\Support\Str;

class CategoryManagement extends Component
{
    use WithPagination, WithFileUploads;

    // Root listing (paginate roots only)
    public string $search = '';
    public string $showActive = ''; // '', '1', '0'
    public int $perPage = 15;

    // Tree state for ALL nodes (limitless depth)
    /** @var array<int,bool> */
    public array $expanded = []; // [categoryId => true]
    /** @var array<int,\Illuminate\Support\Collection> */
    public array $childrenCache = []; // [parentId => Collection<Category>]

    // Create/Edit modal (for any node)
    public ?int $selectedCategoryId = null;
    public ?string $name = null;
    public ?string $slug = null;
    public ?bool $is_active = true;
    public $image; // Livewire temp upload
    public ?string $image_url = null;

    // Inline "Add Subcategory" (single slot at a time)
    public ?int $addingChildFor = null;
    public ?string $child_name = null;
    public ?string $child_slug = null;
    public ?bool $child_is_active = true;
    public $child_image;

    public int $modalKey = 0;




    protected $queryString = [
        'search' => ['except' => ''],
        'showActive' => ['except' => ''],
    ];

    // ---------- Helpers ----------
    private function getDescendantIds(Category $cat)
    {
        $ids = collect([$cat->id]);
        foreach ($cat->children as $child) {
            $ids = $ids->merge($this->getDescendantIds($child));
        }
        return $ids->all();
    }

    // ---------- Validation ----------
    protected function rules()
    {
        $uniqueSlug = $this->selectedCategoryId
            ? 'unique:categories,slug,' . $this->selectedCategoryId
            : 'unique:categories,slug';

        return [
            'name'      => 'required|string|min:3',
            'slug'      => 'nullable|string|' . $uniqueSlug,
            'is_active' => 'boolean',
            'image'     => 'nullable|image|max:10240',
        ];
    }

    protected function childRules()
    {
        return [
            'child_name'      => 'required|string|min:3',
            'child_slug'      => 'nullable|string|unique:categories,slug',
            'child_is_active' => 'boolean',
            'child_image'     => 'nullable|image|max:10240',
        ];
    }

    // ---------- Root Create Modal ----------
    public function openCreateModal()
{
    $this->resetRootForm();
    $this->modalKey++;                
    $this->dispatch('showCategoryModal');
}

    public function createCategory()
    {
        $this->validate();

        $path = $this->image ? $this->image->store('categories', 'public') : null;

        Category::create([
            'name'      => $this->name,
            'slug'      => $this->slug ?: Str::slug($this->name),
            'parent_id' => null,
            'is_active' => (bool) $this->is_active,
            'image'     => $path,
        ]);

        $this->dispatch('hideCategoryModal');
        session()->flash('success', 'Root category created.');
        $this->resetRootForm();
        $this->resetPage();
    }

    // ---------- Edit Modal (any node) ----------
    public function openEditModal(int $id)
    {
        $c = Category::findOrFail($id);
        $this->selectedCategoryId = $c->id;
        $this->name       = $c->name;
        $this->slug       = $c->slug;
        $this->is_active  = (bool)$c->is_active;
        $this->image_url  = $c->image;
        $this->image      = null;

        $this->modalKey++;                // <— force fresh modal DOM
        $this->dispatch('showCategoryModal');
    }

    public function updateCategory()
    {
        $this->validate();

        /** @var Category $c */
        $c = Category::findOrFail($this->selectedCategoryId);

        if ($this->image) {
            $c->image = $this->image->store('categories', 'public');
        }

        $c->name      = $this->name;
        $c->slug      = $this->slug ?: Str::slug($this->name);
        $c->is_active = (bool) $this->is_active;
        $c->save();

        // Cascade active flag to descendants
        $ids = $this->getDescendantIds($c);
        Category::whereIn('id', $ids)->update(['is_active' => $this->is_active]);

        $this->dispatch('hideCategoryModal');
        session()->flash('success', 'Category updated (status cascaded).');

        // Refresh the parent of the edited node if it's cached/expanded
        $parentId = $c->parent_id ?? null;
        if ($parentId && isset($this->childrenCache[$parentId])) {
            $this->reloadChildren($parentId);
        }

        $this->resetRootForm();
    }

    // ---------- Delete ----------
    public function confirmDelete(int $id)
    {
        $this->selectedCategoryId = $id;
        $this->dispatch('showDeleteModal');
    }

    public function deleteCategory()
    {
        $c = Category::findOrFail($this->selectedCategoryId);
        $parentId = $c->parent_id;
        $c->delete();

        $this->dispatch('hideDeleteModal');
        session()->flash('success', 'Category deleted.');

        if ($parentId) {
            $this->reloadChildren($parentId);
        } else {
            $this->resetPage(); // root deleted
        }
    }

    // ---------- Expand / Collapse (any node) ----------
    public function toggleExpand(int $categoryId)
    {
        if (empty($this->expanded[$categoryId])) {
            $this->expanded[$categoryId] = true;
            $this->loadChildren($categoryId);
        } else {
            $this->expanded[$categoryId] = false;
        }
    }

    public function loadChildren(int $parentId)
    {
        $this->childrenCache[$parentId] = Category::where('parent_id', $parentId)
            ->orderBy('order_column')
            ->get();
    }

    private function reloadChildren(int $parentId): void
    {
        if (isset($this->childrenCache[$parentId])) {
            $this->childrenCache[$parentId] = Category::where('parent_id', $parentId)
                ->orderBy('order_column')
                ->get();
        }
    }

    // ---------- Inline Add Subcategory ----------
    public function startAddChild(int $parentId)
    {
        $this->resetChildForm();
        $this->addingChildFor = $parentId;

        if (empty($this->expanded[$parentId])) {
            $this->expanded[$parentId] = true;
            $this->loadChildren($parentId);
        }
    }

    public function createChild()
    {
        $this->validate($this->childRules());

        $parentId = $this->addingChildFor;
        if (!$parentId) return;

        $path = $this->child_image ? $this->child_image->store('categories', 'public') : null;

        Category::create([
            'name'      => $this->child_name,
            'slug'      => $this->child_slug ?: Str::slug($this->child_name),
            'parent_id' => $parentId,
            'is_active' => (bool) $this->child_is_active,
            'image'     => $path,
        ]);

        $this->reloadChildren($parentId);
        $this->resetChildForm();
        session()->flash('success', 'Subcategory added.');
    }

    // ---------- Resets ----------
    private function resetRootForm(): void
    {
        $this->reset(['selectedCategoryId','name','slug','is_active','image','image_url']);
        $this->is_active = true;
    }

    private function resetChildForm(): void
    {
        $this->reset(['addingChildFor','child_name','child_slug','child_is_active','child_image']);
        $this->child_is_active = true;
    }

    // ---------- Hooks ----------
    public function updatingSearch() { $this->resetPage(); }
    public function updatingShowActive() { $this->resetPage(); }

    // ---------- Render ----------
    public function render()
    {
        $roots = Category::whereNull('parent_id')
            ->when($this->search, fn($q) => $q->where('name', 'like', '%' . $this->search . '%'))
            ->when($this->showActive !== '', fn($q) => $q->where('is_active', (bool) $this->showActive))
            ->orderBy('order_column')
            ->paginate($this->perPage);

        return view('livewire.admin.pages.category-management', [
            'roots' => $roots,
        ])->layout('components.layouts.admin');
    }
}
