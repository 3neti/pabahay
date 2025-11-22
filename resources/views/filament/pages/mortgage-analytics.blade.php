<x-filament-panels::page>
    {{-- Header Widgets --}}
    <x-filament-widgets::widgets
        :widgets="$this->getHeaderWidgets()"
        :columns="$this->getHeaderWidgetsColumns()"
    />

    {{-- Footer Widgets --}}
    <x-filament-widgets::widgets
        :widgets="$this->getFooterWidgets()"
        :columns="$this->getFooterWidgetsColumns()"
    />
</x-filament-panels::page>
