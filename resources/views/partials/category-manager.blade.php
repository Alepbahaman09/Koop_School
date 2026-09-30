<dialog id="category-manager" class="m-auto max-h-[90vh] w-[calc(100%-2rem)] max-w-4xl overflow-hidden rounded-2xl bg-white p-0 shadow-2xl backdrop:bg-slate-950/40">
    <div class="relative flex max-h-[90vh] flex-col">
        <div id="category-toast-container" class="pointer-events-none absolute right-4 top-16 z-10 flex w-[calc(100%-2rem)] max-w-lg flex-col gap-3"></div>

        @if (session('category_success'))
            <div data-initial-toast data-toast-target="#category-toast-container" data-type="success" data-message="{{ session('category_success') }}"></div>
        @endif

        @if (session('category_error') || ($errors->any() && old('_category_form')))
            <div data-initial-toast data-toast-target="#category-toast-container" data-type="error" data-message="{{ session('category_error') ?? $errors->first() }}"></div>
        @endif

        {{-- Modal Header --}}
        <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
            <div>
                <h2 class="text-lg font-extrabold text-slate-950">Manage Product Categories</h2>
                <p class="text-xs font-semibold text-slate-400 mt-0.5">Organize store categories. Deleting a category moves its items into Uncategorised instantly.</p>
            </div>
            <form method="dialog">
                <button class="grid h-9 w-9 place-items-center rounded-xl bg-slate-100 text-slate-500 hover:bg-slate-200 transition font-extrabold" aria-label="Close dialog">&times;</button>
            </form>
        </div>

        {{-- Scrollable Content --}}
        <div class="space-y-6 overflow-y-auto p-6">
            {{-- Category Overview Cards --}}
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                <div class="rounded-xl bg-slate-50 p-3.5 ring-1 ring-slate-100">
                    <p class="text-[11px] font-extrabold uppercase text-slate-400">Total Categories</p>
                    <p class="mt-1 text-xl font-black text-slate-900">{{ number_format($categoryStats['total']) }}</p>
                </div>
                <div class="rounded-xl bg-emerald-50/60 p-3.5 ring-1 ring-emerald-100">
                    <p class="text-[11px] font-extrabold uppercase text-emerald-600">Active In App</p>
                    <p class="mt-1 text-xl font-black text-emerald-700">{{ number_format($categoryStats['active']) }}</p>
                </div>
                <div class="rounded-xl bg-amber-50/60 p-3.5 ring-1 ring-amber-100">
                    <p class="text-[11px] font-extrabold uppercase text-amber-600">Hidden</p>
                    <p class="mt-1 text-xl font-black text-amber-700">{{ number_format($categoryStats['inactive']) }}</p>
                </div>
                <div class="rounded-xl bg-indigo-50/60 p-3.5 ring-1 ring-indigo-100">
                    <p class="text-[11px] font-extrabold uppercase text-indigo-600">Uncategorised Items</p>
                    <p class="mt-1 text-xl font-black text-indigo-700">{{ number_format($categoryStats['uncategorized'] ?? 0) }}</p>
                </div>
            </div>

            {{-- Add Category Form --}}
            <section class="rounded-2xl border border-indigo-100 bg-gradient-to-br from-indigo-50/40 to-white p-5">
                <div class="flex items-center gap-2 mb-3">
                    <span class="flex h-6 w-6 items-center justify-center rounded-md bg-indigo-600 text-white text-xs font-black">+</span>
                    <h3 class="text-sm font-extrabold text-slate-900">Add New Category</h3>
                </div>

                <form method="POST" action="{{ route('categories.store') }}" enctype="multipart/form-data" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @csrf
                    <input type="hidden" name="_category_form" value="1">

                    <div class="space-y-1">
                        <label class="text-xs font-extrabold uppercase text-slate-500">Category Name <span class="text-rose-500">*</span></label>
                        <input name="name" value="{{ old('_category_form') ? old('name') : '' }}" required placeholder="e.g. Uniforms, Stationery, Drinks" class="h-10 w-full rounded-xl border-slate-200 bg-white px-3 text-sm font-semibold focus:border-indigo-500 focus:ring-indigo-500">
                    </div>

                    <div class="space-y-1">
                        <label class="text-xs font-extrabold uppercase text-slate-500">Icon / Thumbnail</label>
                        <input name="icon" type="file" accept="image/*" class="block h-10 w-full rounded-xl border border-slate-200 bg-white text-xs text-slate-500 file:mr-2 file:h-10 file:border-0 file:bg-indigo-50 file:px-3 file:text-xs file:font-bold file:text-indigo-700">
                    </div>

                    <div class="flex flex-col justify-end space-y-2">
                        <label class="flex items-center gap-2 text-xs font-extrabold text-slate-700 cursor-pointer pb-2">
                            <input type="hidden" name="is_active" value="0">
                            <input name="is_active" type="checkbox" value="1" checked class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                            <span>Visible in Mobile App</span>
                        </label>
                        <button type="submit" class="h-10 w-full rounded-xl bg-indigo-600 px-4 text-sm font-extrabold text-white hover:bg-indigo-700 transition shadow-sm">
                            Create Category
                        </button>
                    </div>
                </form>
            </section>

            {{-- Existing Categories List --}}
            <section>
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-sm font-extrabold text-slate-900">Existing Categories ({{ $allCategories->count() }})</h3>
                    <span class="text-xs font-semibold text-slate-400">Items link dynamically to products</span>
                </div>

                <div class="grid gap-3 sm:grid-cols-2">
                    @forelse ($allCategories as $category)
                        <article class="flex flex-col justify-between rounded-xl border border-slate-200 bg-white p-4 shadow-xs hover:border-indigo-200 transition">
                            <form method="POST" action="{{ route('categories.update', $category) }}" enctype="multipart/form-data" class="space-y-3">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="_category_form" value="1">

                                <div class="flex items-center gap-3">
                                    <div class="grid h-12 w-12 shrink-0 place-items-center overflow-hidden rounded-xl bg-indigo-50 text-indigo-600 ring-1 ring-indigo-100">
                                        @if ($category->icon_url)
                                            <img src="{{ $category->icon_url }}" alt="{{ $category->name }}" class="h-full w-full object-cover">
                                        @else
                                            <span class="text-base font-black">{{ strtoupper(substr($category->name, 0, 1)) }}</span>
                                        @endif
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <input name="name" required value="{{ $category->name }}" class="h-9 w-full rounded-lg border-slate-200 text-sm font-bold text-slate-900 focus:border-indigo-400">
                                        <p class="mt-1 text-xs font-bold text-indigo-600">
                                            {{ number_format($category->products_count) }} product(s) linked
                                        </p>
                                    </div>
                                </div>

                                <div class="flex items-center justify-between gap-3 pt-1">
                                    <label class="flex items-center gap-2 text-xs font-semibold text-slate-600 cursor-pointer">
                                        <input type="hidden" name="is_active" value="0">
                                        <input name="is_active" value="1" type="checkbox" @checked($category->is_active) class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                                        Visible in App
                                    </label>
                                    <button type="submit" class="h-8 rounded-lg bg-slate-900 px-3 text-xs font-extrabold text-white hover:bg-slate-800 transition">
                                        Save
                                    </button>
                                </div>
                            </form>

                            <div class="mt-3 flex items-center justify-between border-t border-slate-100 pt-2.5">
                                <span class="text-[11px] font-semibold text-slate-400">
                                    @if ($category->products_count > 0)
                                        {{ $category->products_count }} item(s) will move to Uncategorised
                                    @else
                                        No items currently linked
                                    @endif
                                </span>

                                {{-- Instant delete button: products immediately become Uncategorised --}}
                                <form method="POST" action="{{ route('categories.destroy', $category) }}" onsubmit="return confirm('Delete category \'{{ addslashes($category->name) }}\' instantly? Any associated products will safely become Uncategorised.')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="inline-flex items-center gap-1 text-xs font-extrabold text-rose-600 hover:text-rose-700 hover:underline">
                                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                        Delete
                                    </button>
                                </form>
                            </div>
                        </article>
                    @empty
                        <div class="col-span-2 rounded-xl bg-slate-50 p-8 text-center text-sm font-semibold text-slate-400">
                            No categories created yet. Use the form above to add your first store category.
                        </div>
                    @endforelse
                </div>
            </section>
        </div>
    </div>
</dialog>
