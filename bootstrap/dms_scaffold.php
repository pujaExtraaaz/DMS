<?php
/**
 * One-time scaffold generator for DMS controllers, routes, and views.
 * Run: php bootstrap/dms_scaffold.php
 */

$base = dirname(__DIR__);

function writeFile(string $path, string $content): void
{
    $dir = dirname($path);
    if (! is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    file_put_contents($path, $content);
    echo "Created: {$path}\n";
}

// Master entity definitions
$masters = [
    'CustomerType' => [
        'model' => 'CustomerType',
        'route' => 'customer-types',
        'param' => 'customer_type',
        'view' => 'customer-types',
        'title' => 'Customer Type',
        'fields' => [
            ['name' => 'name', 'label' => 'Name', 'type' => 'text', 'required' => true],
            ['name' => 'code', 'label' => 'Code', 'type' => 'text', 'required' => true],
            ['name' => 'description', 'label' => 'Description', 'type' => 'textarea'],
            ['name' => 'is_active', 'label' => 'Active', 'type' => 'checkbox'],
        ],
        'rules' => "['name' => 'required|string|max:255', 'code' => 'required|string|max:20|unique:customer_types,code', 'description' => 'nullable|string', 'is_active' => 'boolean']",
        'columns' => ['name', 'code', 'is_active'],
    ],
    'Area' => [
        'model' => 'Area',
        'route' => 'areas',
        'param' => 'area',
        'view' => 'areas',
        'title' => 'Area',
        'fields' => [
            ['name' => 'name', 'label' => 'Name', 'type' => 'text', 'required' => true],
            ['name' => 'code', 'label' => 'Code', 'type' => 'text', 'required' => true],
            ['name' => 'is_active', 'label' => 'Active', 'type' => 'checkbox'],
        ],
        'rules' => "['name' => 'required|string|max:255', 'code' => 'required|string|max:20|unique:areas,code', 'is_active' => 'boolean']",
        'columns' => ['name', 'code', 'is_active'],
    ],
    'Route' => [
        'model' => 'Route',
        'route' => 'routes',
        'param' => 'route',
        'view' => 'routes',
        'title' => 'Route',
        'fields' => [
            ['name' => 'name', 'label' => 'Name', 'type' => 'text', 'required' => true],
            ['name' => 'code', 'label' => 'Code', 'type' => 'text', 'required' => true],
            ['name' => 'area_id', 'label' => 'Area', 'type' => 'select', 'relation' => 'areas', 'required' => false],
            ['name' => 'is_active', 'label' => 'Active', 'type' => 'checkbox'],
        ],
        'rules' => "['name' => 'required|string|max:255', 'code' => 'required|string|max:20|unique:routes,code', 'area_id' => 'nullable|exists:areas,id', 'is_active' => 'boolean']",
        'columns' => ['name', 'code', 'area.name', 'is_active'],
        'relations' => ['area'],
        'formData' => "\$areas = \\App\\Domains\\Master\\Models\\Area::where('is_active', true)->orderBy('name')->get();",
        'formDataKey' => 'areas',
    ],
    'Uom' => [
        'model' => 'Uom',
        'route' => 'uoms',
        'param' => 'uom',
        'view' => 'uoms',
        'title' => 'UOM',
        'fields' => [
            ['name' => 'name', 'label' => 'Name', 'type' => 'text', 'required' => true],
            ['name' => 'code', 'label' => 'Code', 'type' => 'text', 'required' => true],
            ['name' => 'is_active', 'label' => 'Active', 'type' => 'checkbox'],
        ],
        'rules' => "['name' => 'required|string|max:255', 'code' => 'required|string|max:20|unique:uoms,code', 'is_active' => 'boolean']",
        'columns' => ['name', 'code', 'is_active'],
    ],
    'Vehicle' => [
        'model' => 'Vehicle',
        'route' => 'vehicles',
        'param' => 'vehicle',
        'view' => 'vehicles',
        'title' => 'Vehicle',
        'fields' => [
            ['name' => 'name', 'label' => 'Name', 'type' => 'text', 'required' => true],
            ['name' => 'registration_no', 'label' => 'Registration No', 'type' => 'text', 'required' => true],
            ['name' => 'type', 'label' => 'Type', 'type' => 'text'],
            ['name' => 'capacity', 'label' => 'Capacity', 'type' => 'number'],
            ['name' => 'is_active', 'label' => 'Active', 'type' => 'checkbox'],
        ],
        'rules' => "['name' => 'required|string|max:255', 'registration_no' => 'required|string|max:20|unique:vehicles,registration_no', 'type' => 'nullable|string|max:50', 'capacity' => 'nullable|numeric|min:0', 'is_active' => 'boolean']",
        'columns' => ['name', 'registration_no', 'type', 'is_active'],
    ],
    'Driver' => [
        'model' => 'Driver',
        'route' => 'drivers',
        'param' => 'driver',
        'view' => 'drivers',
        'title' => 'Driver',
        'fields' => [
            ['name' => 'name', 'label' => 'Name', 'type' => 'text', 'required' => true],
            ['name' => 'phone', 'label' => 'Phone', 'type' => 'text'],
            ['name' => 'license_no', 'label' => 'License No', 'type' => 'text'],
            ['name' => 'is_active', 'label' => 'Active', 'type' => 'checkbox'],
        ],
        'rules' => "['name' => 'required|string|max:255', 'phone' => 'nullable|string|max:20', 'license_no' => 'nullable|string|max:30', 'is_active' => 'boolean']",
        'columns' => ['name', 'phone', 'license_no', 'is_active'],
    ],
    'DeliveryPerson' => [
        'model' => 'DeliveryPerson',
        'route' => 'delivery-persons',
        'param' => 'delivery_person',
        'view' => 'delivery-persons',
        'title' => 'Delivery Person',
        'fields' => [
            ['name' => 'name', 'label' => 'Name', 'type' => 'text', 'required' => true],
            ['name' => 'phone', 'label' => 'Phone', 'type' => 'text'],
            ['name' => 'is_active', 'label' => 'Active', 'type' => 'checkbox'],
        ],
        'rules' => "['name' => 'required|string|max:255', 'phone' => 'nullable|string|max:20', 'is_active' => 'boolean']",
        'columns' => ['name', 'phone', 'is_active'],
    ],
];

foreach ($masters as $controllerName => $config) {
    $modelClass = "\\App\\Domains\\Master\\Models\\{$config['model']}";
    $param = $config['param'];
    $routeName = "masters.{$config['route']}";
    $viewPrefix = "masters.{$config['view']}";
    $uniqueRule = str_contains($config['rules'], 'UNIQUE_PLACEHOLDER') ? '' : '';

    $formDataMethod = '';
    if (! empty($config['formData'])) {
        $formDataMethod = "
    protected function formData(): array
    {
        {$config['formData']}
        return ['{$config['formDataKey']}' => \$areas];
    }";
    }

    $withRelations = '';
    if (! empty($config['relations'])) {
        $rels = implode("', '", $config['relations']);
        $withRelations = "->with(['{$rels}'])";
    }

    $updateRules = str_replace('unique:', "unique:{$config['route']},", $config['rules']);
    // Fix unique rules for update - controller will handle inline

    $controller = <<<PHP
<?php

namespace App\Http\Controllers\Master;

use App\Domains\Master\Models\\{$config['model']};
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class {$controllerName}Controller extends Controller
{
    public function index(Request \$request): View
    {
        \$items = {$config['model']}::query(){$withRelations}->latest()
            ->when(\$request->filled('search'), fn (\$q) => \$q->where('name', 'like', '%'.\$request->search.'%'))
            ->paginate(15)
            ->withQueryString();

        return view('{$viewPrefix}.index', compact('items'));
    }

    public function create(): View
    {
        return view('{$viewPrefix}.form', array_merge(['item' => new {$config['model']}], \$this->formData()));
    }

    public function store(Request \$request): RedirectResponse
    {
        \$data = \$this->validated(\$request);
        {$config['model']}::create(\$data);

        return \$this->flashSuccess('{$config['title']} created successfully.', '{$routeName}.index');
    }

    public function show({$config['model']} \${$param}): View
    {
        return view('{$viewPrefix}.show', ['item' => \${$param}]);
    }

    public function edit({$config['model']} \${$param}): View
    {
        return view('{$viewPrefix}.form', array_merge(['item' => \${$param}], \$this->formData()));
    }

    public function update(Request \$request, {$config['model']} \${$param}): RedirectResponse
    {
        \$data = \$this->validated(\$request, \${$param});
        \${$param}->update(\$data);

        return \$this->flashSuccess('{$config['title']} updated successfully.', '{$routeName}.index');
    }

    public function destroy({$config['model']} \${$param}): RedirectResponse
    {
        \${$param}->delete();

        return \$this->flashSuccess('{$config['title']} deleted successfully.', '{$routeName}.index');
    }

    protected function validated(Request \$request, ?{$config['model']} \${$param} = null): array
    {
        \$rules = {$config['rules']};
        if (\${$param}) {
            if (isset(\$rules['code'])) {
                \$rules['code'] = 'required|string|max:20|unique:{$config['route']},code,'.\${$param}->id;
            }
            if (isset(\$rules['registration_no'])) {
                \$rules['registration_no'] = 'required|string|max:20|unique:vehicles,registration_no,'.\${$param}->id;
            }
        }
        \$data = \$request->validate(\$rules);
        \$data['is_active'] = \$request->boolean('is_active');

        return \$data;
    }
{$formDataMethod}
    protected function formData(): array
    {
        return [];
    }
}
PHP;

    // Fix duplicate formData method
    if (! empty($config['formData'])) {
        $controller = str_replace("{\$formDataMethod}\n    protected function formData(): array\n    {\n        return [];\n    }", $formDataMethod, $controller);
    } else {
        $controller = str_replace("{\$formDataMethod}", '', $controller);
    }

    writeFile("{$base}/app/Http/Controllers/Master/{$controllerName}Controller.php", $controller);

    // Generate views
    $routePrefix = $routeName;
    $title = $config['title'];
    $columns = $config['columns'];

    $indexHeaders = '';
    $indexCells = '';
    foreach ($columns as $col) {
        $label = ucwords(str_replace('_', ' ', str_contains($col, '.') ? explode('.', $col)[1] : $col));
        $indexHeaders .= "                            <th scope=\"col\" class=\"px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500\">{$label}</th>\n";
        if ($col === 'is_active') {
            $indexCells .= "                            <td class=\"px-6 py-4 text-sm\"><x-ui.badge :variant=\"\$item->is_active ? 'success' : 'default'\">{{ \$item->is_active ? 'Active' : 'Inactive' }}</x-ui.badge></td>\n";
        } elseif (str_contains($col, '.')) {
            [$rel, $field] = explode('.', $col);
            $indexCells .= "                            <td class=\"px-6 py-4 text-sm text-gray-600\">{{ \$item->{$rel}?->{$field} ?? '—' }}</td>\n";
        } else {
            $indexCells .= "                            <td class=\"px-6 py-4 text-sm text-gray-900\">{{ \$item->{$col} }}</td>\n";
        }
    }

    $indexView = <<<BLADE
@extends('layouts.dms')

@section('title', '{$title}s')

@section('content')
    <x-ui.page-header title="{$title}s" description="Manage {$title} master data.">
        <x-slot name="actions">
            <x-ui.button variant="primary" :href="route('{$routePrefix}.create')">Add {$title}</x-ui.button>
        </x-slot>
    </x-ui.page-header>

    <x-ui.card>
        <form method="GET" class="mb-4 flex gap-2">
            <x-ui.input name="search" placeholder="Search..." value="{{ request('search') }}" class="max-w-xs" />
            <x-ui.button type="submit" variant="secondary">Search</x-ui.button>
        </form>

        <x-ui.table>
            <x-slot name="head">
                <tr>
{$indexHeaders}                            <th scope="col" class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">Actions</th>
                </tr>
            </x-slot>
            @forelse (\$items as \$item)
                <tr class="hover:bg-gray-50">
{$indexCells}                            <td class="px-6 py-4 text-right text-sm space-x-2">
                                <x-ui.button variant="ghost" size="sm" :href="route('{$routePrefix}.edit', \$item)">Edit</x-ui.button>
                                <form method="POST" action="{{ route('{$routePrefix}.destroy', \$item) }}" class="inline" onsubmit="return confirm('Delete this record?')">
                                    @csrf @method('DELETE')
                                    <x-ui.button type="submit" variant="danger" size="sm">Delete</x-ui.button>
                                </form>
                            </td>
                </tr>
            @empty
                <tr><td colspan="{{ count(\$items) > 0 ? 99 : 1 }}" class="px-6 py-8"><x-ui.empty-state title="No records" description="Create your first {$title}." /></td></tr>
            @endforelse
        </x-ui.table>
        <div class="mt-4">{{ \$items->links() }}</div>
    </x-ui.card>
@endsection
BLADE;

    writeFile("{$base}/resources/views/masters/{$config['view']}/index.blade.php", $indexView);

    $formFields = '';
    foreach ($config['fields'] as $field) {
        $fname = $field['name'];
        $flabel = $field['label'];
        $req = ! empty($field['required']) ? ' required' : '';
        if ($field['type'] === 'checkbox') {
            $formFields .= "            <label class=\"flex items-center gap-2 text-sm\"><input type=\"checkbox\" name=\"{$fname}\" value=\"1\" @checked(old('{$fname}', \$item->{$fname})) class=\"rounded border-gray-300 text-indigo-600\"> {$flabel}</label>\n";
        } elseif ($field['type'] === 'textarea') {
            $formFields .= "            <div><label class=\"block text-sm font-medium text-gray-700 mb-1\">{$flabel}</label><textarea name=\"{$fname}\" rows=\"3\" class=\"block w-full rounded-lg border-gray-300 text-sm\">{{ old('{$fname}', \$item->{$fname}) }}</textarea></div>\n";
        } elseif ($field['type'] === 'select' && ! empty($field['relation'])) {
            $relKey = $field['relation'];
            $formFields .= "            <x-ui.select name=\"{$fname}\" label=\"{$flabel}\" placeholder=\"Select...\"><option value=\"\"></option>@foreach(\${$relKey} ?? [] as \$opt)<option value=\"{{ \$opt->id }}\" @selected(old('{$fname}', \$item->{$fname}) == \$opt->id)>{{ \$opt->name }}</option>@endforeach</x-ui.select>\n";
        } else {
            $type = $field['type'];
            $formFields .= "            <x-ui.input name=\"{$fname}\" label=\"{$flabel}\" type=\"{$type}\" :value=\"old('{$fname}', \$item->{$fname})\"{$req} />\n";
        }
    }

    $formView = <<<BLADE
@extends('layouts.dms')

@section('title', \$item->exists ? 'Edit {$title}' : 'Create {$title}')

@section('content')
    <x-ui.page-header :title="\$item->exists ? 'Edit {$title}' : 'Create {$title}'">
        <x-slot name="actions">
            <x-ui.button variant="secondary" :href="route('{$routePrefix}.index')">Back</x-ui.button>
        </x-slot>
    </x-ui.page-header>

    <x-ui.card>
        <form method="POST" action="{{ \$item->exists ? route('{$routePrefix}.update', \$item) : route('{$routePrefix}.store') }}" class="space-y-4 max-w-xl">
            @csrf
            @if(\$item->exists) @method('PUT') @endif
{$formFields}            <div class="flex gap-2 pt-2">
                <x-ui.button type="submit" variant="primary">Save</x-ui.button>
                <x-ui.button variant="ghost" :href="route('{$routePrefix}.index')">Cancel</x-ui.button>
            </div>
        </form>
    </x-ui.card>
@endsection
BLADE;

    writeFile("{$base}/resources/views/masters/{$config['view']}/form.blade.php", $formView);
}

echo "Master scaffold complete.\n";
