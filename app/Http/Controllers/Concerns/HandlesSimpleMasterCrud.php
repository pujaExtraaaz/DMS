<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

trait HandlesSimpleMasterCrud
{
    abstract protected function masterModel(): string;

    abstract protected function masterRoutePrefix(): string;

    abstract protected function masterViewPrefix(): string;

    abstract protected function masterTitle(): string;

    protected function masterValidationRules(?Model $model = null): array
    {
        return [];
    }

    protected function masterRelations(): array
    {
        return [];
    }

    public function index(Request $request): View
    {
        $query = $this->masterModel()::query()->latest();

        foreach ($this->masterRelations() as $relation) {
            $query->with($relation);
        }

        if ($request->filled('search')) {
            $search = $request->string('search');
            $query->where(function ($q) use ($search) {
                if (method_exists($this->masterModel(), 'getFillable')) {
                    foreach (['name', 'code', 'sku'] as $field) {
                        if (in_array($field, (new ($this->masterModel())))->getFillable(), true)) {
                            $q->orWhere($field, 'like', "%{$search}%");
                        }
                    }
                }
            });
        }

        return view("{$this->masterViewPrefix()}.index", [
            'title' => $this->masterTitle(),
            'items' => $query->paginate(15)->withQueryString(),
            'search' => $request->string('search'),
        ]);
    }

    public function create(): View
    {
        return view("{$this->masterViewPrefix()}.form", [
            'title' => "Create {$this->masterTitle()}",
            'item' => new ($this->masterModel()),
            'formData' => $this->formData(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate($this->masterValidationRules());

        $this->masterModel()::create($data);

        return $this->flashSuccess(
            "{$this->masterTitle()} created successfully.",
            "{$this->masterRoutePrefix()}.index"
        );
    }

    public function show(Model $model): View
    {
        foreach ($this->masterRelations() as $relation) {
            $model->load($relation);
        }

        return view("{$this->masterViewPrefix()}.show", [
            'title' => $this->masterTitle(),
            'item' => $model,
        ]);
    }

    public function edit(Model $model): View
    {
        return view("{$this->masterViewPrefix()}.form", [
            'title' => "Edit {$this->masterTitle()}",
            'item' => $model,
            'formData' => $this->formData(),
        ]);
    }

    public function update(Request $request, Model $model): RedirectResponse
    {
        $data = $request->validate($this->masterValidationRules($model));

        $model->update($data);

        return $this->flashSuccess(
            "{$this->masterTitle()} updated successfully.",
            "{$this->masterRoutePrefix()}.index"
        );
    }

    public function destroy(Model $model): RedirectResponse
    {
        $model->delete();

        return $this->flashSuccess(
            "{$this->masterTitle()} deleted successfully.",
            "{$this->masterRoutePrefix()}.index"
        );
    }

    protected function formData(): array
    {
        return [];
    }
}
