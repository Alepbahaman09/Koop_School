<dialog id="category-manager" class="m-auto max-h-[90vh] w-[calc(100%-2rem)] max-w-5xl overflow-hidden rounded-xl bg-white p-0 shadow-2xl backdrop:bg-slate-950/40">
    <div class="relative flex max-h-[90vh] flex-col">
        <div id="category-toast-container" class="pointer-events-none absolute right-4 top-16 z-10 flex w-[calc(100%-2rem)] max-w-lg flex-col gap-3"></div>

        @if (session('category_success'))
            <div data-initial-toast data-toast-target="#category-toast-container" data-type="success" data-message="{{ session('category_success') }}"></div>
        @endif

        @if (session('category_error') || ($errors->any() && old('_category_form')))
            <div data-initial-toast data-toast-target="#category-toast-container" data-type="error" data-message="{{ session('category_error') ?? $errors->first() }}"></div>
        @endif

        <div class="flex items-start justify-between gap-4 border-b border-slate-100 p-5">
            <div>
                <h2 class="text-lg font-extrabold text-slate-950">Manage Categories</h2>
                <p class="mt-1 text-xs font-semibold text-slate-400">Add categories and manage their names, icons, and visibility.</p>
            </div>
            <form method="dialog">
                <button class="grid h-8 w-8 place-items-center rounded-lg bg-slate-100 text-lg font-extrabold text-slate-500" aria-label="Close category manager">&times;</button>
            </form>
        </div>

        <div class="space-y-5 overflow-y-auto p-5">
            <div class="grid gap-3 sm:grid-cols-4">
                @foreach ([
                    ['Categories', $categoryStats['total']],
                    ['Visible', $categoryStats['active']],
                    ['Hidden', $categoryStats['inactive']],
                    ['Products', $categoryStats['products']],
                ] as [$label, $value])
                    <div class="rounded-lg bg-slate-50 p-3">
                        <p class="text-xs font-bold text-slate-400">{{ $label }}</p>
                        <p class="mt-1 text-lg font-extrabold text-slate-900">{{ number_format($value) }}</p>
                    </div>
                @endforeach
            </div>

            <section class="rounded-xl border border-slate-200 p-4">
                <h3 class="text-sm font-extrabold text-slate-900">Add Category</h3>
                <form method="POST" action="{{ route('categories.store') }}" enctype="multipart/form-data" class="mt-4 grid gap-3 lg:grid-cols-[1fr_1fr_auto] lg:items-end">
                    @csrf
                    <input type="hidden" name="_category_form" value="1">
                    <input type="hidden" name="is_active" value="1">
                    <label class="text-xs font-extrabold uppercase text-slate-400">
                        Name
                        <input name="name" value="{{ old('_category_form') ? old('name') : '' }}" required placeholder="Category name" class="mt-1 h-10 w-full rounded-lg border-slate-200 text-sm font-semibold">
                    </label>
                    <label class="text-xs font-extrabold uppercase text-slate-400">
                        Icon
                        <input name="icon" type="file" accept="image/*" class="mt-1 block h-10 w-full rounded-lg border border-slate-200 bg-white text-xs text-slate-500 file:mr-2 file:h-10 file:border-0 file:bg-indigo-50 file:px-3 file:font-extrabold file:text-indigo-700">
                    </label>
                    <button class="h-10 whitespace-nowrap rounded-lg bg-indigo-600 px-4 text-sm font-extrabold text-white">Add Category</button>
                </form>
            </section>

            <section>
                <h3 class="mb-3 text-sm font-extrabold text-slate-900">Existing Categories</h3>
                <div class="grid gap-3 lg:grid-cols-2">
                    @forelse ($allCategories as $category)
                        <article class="rounded-xl border border-slate-200 p-4">
                            <form method="POST" action="{{ route('categories.update', $category) }}" enctype="multipart/form-data" class="space-y-3">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="_category_form" value="1">

                                <div class="flex items-center gap-3">
                                    <div class="grid h-14 w-14 shrink-0 place-items-center overflow-hidden rounded-xl bg-indigo-50 ring-1 ring-indigo-100">
                                        @if ($category->icon_url)
                                            <img src="{{ $category->icon_url }}" alt="{{ $category->name }}" class="h-full w-full object-contain">
                                        @else
                                            <span class="text-lg font-black text-indigo-500">{{ strtoupper(substr($category->name, 0, 1)) }}</span>
                                        @endif
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <input name="name" required value="{{ $category->name }}" class="h-9 w-full rounded-lg border-slate-200 text-sm font-semibold">
                                        <p class="mt-1 text-xs font-semibold text-slate-400">{{ number_format($category->products_count) }} product(s)</p>
                                    </div>
                                </div>

                                <input name="icon" type="file" accept="image/*" class="block w-full text-xs text-slate-500 file:mr-2 file:rounded-lg file:border-0 file:bg-indigo-50 file:px-3 file:py-2 file:font-extrabold file:text-indigo-700">

                                <div class="flex items-center justify-between gap-3 border-t border-slate-100 pt-3">
                                    <label class="flex items-center gap-2 text-xs font-bold text-slate-600">
                                        <input type="hidden" name="is_active" value="0">
                                        <input name="is_active" value="1" type="checkbox" @checked($category->is_active) class="rounded border-slate-300 text-indigo-600">
                                        Visible in app
                                    </label>
                                    <button class="h-9 rounded-lg bg-slate-900 px-3 text-xs font-extrabold text-white">Save</button>
                                </div>
                            </form>

                            <div class="mt-2 text-right">
                                @if ((int) $category->products_count === 0)
                                    <form method="POST" action="{{ route('categories.destroy', $category) }}" onsubmit="return confirm('Delete this category?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="text-xs font-bold text-rose-600">Delete Category</button>
                                    </form>
                                @else
                                    <p class="text-xs font-semibold text-slate-400">Move its products before deleting.</p>
                                @endif
                            </div>
                        </article>
                    @empty
                        <p class="rounded-xl bg-slate-50 p-8 text-center text-sm font-semibold text-slate-400 lg:col-span-2">No categories yet.</p>
                    @endforelse
                </div>
            </section>
        </div>
    </div>
</dialog>
