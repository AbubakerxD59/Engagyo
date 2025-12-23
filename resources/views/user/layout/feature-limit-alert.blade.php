@if (isset($featureLimitReached) && $featureLimitReached)
    <div class="feature-limit-alert-container">
        <div class="feature-limit-banner">
            <div class="container-fluid">
                <div class="row align-items-center">
                    <div class="col-12">
                        <div class="feature-limit-content">
                            <div class="feature-limit-icon-wrapper">
                                <i class="fas fa-exclamation-circle"></i>
                            </div>
                            <div class="feature-limit-text">
                                <h4 class="feature-limit-title">
                                    <strong>Feature Limit Reached</strong>
                                </h4>
                                <p class="feature-limit-message">
                                    {{ $featureLimitMessage ?? 'You have reached your feature limit. Upgrade your package to continue using this feature.' }}
                                </p>
                                @if (isset($featureLimitStats) && $featureLimitStats['limit'] !== null)
                                    <div class="feature-limit-stats">
                                        <span class="usage-badge">
                                            <i class="fas fa-chart-line mr-1"></i>
                                            Usage: <strong>{{ $featureLimitStats['current_usage'] }}</strong> /
                                            <strong>{{ $featureLimitStats['limit'] }}</strong>
                                        </span>
                                    </div>
                                @endif
                            </div>
                            <div class="feature-limit-action">
                                <button type="button" class="btn btn-upgrade" id="upgradeNowBtn" data-toggle="modal"
                                    data-target="#upgradePackagesModal">
                                    <i class="fas fa-arrow-up mr-2"></i>
                                    <span>Upgrade Now</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif

