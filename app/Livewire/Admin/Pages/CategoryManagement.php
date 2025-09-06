<?php

namespace App\Livewire\Admin\Pages;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use App\Models\Category;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Support\Collection;

class CategoryManagement extends Component
{
    use WithPagination, WithFileUploads;

    // Explorer state
    public ?int $currentParentId = null; // null = root ("All Categories")
    
    public string $sort = 'manual';      // manual | name | created | status
    public string $direction = 'asc';    // asc | desc
    public int $perPage = 24;

    // Filters
    public string $search = '';          // searches *current* folder
    public string $statusFilter = '';    // '', '1', '0'

    // Bulk selection
    /** @var array<int,bool> */
    public array $selected = [];         // [id => true]
    public bool $selectAll = false;

    // Create/Edit modal
    public ?int $selectedCategoryId = null;
    public ?string $name = null;
    public ?string $slug = null;
    public ?bool $is_active = true;
    public $image;                       // Livewire temp upload
    public ?string $image_url = null;
    public bool $cascadeStatus = false;  // when editing: optionally cascade

    // Move modal
    public array $selectedForMove = [];
    public ?int $moveDestinationId = null;
    public string $moveSearch = '';
    /** @var array<int,array{id:int,name:string,path:string}> */
    public array $moveOptions = [];

    // Modal keying
    public int $modalKey = 0;

    protected $queryString = [
        'currentParentId' => ['as' => 'folder', 'except' => null],
        'sort'            => ['except' => 'manual'],
        'direction'       => ['except' => 'asc'],
        'search'          => ['except' => ''],
        'statusFilter'    => ['except' => ''],
        'perPage'         => ['except' => 24],
    ];

    /* ===========================
     * Validation
     * ===========================
     */
    protected function rules()
    {
        $id = $this->selectedCategoryId ?: null;

        return [
            'name'      => ['required', 'string', 'min:3'],
            'slug'      => [
                'nullable', 'string',
                Rule::unique('categories', 'slug')->ignore($id),
            ],
            'is_active' => ['boolean'],
            'image'     => ['nullable', 'image', 'max:10240'],
        ];
    }

    /* ===========================
     * Lifecycle
     * ===========================
     */
    public function updatingSearch()    { $this->resetPage(); }
    public function updatingStatusFilter(){ $this->resetPage(); }
    public function updatingSort()      { $this->resetPage(); }
    public function updatingDirection() { $this->resetPage(); }
    public function updatingPerPage()   { $this->resetPage(); }
    public function updatedSelectAll($val)
    {
        if ($val) {
            foreach ($this->currentFolderQuery()->pluck('id') as $id) {
                $this->selected[$id] = true;
            }
        } else {
            $this->selected = [];
        }
    }

    /* ===========================
     * Explorer navigation
     * ===========================
     */
    public function enter(int $id): void
    {
        $this->currentParentId = $id;
        $this->clearSelection();
        $this->resetPage();
    }

    public function goTo(?int $id): void
    {
        $this->currentParentId = $id; // null = root
        $this->clearSelection();
        $this->resetPage();
    }

    public function goUp(): void
    {
        if (is_null($this->currentParentId)) return;
        $parent = Category::find($this->currentParentId);
        $this->currentParentId = $parent?->parent_id;
        $this->clearSelection();
        $this->resetPage();
    }

    public function breadcrumb(): array
    {
        $trail = [];
        $id = $this->currentParentId;
        while (!is_null($id)) {
            $c = Category::find($id);
            if (!$c) break;
            $trail[] = ['id' => $c->id, 'name' => $c->name];
            $id = $c->parent_id;
        }
        return array_reverse($trail);
    }

