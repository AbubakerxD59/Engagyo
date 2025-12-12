@php
    $user = auth()->user();
    $hasAccounts = false;
    
    // Check if user has any connected accounts
    $boardsCount = $user->boards()->count();
    $pagesCount = $user->pages()->count();
    $tiktoksCount = $user->tiktok()->count();
    
    $hasAccounts = ($boardsCount > 0 || $pagesCount > 0 || $tiktoksCount > 0);
    
    // Get current route name
    $currentRoute = request()->route()->getName();
    $accountsRoute = 'panel.accounts';
    
    // Don't show message on accounts page itself
    if (!$hasAccounts && $currentRoute !== $accountsRoute) {
        // Redirect to accounts page after showing message
        session()->flash('warning', 'Please connect at least one social media account to continue.');
        header('Location: ' . route($accountsRoute));
        exit;
    }
@endphp