<style>
    .feature-limit-alert-container {
        position: relative;
        z-index: 1051;
        margin: 0;
        padding: 0;
        width: 100%;
    }

    .feature-limit-banner {
        background: linear-gradient(135deg, #208637 0%, #f59736 100%);
        color: #333;
        padding: 20px 0;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        border-radius: 30px;
        /* border-bottom: 3px solid #f57c00; */
    }

    .feature-limit-content {
        display: flex;
        align-items: center;
        gap: 20px;
        flex-wrap: wrap;
    }

    .feature-limit-icon-wrapper {
        flex-shrink: 0;
        width: 50px;
        height: 50px;
        background: rgba(255, 255, 255, 0.25);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .feature-limit-icon-wrapper i {
        font-size: 24px;
        color: #fff;
        text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
    }

    .feature-limit-text {
        flex: 1;
        min-width: 0;
    }

    .feature-limit-title {
        font-size: 18px;
        font-weight: 700;
        color: #fff;
        margin: 0 0 8px 0;
        text-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
        letter-spacing: 0.3px;
    }

    .feature-limit-message {
        font-size: 14px;
        color: rgba(255, 255, 255, 0.95);
        margin: 0 0 10px 0;
        line-height: 1.6;
        word-wrap: break-word;
        text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
    }

    .feature-limit-stats {
        margin-top: 8px;
    }

    .usage-badge {
        display: inline-flex;
        align-items: center;
        background: rgba(255, 255, 255, 0.2);
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 13px;
        color: #fff;
        border: 1px solid rgba(255, 255, 255, 0.3);
    }

    .usage-badge i {
        font-size: 12px;
    }

    .usage-badge strong {
        font-weight: 700;
        margin: 0 2px;
    }

    .feature-limit-action {
        flex-shrink: 0;
    }

    .btn-upgrade {
        background: #fff;
        color: #ff9800;
        border: none;
        padding: 12px 24px;
        border-radius: 25px;
        font-weight: 600;
        font-size: 14px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        text-decoration: none;
    }

    .btn-upgrade:hover {
        background: #f5f5f5;
        color: #208637;
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(0, 0, 0, 0.25);
        text-decoration: none;
    }

    .btn-upgrade:active {
        transform: translateY(0);
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
    }

    .btn-upgrade i {
        font-size: 12px;
    }

    /* Responsive Design */
    @media (max-width: 768px) {
        .feature-limit-banner {
            padding: 15px 0;
        }

        .feature-limit-content {
            flex-direction: column;
            align-items: flex-start;
            gap: 15px;
        }

        .feature-limit-icon-wrapper {
            width: 45px;
            height: 45px;
        }

        .feature-limit-icon-wrapper i {
            font-size: 20px;
        }

        .feature-limit-title {
            font-size: 16px;
        }

        .feature-limit-message {
            font-size: 13px;
        }

        .btn-upgrade {
            width: 100%;
            justify-content: center;
            padding: 14px 24px;
        }

        .feature-limit-action {
            width: 100%;
        }
    }

    @media (max-width: 480px) {
        .feature-limit-banner {
            padding: 12px 0;
        }

        .feature-limit-title {
            font-size: 15px;
        }

        .feature-limit-message {
            font-size: 12px;
        }
    }
</style>

<!-- Upgrade Packages Modal -->
<div class="modal fade" id="upgradePackagesModal" tabindex="-1" role="dialog"
    aria-labelledby="upgradePackagesModalLabel" aria-hidden="true" style="z-index: 1060;">
    <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
        <div class="modal-content upgrade-modal-content">
            <div class="modal-header upgrade-modal-header">
                <h5 class="modal-title" id="upgradePackagesModalLabel">
                    <i class="fas fa-rocket mr-2"></i>
                    Upgrade Your Package
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body upgrade-modal-body">
                <div id="upgradePackagesLoader" class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="sr-only">Loading...</span>
                    </div>
                    <p class="mt-3">Loading upgrade packages...</p>
                </div>
                <div id="upgradePackagesContainer" class="row g-4 justify-content-center" style="display: none;">
                    <!-- Packages will be loaded here via AJAX -->
                </div>
                <div id="upgradePackagesError" class="alert alert-danger" style="display: none;">
                    <i class="fas fa-exclamation-triangle mr-2"></i>
                    <span id="upgradePackagesErrorMessage">Failed to load upgrade packages. Please try again.</span>
                </div>
                <div id="upgradePackagesEmpty" class="text-center py-5" style="display: none;">
                    <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                    <p class="text-muted">You're already on the highest tier package!</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Styles -->
<style>
    /* Ensure modal appears above feature-limit-alert (z-index: 1051) */
    #upgradePackagesModal {
        z-index: 1060 !important;
    }

    #upgradePackagesModal {
        z-index: 1055 !important;
    }

    /* Center packages in modal */
    #upgradePackagesContainer {
        justify-content: center !important;
    }

    .upgrade-modal-content {
        border-radius: 20px;
        border: none;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
        overflow: hidden;
    }

    .upgrade-modal-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
        padding: 20px 30px;
    }

    .upgrade-modal-header .modal-title {
        font-weight: 700;
        font-size: 24px;
        display: flex;
        align-items: center;
    }

    .upgrade-modal-header .close {
        color: white;
        opacity: 0.9;
        font-size: 28px;
        font-weight: 300;
    }

    .upgrade-modal-header .close:hover {
        opacity: 1;
    }

    .upgrade-modal-body {
        padding: 30px;
        background: #f8f9fa;
    }

    .upgrade-package-card {
        background: white;
        border-radius: 16px;
        padding: 30px;
        height: 100%;
        transition: all 0.3s ease;
        border: 2px solid transparent;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
        position: relative;
        overflow: hidden;
        margin: 0 auto;
        max-width: 100%;
    }

    .upgrade-package-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
        transform: scaleX(0);
        transition: transform 0.3s ease;
    }

    .upgrade-package-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 12px 30px rgba(0, 0, 0, 0.15);
        border-color: #667eea;
    }

    .upgrade-package-card:hover::before {
        transform: scaleX(1);
    }

    .upgrade-package-card.featured {
        border-color: #667eea;
        box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
    }

    .upgrade-package-card.featured::before {
        transform: scaleX(1);
        background: linear-gradient(90deg, #f093fb 0%, #f5576c 100%);
    }

    .package-icon-wrapper {
        width: 80px;
        height: 80px;
        margin: 0 auto 20px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
    }

    .package-icon-wrapper img {
        max-width: 50px;
        max-height: 50px;
        filter: brightness(0) invert(1);
    }

    .package-name {
        font-size: 24px;
        font-weight: 700;
        color: #2d3748;
        margin-bottom: 10px;
        text-align: center;
    }

    .package-description {
        font-size: 14px;
        color: #718096;
        text-align: center;
        margin-bottom: 20px;
        min-height: 40px;
    }

    .package-price {
        text-align: center;
        margin-bottom: 25px;
    }

    .package-price-amount {
        font-size: 42px;
        font-weight: 800;
        color: #667eea;
        line-height: 1;
    }

    .package-price-period {
        font-size: 16px;
        color: #718096;
        font-weight: 500;
    }

    .package-features {
        list-style: none;
        padding: 0;
        margin: 0 0 25px 0;
        min-height: 200px;
    }

    .package-features li {
        padding: 10px 0;
        display: flex;
        align-items: flex-start;
        font-size: 14px;
        color: #4a5568;
        border-bottom: 1px solid #e2e8f0;
    }

    .package-features li:last-child {
        border-bottom: none;
    }

    .package-features li i {
        color: #48bb78;
        margin-right: 12px;
        margin-top: 4px;
        font-size: 16px;
        flex-shrink: 0;
    }

    .package-feature-limit {
        font-size: 12px;
        color: #a0aec0;
        margin-left: 5px;
    }

    .package-checkout-btn {
        width: 100%;
        padding: 14px 24px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
        border-radius: 12px;
        font-weight: 600;
        font-size: 16px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        transition: all 0.3s ease;
        box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }

    .package-checkout-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(102, 126, 234, 0.5);
        color: white;
    }

    .package-checkout-btn:active {
        transform: translateY(0);
    }

    .package-checkout-btn.loading {
        opacity: 0.7;
        cursor: not-allowed;
    }

    .package-checkout-btn.loading i {
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        from {
            transform: rotate(0deg);
        }

        to {
            transform: rotate(360deg);
        }
    }

    .featured-badge {
        position: absolute;
        top: 20px;
        right: 20px;
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        color: white;
        padding: 6px 16px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        box-shadow: 0 4px 10px rgba(245, 87, 108, 0.3);
    }

    @media (max-width: 768px) {
        .upgrade-modal-body {
            padding: 20px;
        }

        .upgrade-package-card {
            padding: 20px;
        }

        .package-price-amount {
            font-size: 36px;
        }
    }
