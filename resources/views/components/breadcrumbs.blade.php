@props(['items'])

<nav class="browse-breadcrumbs" aria-label="Breadcrumb">
    <ol>
        @foreach ($items as $item)
            <li>
                @if (!$loop->first)
                    <span aria-hidden="true">/</span>
                @endif
                @if ($loop->last)
                    <span aria-current="page">{{ $item['label'] }}</span>
                @else
                    <a class="market-nav-link" href="{{ $item['url'] }}">{{ $item['label'] }}</a>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