    /* ===========================
     * Queries
     * ===========================
     */
    private function currentFolderQuery()
    {
        $q = Category::query()
            ->where('parent_id', $this->currentParentId)
            ->when($this->search, fn($qq) =>
                $qq->where('name', 'like', '%' . $this->search . '%')
            )
            ->when($this->statusFilter !== '', fn($qq) =>
                $qq->where('is_active', (bool) $this->statusFilter)
            )
            ->withCount('children');

        // Sorting
        switch ($this->sort) {
            case 'name':
                $q->orderBy('name', $this->direction);
                break;
            case 'created':
                $q->orderBy('created_at', $this->direction);
                break;
            case 'status':
                $q->orderBy('is_active', $this->direction)->orderBy('name', 'asc');
                break;
            case 'manual':
            default:
                $q->orderBy('order_column')->orderBy('name', 'asc');
        }

        return $q;
    }

    /* ===========================
     * Create
     * ===========================
     */
    public function openCreateModal(): void
    {
        $this->resetForm();
        $this->modalKey++;
        $this->dispatch('showCategoryModal');
    }

    public function createCategory(): void
    {
        $this->validate();

        $path = $this->image ? $this->image->store('categories', 'public') : null;

        Category::create([
            'name'      => $this->name,
            'slug'      => $this->slug ?: Str::slug($this->name),
            'parent_id' => $this->currentParentId,
            'is_active' => (bool) $this->is_active,
            'image'     => $path,
        ]);

        $this->dispatch('hideCategoryModal');
        session()->flash('success', 'Category created in this folder.');
        $this->resetForm();
        $this->resetPage();
    }

    /* ===========================
     * Edit
     * ===========================
     */
    public function openEditModal(int $id): void
    {
        $c = Category::findOrFail($id);
        $this->selectedCategoryId = $c->id;
        $this->name               = $c->name;
        $this->slug               = $c->slug;
        $this->is_active          = (bool)$c->is_active;
        $this->image_url          = $c->image;
        $this->image              = null;
        $this->cascadeStatus      = false;

        $this->modalKey++;
        $this->dispatch('showCategoryModal');
    }

    public function updateCategory(): void
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

        if ($this->cascadeStatus) {
            $ids = $this->getDescendantIds($c);
            if (!empty($ids)) {
                Category::whereIn('id', $ids)->update(['is_active' => $this->is_active]);
            }
        }

        $this->dispatch('hideCategoryModal');
        session()->flash('success', 'Category updated' . ($this->cascadeStatus ? ' (status cascaded).' : '.'));

