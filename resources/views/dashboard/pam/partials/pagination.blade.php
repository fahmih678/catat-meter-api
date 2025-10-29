@if ($pams->hasPages())
    <div class="pagination-container">
        <div class="pagination-info">
            <i class="bi bi-list-ul me-2"></i>
            Showing
            <strong>{{ $pams->firstItem() ?? 0 }}</strong> to
            <strong>{{ $pams->lastItem() ?? 0 }}</strong> of
            <strong>{{ $pams->total() }}</strong>
            PAM {{ Str::plural('Company', $pams->total()) }}
            @if ($pams->lastPage() > 1)
                <span class="text-muted ms-2">(Page {{ $pams->currentPage() }} of {{ $pams->lastPage() }})</span>
            @endif
        </div>
        <div class="pagination-wrapper">
            <ul class="pagination">
                {{-- Previous Page Link --}}
                @if ($pams->onFirstPage())
                    <li class="page-item disabled" aria-disabled="true" aria-label="Previous">
                        <span class="page-link" aria-hidden="true">
                            <i class="bi bi-chevron-left"></i>
                        </span>
                    </li>
                @else
                    <li class="page-item">
                        <a class="page-link" href="javascript:void(0)" onclick="loadPage({{ $pams->currentPage() - 1 }})" rel="prev" aria-label="Previous">
                            <i class="bi bi-chevron-left"></i>
                        </a>
                    </li>
                @endif

                {{-- Pagination Elements --}}
                @foreach ($elements as $element)
                    {{-- "Three Dots" Separator --}}
                    @if (is_string($element))
                        <li class="page-item disabled" aria-disabled="true">
                            <span class="page-link">...</span>
                        </li>
                    @endif

                    {{-- Array Of Links --}}
                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            @if ($page == $pams->currentPage())
                                <li class="page-item active" aria-current="page">
                                    <span class="page-link">{{ $page }}</span>
                                </li>
                            @else
                                <li class="page-item">
                                    <a class="page-link" href="javascript:void(0)" onclick="loadPage({{ $page }})">{{ $page }}</a>
                                </li>
                            @endif
                        @endforeach
                    @endif
                @endforeach

                {{-- Next Page Link --}}
                @if ($pams->hasMorePages())
                    <li class="page-item">
                        <a class="page-link" href="javascript:void(0)" onclick="loadPage({{ $pams->currentPage() + 1 }})" rel="next" aria-label="Next">
                            <i class="bi bi-chevron-right"></i>
                        </a>
                    </li>
                @else
                    <li class="page-item disabled" aria-disabled="true" aria-label="Next">
                        <span class="page-link" aria-hidden="true">
                            <i class="bi bi-chevron-right"></i>
                        </span>
                    </li>
                @endif
            </ul>
        </div>
    </div>
@endif