</style>

<!-- JavaScript for Modal -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const upgradeBtn = document.getElementById('upgradeNowBtn');
        const modal = document.getElementById('upgradePackagesModal');
        const loader = document.getElementById('upgradePackagesLoader');
        const container = document.getElementById('upgradePackagesContainer');
        const errorDiv = document.getElementById('upgradePackagesError');
        const emptyDiv = document.getElementById('upgradePackagesEmpty');

        // When modal is shown, load packages
        $(modal).on('show.bs.modal', function() {
            loadUpgradePackages();
        });

        function loadUpgradePackages() {
            // Show loader, hide others
            loader.style.display = 'block';
            container.style.display = 'none';
            errorDiv.style.display = 'none';
            emptyDiv.style.display = 'none';

            // Fetch packages
            fetch("{{ route('panel.packages.upgrade') }}", {
                    method: 'GET',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                    credentials: 'same-origin'
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    loader.style.display = 'none';

                    if (data.packages && data.packages.length > 0) {
                        renderPackages(data.packages);
                        container.style.display = 'flex';
                    } else {
                        emptyDiv.style.display = 'block';
                    }
                })
                .catch(error => {
                    console.error('Error loading packages:', error);
                    loader.style.display = 'none';
                    errorDiv.style.display = 'block';
                });
        }

        function renderPackages(packages) {
            container.innerHTML = '';

            packages.forEach((package, index) => {
                const isFeatured = index === Math.floor(packages.length /
                    2); // Middle package is featured

                // Determine column class based on number of packages for better centering
                let colClass = 'col-12';
                if (packages.length === 1) {
                    colClass = 'col-12 col-md-8 col-lg-6';
                } else if (packages.length === 2) {
                    colClass = 'col-12 col-md-6 col-lg-5';
                } else {
                    colClass = 'col-12 col-md-6 col-lg-4';
                }

                const card = document.createElement('div');
                card.className = colClass;

                const featuresHtml = package.features.map(feature => {
                    const limitText = feature.is_unlimited ?
                        '<span class="package-feature-limit">(Unlimited)</span>' :
                        feature.limit_value ?
                        `<span class="package-feature-limit">(${feature.limit_value})</span>` :
                        '';
                    return `<li><i class="fas fa-check-circle"></i> ${feature.name}${limitText}</li>`;
                }).join('');

                card.innerHTML = `
                <div class="upgrade-package-card ${isFeatured ? 'featured' : ''}">
                    ${isFeatured ? '<span class="featured-badge">Popular</span>' : ''}
                    ${package.icon ? `
                        <div class="package-icon-wrapper">
                            <img src="${package.icon}" alt="${package.name}">
                        </div>
                    ` : ''}
                    <h3 class="package-name">${package.name}</h3>
                    ${package.description ? `<p class="package-description">${package.description}</p>` : ''}
                    <div class="package-price">
                        <div class="package-price-amount">${package.price_formatted}</div>
                        <div class="package-price-period">/${package.duration} ${package.date_type}</div>
                    </div>
                    <ul class="package-features">
                        ${featuresHtml || '<li class="text-muted">No features listed</li>'}
                    </ul>
                    <button class="package-checkout-btn" onclick="checkoutPackage(${package.id})" data-package-id="${package.id}">
                        <i class="fas fa-shopping-cart"></i>
                        <span>Upgrade Now</span>
                    </button>
                </div>
            `;

                container.appendChild(card);
            });
        }

        // Global function for checkout
        window.checkoutPackage = function(packageId) {
            const btn = event.target.closest('.package-checkout-btn');
            const originalHtml = btn.innerHTML;

            // Show loading state
            btn.classList.add('loading');
            btn.innerHTML = '<i class="fas fa-spinner"></i> <span>Processing...</span>';
            btn.disabled = true;

            // Redirect to checkout
            const checkoutUrl = "{{ route('payment.checkout', ':packageId') }}".replace(
                ':packageId', packageId);
            window.location.href = checkoutUrl;
        };
    });
</script>
