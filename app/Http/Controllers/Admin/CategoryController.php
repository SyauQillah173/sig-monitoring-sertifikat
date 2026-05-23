<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCategoryRequest;
use App\Http\Requests\Admin\UpdateCategoryRequest;
use App\Models\Category;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Throwable;

class CategoryController extends Controller
{
    public function index(): View
    {
        return view('admin.master-data.categories.index', [
            'categories' => Category::query()
                ->orderBy('name')
                ->paginate(10),
        ]);
    }

    public function create(): View
    {
        return view('admin.master-data.categories.create', [
            'category' => new Category,
        ]);
    }

    public function store(StoreCategoryRequest $request): RedirectResponse
    {
        try {
            Category::query()->create($request->validated());

            return redirect()
                ->route('admin.categories.index')
                ->with('success', 'Kategori produk berhasil ditambahkan.');
        } catch (Throwable $throwable) {
            report($throwable);

            return back()
                ->withInput()
                ->with('error', 'Kategori produk gagal disimpan. Silakan coba lagi.');
        }
    }

    public function edit(Category $category): View
    {
        return view('admin.master-data.categories.edit', [
            'category' => $category,
        ]);
    }

    public function update(UpdateCategoryRequest $request, Category $category): RedirectResponse
    {
        try {
            $category->update($request->validated());

            return redirect()
                ->route('admin.categories.index')
                ->with('success', 'Kategori produk berhasil diperbarui.');
        } catch (Throwable $throwable) {
            report($throwable);

            return back()
                ->withInput()
                ->with('error', 'Kategori produk gagal diperbarui. Silakan coba lagi.');
        }
    }

    public function destroy(Category $category): RedirectResponse
    {
        try {
            $category->delete();

            return redirect()
                ->route('admin.categories.index')
                ->with('success', 'Kategori produk berhasil dihapus.');
        } catch (Throwable $throwable) {
            report($throwable);

            return back()->with('error', 'Kategori produk tidak dapat dihapus karena masih digunakan.');
        }
    }
}
