@extends('user.layout.main')
@section('title', 'Test Page Insights')
@section('page_content')
    <div class="page-content">
        <section class="content">
            <div class="container-fluid">
                <div class="card">
                    <div class="card-header with-border">
                        <h3 class="card-title"><i class="fas fa-vial mr-2"></i>Page Posts + Insights Test</h3>
                    </div>
                    <div class="card-body">
                        @if ($error)
                            <div class="alert alert-danger">{{ $error }}</div>
                        @else
                            <p class="mb-1"><strong>Page:</strong> {{ $page->name ?? 'N/A' }}</p>
                            <p class="text-muted">Date range: {{ $since }} to {{ $until }}</p>

                            <h5 class="mt-3">Insights</h5>
                            <pre class="bg-light p-3 rounded"><code>{{ json_encode($pageInsights, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</code></pre>

                            <h5 class="mt-4">Posts ({{ count($posts ?? []) }})</h5>
                            <pre class="bg-light p-3 rounded" style="max-height: 500px; overflow: auto;"><code>{{ json_encode($posts, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</code></pre>
                        @endif
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection
