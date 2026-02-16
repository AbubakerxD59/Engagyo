@extends('frontend.layout.features')

@push('styles')
<style>
.utm-card {
    max-width: 420px;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    border: 1px solid rgba(0,0,0,0.06);
    background: #fff;
}
.utm-card-header {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 20px 20px 16px;
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    border-bottom: 1px solid rgba(0,0,0,0.06);
}
.utm-card-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
    color: #fff;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    flex-shrink: 0;
}
.utm-card-title {
    font-size: 1.1rem;
    font-weight: 600;
    color: #1e293b;
    margin: 0 0 2px 0;
}
.utm-card-subtitle {
    font-size: 0.8rem;
    color: #64748b;
    margin: 0;
}
.utm-card-body {
    padding: 20px;
}
.utm-field {
    margin-bottom: 18px;
}
.utm-field:last-of-type { margin-bottom: 0; }
.utm-row.utm-field { margin-bottom: 18px; }
.utm-label {
    display: block;
    font-size: 0.8rem;
    font-weight: 600;
    color: #334155;
    margin-bottom: 6px;
}
.utm-required { color: #dc2626; }
.utm-input {
    width: 100%;
    padding: 10px 12px;
    font-size: 0.9rem;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    background: #fff;
    color: #1e293b;
    transition: border-color 0.15s, box-shadow 0.15s;
}
.utm-input:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
}
.utm-hint {
    display: block;
    font-size: 0.75rem;
    color: #94a3b8;
    margin-top: 4px;
}
.utm-source-badge {
    display: inline-block;
    padding: 8px 14px;
    font-size: 0.9rem;
    font-weight: 500;
    color: #1e40af;
    background: #eff6ff;
    border-radius: 8px;
    border: 1px solid #bfdbfe;
}
.utm-actions {
    margin-top: 22px;
    padding-top: 18px;
    border-top: 1px solid #e2e8f0;
}
.utm-btn-start {
    width: 100%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 12px 20px;
    font-size: 0.95rem;
    font-weight: 600;
    color: #fff;
    background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
    border: none;
    border-radius: 10px;
    cursor: pointer;
    transition: opacity 0.2s, transform 0.15s;
}
.utm-btn-start:hover {
    opacity: 0.95;
    transform: translateY(-1px);
}
.utm-btn-start:disabled {
    opacity: 0.7;
    cursor: not-allowed;
    transform: none;
}
.utm-btn-icon {
    font-size: 0.75rem;
    opacity: 0.9;
}
</style>
@endpush

