@extends('user.layout.main')
@section('title', 'Instagram carousel test')
@section('page_content')
    <div class="page-content">
        <div class="content-header clearfix">
            <div class="container-fluid">
                <h1 class="text-dark mb-0"><i class="fab fa-instagram mr-2 text-danger"></i>Instagram carousel publish test</h1>
                <p class="text-muted mb-0 small">Creates a real carousel via Content Publishing API. Requires public HTTPS URLs for each
                    slide (uploads are stored under <code>uploads/instagram-carousel-test/</code>).</p>
            </div>
        </div>
        <section class="content">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-lg-6">
                        <div class="card card-outline card-secondary">
                            <div class="card-header">
                                <h3 class="card-title mb-0">Compose</h3>
                            </div>
                            <div class="card-body">
                                <form id="igCarouselTestForm" enctype="multipart/form-data">
                                    @csrf
                                    <div class="form-group">
                                        <label for="account_id">Instagram account</label>
                                        <select name="account_id" id="account_id" class="form-control">
                                            @forelse ($accounts as $acc)
                                                <option value="{{ $acc->id }}">{{ $acc->name ?? $acc->username }}
                                                    @if ($acc->username)
                                                        ({{ '@'.$acc->username }})
                                                    @endif
                                                </option>
                                            @empty
                                                <option value="">No accounts — connect Instagram first</option>
                                            @endforelse
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="caption">Caption</label>
                                        <input type="text" class="form-control" name="caption" id="caption"
                                            value="Engagyo carousel test">
                                    </div>
                                    <div class="form-group">
                                        <label for="media">Images / videos (2–10 files)</label>
                                        <input type="file" class="form-control-file" name="media[]" id="media" multiple
                                            accept="image/*,video/*">
                                    </div>
                                    <div class="form-group">
                                        <label for="url_lines">Or paste https URLs (one per line)</label>
                                        <textarea class="form-control" name="url_lines" id="url_lines" rows="4"
                                            placeholder="https://example.com/a.jpg&#10;https://example.com/b.jpg"></textarea>
                                    </div>
                                    <div class="form-group form-check">
                                        <input type="checkbox" class="form-check-input" name="async" id="async" value="1">
                                        <label class="form-check-label" for="async">Queue job only (no live step log from
                                            Graph)</label>
                                    </div>
                                    <button type="submit" class="btn btn-primary" id="submitBtn"
                                        {{ count($accounts) ? '' : 'disabled' }}>
                                        <i class="fas fa-paper-plane mr-1"></i> Publish carousel
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="card card-outline card-info">
                            <div class="card-header">
                                <h3 class="card-title mb-0">Steps &amp; response</h3>
                            </div>
                            <div class="card-body">
                                <div id="igCarouselStatus" class="small text-muted mb-2">Submit the form to run the test.</div>
                                <ol id="igCarouselSteps" class="ig-carousel-steps mb-0 pl-3"></ol>
                                <pre id="igCarouselRaw"
                                    class="bg-light border rounded p-2 small mt-3 mb-0 d-none" style="max-height: 320px; overflow: auto;"></pre>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
    <style>
        .ig-carousel-steps li {
            margin-bottom: 0.5rem;
            line-height: 1.35;
        }

        .ig-carousel-steps .step-ok {
            color: #1e7e34;
        }

        .ig-carousel-steps .step-running {
            color: #856404;
        }

        .ig-carousel-steps .step-error {
            color: #c82333;
            font-weight: 600;
        }

        .ig-carousel-steps .step-key {
            font-family: ui-monospace, monospace;
            font-size: 0.85em;
            color: #6c757d;
        }
    </style>
    <script>
        (function() {
            var form = document.getElementById('igCarouselTestForm');
            if (!form) return;
            var stepsEl = document.getElementById('igCarouselSteps');
            var statusEl = document.getElementById('igCarouselStatus');
            var rawEl = document.getElementById('igCarouselRaw');
            var submitBtn = document.getElementById('submitBtn');

            function clearSteps() {
                stepsEl.innerHTML = '';
                rawEl.textContent = '';
                rawEl.classList.add('d-none');
            }

            function appendStep(s) {
                var li = document.createElement('li');
                var st = (s.status || 'ok').toLowerCase();
                li.className = 'step-' + st;
                var key = document.createElement('span');
                key.className = 'step-key';
                key.textContent = (s.key || '') + ' ';
                li.appendChild(key);
                var msg = document.createElement('span');
                msg.textContent = s.message || '';
                li.appendChild(msg);
                if (s.meta && Object.keys(s.meta).length) {
                    var meta = document.createElement('div');
                    meta.className = 'small text-muted mt-1';
                    meta.style.whiteSpace = 'pre-wrap';
                    try {
                        meta.textContent = JSON.stringify(s.meta, null, 0);
                    } catch (e) {
                        meta.textContent = String(s.meta);
                    }
                    li.appendChild(meta);
                }
                if (s.at) {
                    var t = document.createElement('div');
                    t.className = 'small text-muted';
                    t.textContent = s.at;
                    li.appendChild(t);
                }
                stepsEl.appendChild(li);
            }

            form.addEventListener('submit', function(e) {
                e.preventDefault();
                clearSteps();
                statusEl.textContent = 'Publishing… this may take several minutes for video slides.';
                submitBtn.disabled = true;

                var fd = new FormData(form);
                var asyncCb = document.getElementById('async');
                if (!asyncCb || !asyncCb.checked) {
                    fd.delete('async');
                }

                fetch('{{ route('panel.schedule.dev.instagram-carousel-test.publish') }}', {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                        },
                        body: fd,
                        credentials: 'same-origin'
                    })
                    .then(function(r) {
                        return r.json().then(function(data) {
                            return {
                                ok: r.ok,
                                data: data
                            };
                        });
                    })
                    .then(function(result) {
                        var data = result.data || {};
                        if (Array.isArray(data.steps)) {
                            data.steps.forEach(appendStep);
                        }
                        rawEl.textContent = JSON.stringify(data, null, 2);
                        rawEl.classList.remove('d-none');
                        if (data.success) {
                            statusEl.textContent = 'Done. success=true';
                            statusEl.className = 'small text-success mb-2';
                        } else {
                            statusEl.textContent = (data.message || 'Failed') + ' (ok=' + result.ok + ')';
                            statusEl.className = 'small text-danger mb-2';
                        }
                    })
                    .catch(function(err) {
                        statusEl.textContent = 'Request error: ' + err;
                        statusEl.className = 'small text-danger mb-2';
                    })
                    .finally(function() {
                        submitBtn.disabled = false;
                    });
            });
        })();
    </script>
@endsection
