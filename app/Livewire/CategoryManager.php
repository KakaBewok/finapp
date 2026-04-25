<?php

namespace App\Livewire;

use App\Models\Category;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class CategoryManager extends Component
{
    public bool $showModal = false;
    public bool $showDeleteModal = false;
    public ?int $editingId = null;
    public ?int $deletingId = null;

    public string $name = '';
    public string $icon = '📦';
    public string $color = '#6366f1';
    public string $type = 'expense';

    protected function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'icon' => 'required|string|max:10',
            'color' => 'required|string|max:7',
            'type' => 'required|in:income,expense',
        ];
    }

    public function openCreateModal(): void
    {
        $this->resetForm();
        $this->showModal = true;
    }

    public function openEditModal(int $id): void
    {
        $category = Category::forUser(Auth::id())->findOrFail($id);

        // Don't allow editing system categories
        if ($category->user_id === null) {
            Flux::toast(text: 'Kategori bawaan tidak dapat diedit.', variant: 'warning');
            return;
        }

        $this->editingId = $category->id;
        $this->name = $category->name;
        $this->icon = $category->icon;
        $this->color = $category->color;
        $this->type = $category->type;

        $this->showModal = true;
    }

    public function save(): void
    {
        $this->validate();

        $data = [
            'user_id' => Auth::id(),
            'name' => $this->name,
            'icon' => $this->icon,
            'color' => $this->color,
            'type' => $this->type,
        ];

        if ($this->editingId) {
            $category = Category::where('user_id', Auth::id())->findOrFail($this->editingId);
            $category->update($data);
        } else {
            Category::create($data);
        }

        $this->showModal = false;
        $this->resetForm();

        Flux::toast(
            text: $this->editingId ? 'Kategori berhasil diperbarui.' : 'Kategori berhasil ditambahkan.',
            variant: 'success',
        );
    }

    public function confirmDelete(int $id): void
    {
        $category = Category::forUser(Auth::id())->findOrFail($id);

        if ($category->user_id === null) {
            Flux::toast(text: 'Kategori bawaan tidak dapat dihapus.', variant: 'warning');
            return;
        }

        $this->deletingId = $id;
        $this->showDeleteModal = true;
    }

    public function delete(): void
    {
        Category::where('user_id', Auth::id())->where('id', $this->deletingId)->delete();

        $this->showDeleteModal = false;
        $this->deletingId = null;

        Flux::toast(text: 'Kategori berhasil dihapus.', variant: 'success');
    }

    protected function resetForm(): void
    {
        $this->editingId = null;
        $this->name = '';
        $this->icon = '📦';
        $this->color = '#6366f1';
        $this->type = 'expense';
        $this->resetValidation();
    }

    public function render()
    {
        $categories = Category::forUser(Auth::id())
            ->orderByRaw('user_id IS NULL DESC')
            ->orderBy('type')
            ->orderBy('name')
            ->get();

        return view('livewire.category-manager', [
            'categories' => $categories,
        ])->layout('layouts.app', ['title' => __('Kategori')]);
    }
}