@section('body')
    {{-- Free URL Shortener Hero --}}
    <div class="container-fluid bg-light-gradient">
        <div class="container mt-48 py-5">
            <div class="text-wrapper center">
                <div class="animated-text">
                    Free URL Shortener <span></span>
                </div>
                <p>
                    Shorten long links instantly. Create clean, shareable short URLs that redirect to your original link.
                    <br>
                    No sign-up required to learn more—create your first short link free in your dashboard.
                </p>
            </div>
            @php
                $utmKeys = \App\Models\DomainUtmCode::$utm_keys;
                $utmValues = \App\Models\DomainUtmCode::$utm_values;
                $userLoggedIn = auth()->guard('user')->check();
            @endphp
            <div class="col-12">
                <div class="url-shortner mx-auto d-block d-lg-flex align-items-center flex-wrap gap-2">
                    <input type="url" id="shorten-url-input" placeholder="https://example.com/your-long-url"
                        class="short-linker-field" value="">
                    <button type="button" class="btn btn-link-shortner" id="btn-link-shortner">Shorten Link</button>
                </div>
                <div id="shorten-result" class="mt-4 mx-auto text-center" style="display: none;">
                    <p class="text-success mb-2 small">Your short link:</p>
                    <div class="d-flex align-items-center justify-content-center flex-wrap gap-2">
                        <input type="text" id="short-url-output" class="form-control form-control-sm"
                            style="max-width: 360px;" readonly>
                        <button type="button" class="btn btn-sm btn-outline-primary" id="copy-short-url"
                            title="Copy">Copy</button>
                    </div>
                    <p class="text-muted small mt-2 mb-0">Anyone who opens this link will be redirected to your original
                        URL.</p>
                </div>
                <div id="url-tracking-wrap" class="mt-4 mx-auto text-center" style="display: none;">
                    <label class="d-flex align-items-center justify-content-center gap-2 cursor-pointer">
                        <input type="checkbox" id="url-tracking-toggle" class="form-check-input">
                        <span class="small">Enable URL Tracking</span>
                    </label>
                    <p class="small text-muted mb-0 mt-1">Add UTM parameters to track where your links are clicked.</p>
                    <div id="url-tracking-fields" class="utm-card mt-3 mx-auto text-start" style="display: none;">
                        <div class="utm-card-header">
                            <span class="utm-card-icon" aria-hidden="true">↗</span>
                            <div>
                                <h3 class="utm-card-title">UTM parameters</h3>
                                <p class="utm-card-subtitle">Track traffic source, medium, and campaign for this domain.</p>
                            </div>
                        </div>
                        <div class="utm-card-body">
                            <div class="utm-field">
                                <label for="utm-domain-name" class="utm-label">Domain <span class="utm-required">*</span></label>
                                <input type="text" id="utm-domain-name" class="utm-input" placeholder="example.com" maxlength="255" autocomplete="off">
                                <span class="utm-hint">From your shortened URL or enter manually</span>
                            </div>
                            @foreach ($utmKeys as $key => $label)
                                <div class="utm-field utm-row" data-utm-key="{{ $key }}">
                                    <label class="utm-label">{{ $label }}</label>
                                    @if ($key === 'utm_source')
                                        <div class="utm-source-badge">Engagyo</div>
                                        <input type="hidden" class="utm-value" data-key="{{ $key }}" value="Engagyo">
                                    @else
                                        <select class="utm-input utm-value utm-select" data-key="{{ $key }}">
                                            <option value="">Select option</option>
                                            @foreach ($utmValues as $vKey => $vLabel)
                                                <option value="{{ $vKey }}">{{ $vLabel }}</option>
                                            @endforeach
                                        </select>
                                        <input type="text" class="utm-input utm-custom d-none mt-1" data-key="{{ $key }}" placeholder="Enter custom value" autocomplete="off">
                                    @endif
                                </div>
                            @endforeach
                            <div class="utm-actions">
                                <button type="button" class="utm-btn-start" id="start-tracking-btn">
                                    <span class="utm-btn-icon">▶</span>
                                    Start Tracking
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div id="shorten-error" class="mt-3 mx-auto text-center text-danger small" style="display: none;"></div>
            </div>
        </div>
    </div>

    {{-- Why use our URL shortener --}}
    <div class="container-fluid bg-light-white">
        <div class="container py-5">
            <div class="col-12">
                <div class="text-wrapper center">
                    <h2>Why use our free URL shortener?</h2>
                    <p class="desc-small">
                        Built into {{ env('APP_NAME', 'Engagyo') }}—no extra cost. Shorten links, track clicks, and manage
                        everything in one place.
                    </p>
                </div>
            </div>
            <div class="row justify-content-center g-4 py-3">
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="share__content__item h-100">
                        <div class="share__content__link">
                            <h3><i class='bx bx-link-alt'></i> Short, clean links</h3>
                        </div>
                        <div class="share__content">
                            <p>Turn long URLs into short, easy-to-share links (e.g. yoursite.com/s/abc123) that look
                                professional in posts and messages.</p>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="share__content__item h-100">
                        <div class="share__content__link">
                            <h3><i class='bx bx-transfer-alt'></i> Instant redirect</h3>
                        </div>
                        <div class="share__content">
                            <p>Anyone who clicks your short link is taken straight to your original URL. Fast and reliable
                                redirects every time.</p>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="share__content__item h-100">
                        <div class="share__content__link">
                            <h3><i class='bx bx-line-chart'></i> See click counts</h3>
                        </div>
                        <div class="share__content">
                            <p>View how many times each short link was clicked so you can see what’s working and what’s not.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="text-wrapper center mt-4">
                <a href="{{ route('frontend.showRegister') }}" class="btn btn-featured">Start using free Link Shortener</a>
            </div>
        </div>
    </div>

    {{-- How it works --}}
    <div class="container-fluid">
        <div class="container py-5">
            <div class="col-12">
                <div class="text-wrapper center">
                    <h2>How it works</h2>
                    <p class="desc-small">Create and use short links in three simple steps.</p>
                </div>
            </div>
            <div class="row g-4 py-3">
                <div class="col-12 col-md-4 text-center">
                    <div class="review-container left h-100">
                        <span
                            class="d-inline-flex align-items-center justify-content-center rounded-circle bg-primary text-white mb-3"
                            style="width: 48px; height: 48px; font-weight: 700;">1</span>
                        <h3>Sign up free</h3>
                        <p class="mb-0">Create your {{ env('APP_NAME', 'Engagyo') }} account and open Link Shortener from
                            your dashboard.</p>
                    </div>
                </div>
                <div class="col-12 col-md-4 text-center">
                    <div class="review-container left h-100">
                        <span
                            class="d-inline-flex align-items-center justify-content-center rounded-circle bg-primary text-white mb-3"
                            style="width: 48px; height: 48px; font-weight: 700;">2</span>
                        <h3>Paste your long URL</h3>
                        <p class="mb-0">Enter any URL you want to shorten. We generate a unique short code for you.</p>
                    </div>
                </div>
                <div class="col-12 col-md-4 text-center">
                    <div class="review-container left h-100">
                        <span
                            class="d-inline-flex align-items-center justify-content-center rounded-circle bg-primary text-white mb-3"
                            style="width: 48px; height: 48px; font-weight: 700;">3</span>
                        <h3>Share your short link</h3>
                        <p class="mb-0">Copy the short link and use it anywhere. Clicks redirect to your original URL.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- CTA --}}
    <div class="container-fluid bg-light-white py-5">
        <div class="container">
            <div class="text-wrapper center">
                <h2>Free link shortener, no hidden fees</h2>
                <p class="desc-small">Part of {{ env('APP_NAME', 'Engagyo') }}. Create short links, track clicks, and
                    manage
                    them in your dashboard.</p>
                <a href="{{ route('frontend.showRegister') }}" class="btn btn-colored">Get started for free</a>
            </div>
        </div>
    </div>

    {{-- Auth modal for URL Tracking (when not logged in) --}}
    <div class="modal fade" id="urlTrackingAuthModal" tabindex="-1" aria-labelledby="urlTrackingAuthModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="urlTrackingAuthModalLabel">Sign in to start tracking</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-3">Your UTM settings are saved. Sign in or create a free account to add them to your URL Tracking dashboard.</p>
                    <div id="auth-modal-short-url-wrap" class="mb-3" style="display: none;">
                        <label class="small text-muted">Your short link:</label>
                        <div class="input-group input-group-sm">
                            <input type="text" id="auth-modal-short-url" class="form-control" readonly>
                            <button type="button" class="btn btn-outline-secondary" id="auth-modal-copy">Copy</button>
                        </div>
                    </div>
                    <div class="d-flex flex-column gap-2">
                        <a href="{{ route('general.setIntendedAndShowLogin') }}" class="btn btn-primary">Sign In</a>
                        <a href="{{ route('general.setIntendedAndShowRegister') }}" class="btn btn-outline-primary">Sign
                            Up</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (function() {
            var shortenUrl = "{{ route('general.shorten') }}";
            var savePendingUrlTrackingUrl = "{{ route('general.savePendingUrlTracking') }}";
            var panelUrlTrackingStoreUrl = "{{ route('panel.url-tracking.store') }}";
            var csrfToken = "{{ csrf_token() }}";
            var userLoggedIn = {{ $userLoggedIn ? 'true' : 'false' }};

            function showResult(shortUrl, isExisting, originalUrl) {
                document.getElementById('short-url-output').value = shortUrl;
                var label = document.querySelector('#shorten-result .text-success');
                if (label) label.textContent = isExisting ? 'You already shortened this URL. Your short link:' :
                    'Your short link:';
                document.getElementById('shorten-result').style.display = 'block';
                document.getElementById('shorten-error').style.display = 'none';
                document.getElementById('shorten-error').textContent = '';
                var wrap = document.getElementById('url-tracking-wrap');
                if (wrap) {
                    wrap.style.display = 'block';
                    var toggle = document.getElementById('url-tracking-toggle');
                    var fields = document.getElementById('url-tracking-fields');
                    if (toggle) toggle.checked = false;
                    if (fields) fields.style.display = 'none';
                    var domainInput = document.getElementById('utm-domain-name');
                    if (domainInput && originalUrl) domainInput.value = domainFromUrl(originalUrl);
                }
            }

            function showError(msg) {
                document.getElementById('shorten-error').textContent = msg || 'Something went wrong. Please try again.';
                document.getElementById('shorten-error').style.display = 'block';
                document.getElementById('shorten-result').style.display = 'none';
                var wrap = document.getElementById('url-tracking-wrap');
                if (wrap) wrap.style.display = 'none';
            }

            function isValidUrl(s) {
                try {
                    var u = new URL(s);
                    return u.protocol === 'http:' || u.protocol === 'https:';
                } catch (e) {
                    return false;
                }
            }

            function domainFromUrl(url) {
                try {
                    var u = new URL(url);
                    var host = u.hostname || '';
                    if (host.toLowerCase().indexOf('www.') === 0) host = host.slice(4);
                    return host;
                } catch (e) {
                    return '';
                }
            }

            function getUtmCodes() {
                var codes = [];
                document.querySelectorAll('.utm-row').forEach(function(row) {
                    var key = row.getAttribute('data-utm-key');
                    var valueEl = row.querySelector('.utm-value');
                    var customEl = row.querySelector('.utm-custom');
                    var val = valueEl ? valueEl.value : '';
                    if (key === 'utm_source') val = 'Engagyo';
                    else if (valueEl && valueEl.classList.contains('utm-select') && val === 'custom' &&
                        customEl) val = (customEl.value || '').trim();
                    if (val) codes.push({
                        key: key,
                        value: val
                    });
                });
                return codes;
            }

            function collectPayload(url) {
                var domainInput = document.getElementById('utm-domain-name');
                var domain = (domainInput && domainInput.value) ? domainInput.value.trim() : domainFromUrl(url);
                if (!domain) domain = domainFromUrl(url);
                return {
                    domain_name: domain,
                    utm_codes: getUtmCodes()
                };
            }

            document.addEventListener('DOMContentLoaded', function() {
                var toggle = document.getElementById('url-tracking-toggle');
                var urlTrackingFields = document.getElementById('url-tracking-fields');
                var urlInput = document.getElementById('shorten-url-input');
                var domainInput = document.getElementById('utm-domain-name');

                if (toggle && urlTrackingFields) {
                    toggle.addEventListener('change', function() {
                        urlTrackingFields.style.display = this.checked ? 'block' : 'none';
                    });
                }

                if (urlInput && domainInput) {
                    urlInput.addEventListener('blur', function() {
                        var url = (this.value || '').trim();
                        if (url && isValidUrl(url) && !domainInput.value) domainInput.value =
                            domainFromUrl(url);
                    });
                }

                document.querySelectorAll('.utm-select').forEach(function(sel) {
                    sel.addEventListener('change', function() {
                        var row = this.closest('.utm-row');
                        var custom = row ? row.querySelector('.utm-custom') : null;
                        if (custom) custom.classList.toggle('d-none', this.value !== 'custom');
                    });
                });

                var startTrackingBtnEl = document.getElementById('start-tracking-btn');
                if (startTrackingBtnEl) {
                    startTrackingBtnEl.addEventListener('click', function() {
                        var trackingBtn = this;
                        var url = (urlInput && urlInput.value) ? urlInput.value.trim() : '';
                        var payload = collectPayload(url);
                        if (!payload.domain_name) {
                            if (typeof toastr !== 'undefined') toastr.error(
                                'Enter a domain name or shorten a URL first so we can extract the domain.');
                            return;
                        }
                        if (!payload.utm_codes || payload.utm_codes.length === 0) {
                            if (typeof toastr !== 'undefined') toastr.error(
                                'Add at least one UTM value (Source is set to Engagyo).');
                            return;
                        }
                        trackingBtn.disabled = true;
                        if (userLoggedIn) {
                            var storeXhr = new XMLHttpRequest();
                            storeXhr.open('POST', panelUrlTrackingStoreUrl, true);
                            storeXhr.setRequestHeader('Content-Type', 'application/json');
                            storeXhr.setRequestHeader('X-CSRF-TOKEN', csrfToken);
                            storeXhr.setRequestHeader('Accept', 'application/json');
                            storeXhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                            storeXhr.onload = function() {
                                trackingBtn.disabled = false;
                                try {
                                    var sr = JSON.parse(storeXhr.responseText);
                                    if (sr.success && typeof toastr !== 'undefined') toastr.success(sr.message || 'URL Tracking saved.');
                                    else if (!sr.success && typeof toastr !== 'undefined') toastr.error(sr.message || 'Could not save URL Tracking.');
                                } catch (e) {}
                            };
                            storeXhr.onerror = function() { trackingBtn.disabled = false; };
                            storeXhr.send(JSON.stringify({ domain_name: payload.domain_name, utm_codes: payload.utm_codes, _token: csrfToken }));
                        } else {
                            var saveXhr = new XMLHttpRequest();
                            saveXhr.open('POST', savePendingUrlTrackingUrl, true);
                            saveXhr.setRequestHeader('Content-Type', 'application/json');
                            saveXhr.setRequestHeader('X-CSRF-TOKEN', csrfToken);
                            saveXhr.setRequestHeader('Accept', 'application/json');
                            saveXhr.onload = function() {
                                trackingBtn.disabled = false;
                                try {
                                    var sr = JSON.parse(saveXhr.responseText);
                                    if (sr.success) {
                                        var el = document.getElementById('auth-modal-short-url');
                                        var wrap = document.getElementById('auth-modal-short-url-wrap');
                                        var shortOut = document.getElementById('short-url-output');
                                        if (el && shortOut) el.value = shortOut.value;
                                        if (wrap) wrap.style.display = 'block';
                                        var modal = document.getElementById('urlTrackingAuthModal');
                                        if (modal && typeof bootstrap !== 'undefined') new bootstrap.Modal(modal).show();
                                    }
                                } catch (e) {}
                            };
                            saveXhr.onerror = function() { trackingBtn.disabled = false; };
                            saveXhr.send(JSON.stringify({ domain_name: payload.domain_name, utm_codes: payload.utm_codes, _token: csrfToken }));
                        }
                    });
                }

                var btn = document.getElementById('btn-link-shortner');
                var input = document.getElementById('shorten-url-input');
                var copyBtn = document.getElementById('copy-short-url');

                if (btn && input) {
                    btn.addEventListener('click', function() {
                        var url = (input.value || '').trim();
                        if (!url) {
                            showError('Please enter a URL to shorten.');
                            return;
                        }
                        if (!isValidUrl(url)) {
                            showError('Please enter a valid URL (e.g. https://example.com).');
                            return;
                        }

                        btn.disabled = true;
                        btn.textContent = 'Shortening...';
                        showError('');

                        var xhr = new XMLHttpRequest();
                        xhr.open('POST', shortenUrl, true);
                        xhr.setRequestHeader('Content-Type', 'application/json');
                        xhr.setRequestHeader('X-CSRF-TOKEN', csrfToken);
                        xhr.setRequestHeader('Accept', 'application/json');
                        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

                        xhr.onload = function() {
                            try {
                                var res = JSON.parse(xhr.responseText);
                                if (res.success && res.short_url) {
                                    showResult(res.short_url, res.existing === true, url);
                                    if (typeof toastr !== 'undefined') toastr.success(res.existing ?
                                        'You already shortened this URL. Here is your short link.' :
                                        'Link shortened!');
                                } else {
                                    showError(res.message || 'Could not shorten link.');
                                }
                            } catch (e) {
                                showError('Could not shorten link. Please try again.');
                            }
                            btn.disabled = false;
                            btn.textContent = 'Shorten Link';
                        };

                        xhr.onerror = function() {
                            btn.disabled = false;
                            btn.textContent = 'Shorten Link';
                            showError('Network error. Please try again.');
                        };

                        var payload = JSON.stringify({
                            original_url: url,
                            user_agent: navigator.userAgent || '',
                            _token: csrfToken
                        });
                        xhr.send(payload);
                    });
                }

                if (copyBtn) {
                    copyBtn.addEventListener('click', function() {
                        var out = document.getElementById('short-url-output');
                        if (!out || !out.value) return;
                        if (navigator.clipboard && navigator.clipboard.writeText) {
                            navigator.clipboard.writeText(out.value).then(function() {
                                if (typeof toastr !== 'undefined') toastr.success(
                                    'Copied to clipboard!');
                            }).catch(function() {
                                copyFallback(out);
                            });
                        } else {
                            copyFallback(out);
                        }
                    });
                }

                var authModalCopy = document.getElementById('auth-modal-copy');
                if (authModalCopy) {
                    authModalCopy.addEventListener('click', function() {
                        var out = document.getElementById('auth-modal-short-url');
                        if (!out || !out.value) return;
                        if (navigator.clipboard && navigator.clipboard.writeText) {
                            navigator.clipboard.writeText(out.value).then(function() {
                                if (typeof toastr !== 'undefined') toastr.success(
                                    'Copied to clipboard!');
                            }).catch(function() {
                                copyFallback(out);
                            });
                        } else {
                            copyFallback(out);
                        }
                    });
                }

                function copyFallback(el) {
                    el.select();
                    el.setSelectionRange(0, 99999);
                    try {
                        document.execCommand('copy');
                        if (typeof toastr !== 'undefined') toastr.success('Copied to clipboard!');
                    } catch (e) {
                        if (typeof toastr !== 'undefined') toastr.error(
                            'Could not copy. Select and copy manually.');
                    }
                }
            });
        })();
    </script>
@endpush
