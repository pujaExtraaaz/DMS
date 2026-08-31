@props([
    'striped' => true,
])

<div {{ $attributes->merge(['class' => 'overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm']) }}>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            @isset($head)
                <thead class="bg-gray-50">
                    {{ $head }}
                </thead>
            @endisset

            <tbody @class(['divide-y divide-gray-200 bg-white', 'divide-y divide-gray-100' => $striped])>
                {{ $slot }}
            </tbody>

            @isset($foot)
                <tfoot class="bg-gray-50">
                    {{ $foot }}
                </tfoot>
            @endisset
        </table>
    </div>
</div>
