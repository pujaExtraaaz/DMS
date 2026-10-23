@if($can('masters.view', 'products.view', 'customers.view', 'price-master.view'))
    @php $sNum = $num(); @endphp
    <div
        data-sidebar-section="masters"
        class="sidebar-section-item transition-all rounded-xl"
        @dragover="onDragOver($event)"
        @dragleave="onDragLeave($event)"
        @drop="onDrop($event)"
        x-data="{ open: {{ $is('masters.*') ? 'true' : 'false' }} }"
    >
        <div class="flex items-center gap-1">
            <span
                x-show="customizing"
                x-cloak
                draggable="true"
                @dragstart="onDragStart($event, 'masters')"
                @dragend="onDragEnd()"
                @click.stop
                @mousedown.stop
                class="cursor-grab active:cursor-grabbing p-1 rounded hover:bg-slate-700/80 text-indigo-400 hover:text-indigo-300 transition"
                title="Drag to reorder section"
            >
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                </svg>
            </span>
            <div x-show="customizing" x-cloak class="flex items-center gap-0.5" @click.stop @mousedown.stop>
                <button type="button" @click="moveSection('masters', 'up')" class="p-1 rounded hover:bg-slate-700/80 text-slate-400 hover:text-white transition" title="Move Up">
                    <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 15.75l7.5-7.5 7.5 7.5" /></svg>
                </button>
                <button type="button" @click="moveSection('masters', 'down')" class="p-1 rounded hover:bg-slate-700/80 text-slate-400 hover:text-white transition" title="Move Down">
                    <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" /></svg>
                </button>
            </div>
            <button type="button" class="{{ $sectionBtn }} flex-1" x-show="!$root.sidebarCollapsed" x-cloak @click="if (!customizing) open = !open">
                <span><span class="section-num">{{ $sNum }}</span> · Masters</span>
                <svg class="h-3.5 w-3.5 shrink-0 transition-transform" :class="open && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" /></svg>
            </button>
        </div>
        <div x-show="open || $root.sidebarCollapsed" x-cloak class="space-y-0.5">
            @if($can('products.view', 'masters.view'))
                <a href="{{ route('masters.brands.index') }}" class="{{ $itemClass }} {{ $linkClass(['masters.brands.*']) }}"><span x-show="!$root.sidebarCollapsed" x-cloak>Brands</span></a>
                <a href="{{ route('masters.categories.index') }}" class="{{ $itemClass }} {{ $linkClass(['masters.categories.*']) }}"><span x-show="!$root.sidebarCollapsed" x-cloak>Categories</span></a>
                <a href="{{ route('masters.sub-categories.index') }}" class="{{ $itemClass }} {{ $linkClass(['masters.sub-categories.*']) }}"><span x-show="!$root.sidebarCollapsed" x-cloak>Sub Categories</span></a>
                <a href="{{ route('masters.product-types.index') }}" class="{{ $itemClass }} {{ $linkClass(['masters.product-types.*']) }}"><span x-show="!$root.sidebarCollapsed" x-cloak>Product Types</span></a>
                <a href="{{ route('masters.products.index') }}" class="{{ $itemClass }} {{ $linkClass(['masters.products.*']) }}"><span x-show="!$root.sidebarCollapsed" x-cloak>Products</span></a>
            @endif
            @if($can('customers.view', 'masters.view'))
                <a href="{{ route('masters.customers.index') }}" class="{{ $itemClass }} {{ $linkClass(['masters.customers.*', 'masters.parties.*']) }}"><span x-show="!$root.sidebarCollapsed" x-cloak>Parties</span></a>
            @endif
            @if($can('price-master.view', 'masters.view'))
                <a href="{{ route('masters.price-masters.index') }}" class="{{ $itemClass }} {{ $linkClass(['masters.price-masters.*']) }}"><span x-show="!$root.sidebarCollapsed" x-cloak>Price List</span></a>
            @endif
            @if($can('masters.view', 'products.view', 'customers.view'))
                <a href="{{ route('masters.bulk-import.index') }}" class="{{ $itemClass }} {{ $linkClass(['masters.bulk-import.*']) }}"><span x-show="!$root.sidebarCollapsed" x-cloak>Bulk Import</span></a>
            @endif
        </div>
    </div>
@endif

