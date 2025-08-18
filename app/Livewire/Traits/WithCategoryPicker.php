<?php

namespace App\Livewire\Traits;

use App\Models\Category;

trait WithCategoryPicker
{
    // === Category Picker state ===
    public string $categoryQuery = '';
    public bool $showCategoryMenu = false;
    public int $categoryHighlight = 0;

    /** @var array<int,array{id:int,name:string,depth:int,full_path:string,is_active:bool}> */
    public array $flatCategories = [];  // flattened tree for fast search

    /**
     * Build a flattened category tree once.
     * Call this in mount(), openCreate(), openEdit(), or right before you show the modal.
     */
    public function buildFlatCategories(): void
    {
        $rows = Category::select('id','name','parent_id','is_active')
            ->orderBy('order_column')
            ->get();

        // Group by parent
        $byParent = [];
        foreach ($rows as $r) {
            $byParent[$r->parent_id ?? 0][] = $r;
        }

        // DFS → flat with depth + full_path
        $out = [];
        $walk = function($parentId, $prefix, $depth) use (&$walk, &$out, &$byParent) {
            foreach ($byParent[$parentId] ?? [] as $node) {
                $full = $prefix ? ($prefix.' / '.$node->name) : $node->name;
                $out[] = [
                    'id'        => $node->id,
                    'name'      => $node->name,
                    'depth'     => $depth,
                    'full_path' => $full,
                    'is_active' => (bool)$node->is_active,
                ];
                $walk($node->id, $full, $depth + 1);
            }
        };
        $walk(0, '', 0);

        $this->flatCategories = $out;
    }

    /** Computed results for the dropdown (limit for speed) */
    public function getCategoryResultsProperty(): array
    {
        $q = trim(mb_strtolower($this->categoryQuery));
        if ($q === '') {
            $roots = array_filter($this->flatCategories, fn($c) => $c['depth'] === 0);
            return array_slice($roots, 0, 30);
        }

        $tokens = preg_split('/\s+/', $q, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $res = [];
        foreach ($this->flatCategories as $c) {
            $hay = mb_strtolower($c['full_path']);
            $ok = true;
            foreach ($tokens as $t) {
                if (mb_strpos($hay, $t) === false) { $ok = false; break; }
            }
            if ($ok) $res[] = $c;
            if (count($res) >= 60) break; // cap
        }
        return $res;
    }

    public function openCategoryPicker(): void
    {
        if (empty($this->flatCategories)) {
            $this->buildFlatCategories();
        }

        // Seed with current selection (if any)
        if (!empty($this->category_id)) {
            $sel = collect($this->flatCategories)->firstWhere('id', (int)$this->category_id);
            $this->categoryQuery = $sel['full_path'] ?? '';
        } else {
            $this->categoryQuery = '';
        }

        $this->categoryHighlight = 0;
        $this->showCategoryMenu = true;
    }

    public function closeCategoryPicker(): void
    {
        $this->showCategoryMenu = false;
    }

    public function updatedCategoryQuery(): void
    {
        $this->categoryHighlight = 0;
        if (!$this->showCategoryMenu) $this->showCategoryMenu = true;
    }

    public function moveCategoryHighlight(int $delta): void
    {
        $total = count($this->categoryResults);
        if ($total === 0) { $this->categoryHighlight = 0; return; }
        $this->categoryHighlight = max(0, min($total - 1, $this->categoryHighlight + $delta));
    }

    public function chooseHighlightedCategory(): void
    {
        $list = $this->categoryResults;
        if (!isset($list[$this->categoryHighlight])) return;
        $this->selectCategory((int)$list[$this->categoryHighlight]['id']);
    }

    public function selectCategory(int $id): void
    {
        $sel = collect($this->flatCategories)->firstWhere('id', $id);
        if ($sel) {
            // IMPORTANT: This assumes your product component uses `public ?int $category_id`
            $this->category_id      = $sel['id'];
            $this->categoryQuery    = $sel['full_path'];
            $this->showCategoryMenu = false;
        }
    }

    public function clearCategory(): void
    {
        $this->category_id   = null;
        $this->categoryQuery = '';
    }
}

