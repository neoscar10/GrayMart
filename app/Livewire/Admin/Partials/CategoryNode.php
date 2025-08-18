<?php

namespace App\Livewire\Admin\Partials;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\Category;
use Illuminate\Support\Str;

class CategoryNode extends Component
{
    use WithFileUploads;

    public Category $node;
    public int $level = 1;

    public bool $expanded = false;

    /** @var \Illuminate\Support\Collection */
    public $children;

    // Inline add child form (for this node)
    public ?int $addingChildFor = null;
    public ?string $child_name = null;
    public ?string $child_slug = null;
    public ?bool $child_is_active = true;
    public $child_image;

    protected $listeners = [
        'refreshNode' => 'refreshSelf', // parent can tell this node to refresh itself
    ];

    public function mount(Category $node, int $level = 1)
    {
        $this->node = $node;
        $this->level = $level;
        $this->children = collect();
    }

    public function toggleExpand()
    {
        $this->expanded = !$this->expanded;
        if ($this->expanded) {
            $this->loadChildren();
        }
    }

    public function loadChildren()
    {
        $this->children = Category::where('parent_id', $this->node->id)
            ->orderBy('order_column')
            ->get();
    }

    public function startAddChild()
    {
        if (!$this->expanded) {
            $this->expanded = true;
            $this->loadChildren();
        }
        $this->addingChildFor = $this->node->id;
        $this->resetChildForm(false);
    }

    public function createChild()
    {
        $this->validate([
            'child_name'      => 'required|string|min:3',
            'child_slug'      => 'nullable|string|unique:categories,slug',
            'child_is_active' => 'boolean',
            'child_image'     => 'nullable|image|max:10240',
        ]);

        $path = $this->child_image ? $this->child_image->store('categories', 'public') : null;

        Category::create([
            'name'      => $this->child_name,
            'slug'      => $this->child_slug ?: Str::slug($this->child_name),
            'parent_id' => $this->node->id,
            'is_active' => (bool)$this->child_is_active,
            'image'     => $path,
        ]);

        $this->loadChildren();
        $this->resetChildForm(true);

        // Let parent refresh its cached branch if it keeps one
        $this->dispatch('branchChanged', parentId: $this->node->id);

        session()->flash('success', 'Subcategory added.');
    }

    public function cancelAddChild()
    {
        $this->resetChildForm(true);
    }

    private function resetChildForm(bool $clear = true)
    {
        if ($clear) {
            $this->addingChildFor = null;
        }
        $this->child_name = null;
        $this->child_slug = null;
        $this->child_is_active = true;
        $this->child_image = null;
    }

    // Forward edit/delete to parent modal handlers
    public function askEdit()
    {
        $this->dispatch('openEditModal', id: $this->node->id);
    }

    public function askDelete()
    {
        $this->dispatch('confirmDelete', id: $this->node->id);
    }

    public function refreshSelf(int $nodeId = null)
    {
        if (is_null($nodeId) || $nodeId === $this->node->id) {
            $this->node->refresh();
            if ($this->expanded) {
                $this->loadChildren();
            }
        }
    }

    public function render()
    {
        return view('livewire.admin.partials.category-node');
    }
}
