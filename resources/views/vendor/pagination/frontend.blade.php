@if ($paginator->hasPages())
    <style>
        .frontend-pagination .page-link {
            height: 32px !important;
            width: 32px !important;
            padding: 6px !important;
            font-size: 12px !important;
            line-height: 1.4 !important;
            min-width: unset !important;
            min-height: unset !important;
            /* height: auto !important; */
        }
    </style>

    <div class="d-flex justify-content-between align-items-center mt-4 flex-wrap gap-2">

        {{-- Left: Showing X to Y of Z --}}
        <p class="mb-0" style="color:#5a6a7a; font-size:13px;">
            Showing
            <strong>{{ $paginator->firstItem() }}</strong>
            &ndash;
            <strong>{{ $paginator->lastItem() }}</strong>
            of
            <strong>{{ $paginator->total() }}</strong>
            results
        </p>

        {{-- Right: Pagination links --}}
        <nav aria-label="Page navigation">
            <ul class="pagination pagination-sm justify-content-end mb-0 frontend-pagination mt-0">

                {{-- Previous --}}
                @if ($paginator->onFirstPage())
                    <li class="page-item disabled">
                        <a class="page-link" aria-disabled="true" tabindex="-1">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                    </li>
                @else
                    <li class="page-item">
                        <a class="page-link" href="{{ $paginator->previousPageUrl() }}" rel="prev">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                    </li>
                @endif

                {{-- Page Numbers --}}
                @foreach ($elements as $element)
                    {{-- Dots --}}
                    @if (is_string($element))
                        <li class="page-item disabled">
                            <a class="page-link">{{ $element }}</a>
                        </li>
                    @endif

                    {{-- Links --}}
                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            @if ($page == $paginator->currentPage())
                                <li class="page-item active" aria-current="page">
                                    <a class="page-link"
                                        style="background:#0b3d91; border-color:#0b3d91;">{{ $page }}</a>
                                </li>
                            @else
                                <li class="page-item">
                                    <a class="page-link" href="{{ $url }}">{{ $page }}</a>
                                </li>
                            @endif
                        @endforeach
                    @endif
                @endforeach

                {{-- Next --}}
                @if ($paginator->hasMorePages())
                    <li class="page-item">
                        <a class="page-link" href="{{ $paginator->nextPageUrl() }}" rel="next">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    </li>
                @else
                    <li class="page-item disabled">
                        <a class="page-link" aria-disabled="true" tabindex="-1">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    </li>
                @endif

            </ul>
        </nav>
    </div>
@endif