        $this->resetForm();
    }

    /* ===========================
     * Inline toggles
     * ===========================
     */
    public function toggleActive(int $id): void
    {
        $c = Category::findOrFail($id);
        $c->is_active = !$c->is_active;
        $c->save();
    }

    /* ===========================
     * Delete (single & bulk)
     * ===========================
     */
    public function confirmDelete(int $id): void
    {
        $this->selectedCategoryId = $id;
        $this->dispatch('showDeleteModal');
    }

    public function deleteCategory(): void
    {
        $c = Category::findOrFail($this->selectedCategoryId);
        $this->deleteWithDescendants($c);
        $this->dispatch('hideDeleteModal');
        session()->flash('success', 'Category (and its subfolders) deleted.');
        $this->resetForm();
        $this->resetPage();
    }

    public function bulkDelete(): void
    {
        $ids = $this->selectedIds();
        if (empty($ids)) return;

        // Delete each with descendants safely
        foreach ($ids as $id) {
            if ($cat = Category::find($id)) {
                $this->deleteWithDescendants($cat);
            }
        }
        $this->clearSelection();
        session()->flash('success', 'Selected categories deleted.');
        $this->resetPage();
    }

    /* ===========================
     * Bulk activate/deactivate
     * ===========================
     */
    public function bulkSetActive(bool $active): void
    {
        $ids = $this->selectedIds();
        if (empty($ids)) return;

        Category::whereIn('id', $ids)->update(['is_active' => $active]);
        $this->clearSelection();
        session()->flash('success', 'Selected categories updated.');
    }

    /* ===========================
     * Move (bulk)
     * ===========================
     */
    public function openMoveModal(): void
    {
        $this->selectedForMove = $this->selectedIds();
        if (empty($this->selectedForMove)) return;

        $this->moveDestinationId = null;
        $this->moveSearch = '';
        $this->moveOptions = [];
        $this->dispatch('showMoveModal');
    }

    public function updatedMoveSearch(): void
    {
        $this->moveOptions = $this->searchMoveOptions($this->moveSearch);
    }

    public function moveSelected(): void
    {
        if (empty($this->selectedForMove)) return;

        $dest = $this->moveDestinationId; // may be null (root)
        // Guard: cannot move into own descendant
        foreach ($this->selectedForMove as $id) {
            if (!is_null($dest) && $this->isDescendant($dest, $id)) {
                session()->flash('error', 'Cannot move a category inside one of its descendants.');
                return;
            }
        }

        Category::whereIn('id', $this->selectedForMove)->update(['parent_id' => $dest]);

        $this->dispatch('hideMoveModal');
        $this->clearSelection();
        session()->flash('success', 'Selected categories moved.');
        $this->resetPage();
    }

    /* ===========================
     * Helpers
     * ===========================
     */
    private function resetForm(): void
    {
        $this->reset([
            'selectedCategoryId', 'name', 'slug', 'is_active',
            'image', 'image_url', 'cascadeStatus',
        ]);
        $this->is_active = true;
    }

    private function clearSelection(): void
    {
        $this->selected = [];
        $this->selectAll = false;
    }

    /** @return int[] */
    private function selectedIds(): array
    {
        return array_keys(array_filter($this->selected));
    }

    /** @return int[] */
    private function getDescendantIds(Category $cat): array
    {
        $ids = [];
        foreach ($cat->children as $child) {
            $ids[] = $child->id;
            $ids = array_merge($ids, $this->getDescendantIds($child));
        }
        return $ids;
    }

    private function deleteWithDescendants(Category $cat): void
    {
        foreach ($cat->children as $child) {
            $this->deleteWithDescendants($child);
        }
        $cat->delete();
    }

    private function isDescendant(int $candidateId, int $ancestorId): bool
    {
        $cur = Category::find($candidateId);
        while ($cur) {
            if ($cur->id === $ancestorId) return true;
            $cur = $cur->parent;
        }
        return false;
    }

    /** @return array<int,array{id:int,name:string,path:string}> */
    private function searchMoveOptions(string $term): array
    {
        $term = trim($term);
        $excluded = $this->selectedForMove ?? [];
        $results = Category::query()
            ->when($term, fn($q) => $q->where('name', 'like', '%' . $term . '%'))
            ->limit(50)
            ->get();

        $items = [];
        foreach ($results as $cat) {
            if (in_array($cat->id, $excluded, true)) continue;
            // also exclude descendants of selected
            $skip = false;
            foreach ($excluded as $selId) {
                if ($this->isDescendant($cat->id, $selId)) { $skip = true; break; }
            }
            if ($skip) continue;

            $items[] = [
                'id'   => $cat->id,
                'name' => $cat->name,
                'path' => $this->buildPathString($cat),
            ];
        }
        return $items;
    }

    private function buildPathString(Category $cat): string
    {
        $nodes = [];
        $cur = $cat;
        while ($cur) {
            $nodes[] = $cur->name;
            $cur = $cur->parent;
        }
        return implode(' / ', array_reverse($nodes));
    }

    /* ===========================
     * Render
     * ===========================
     */
    public function render()
    {
        $categories = $this->currentFolderQuery()->paginate($this->perPage);

        return view('livewire.admin.pages.category-management', [
            'categories' => $categories,
            'breadcrumb' => $this->breadcrumb(),
            'currentParent' => $this->currentParentId ? Category::find($this->currentParentId) : null,
        ])->layout('components.layouts.admin');
    }
}
