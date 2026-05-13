<div class="guide-topbar">
    <a href="{{ route('guide.dashboard') }}" class="guide-nav-btn">
        <i class="bi bi-house-door"></i>
        <span>Dashboard</span>
    </a>

    <a href="{{ route('quick-tours.create') }}" class="guide-nav-btn">
        <i class="bi bi-lightning-charge-fill"></i>
        <span>Snabbtur</span>
    </a>

    <a href="{{ route('guide.reports.create') }}" class="guide-nav-btn">
        <i class="bi bi-exclamation-triangle"></i>
        <span>Felrapport</span>
    </a>

    <form method="POST" action="{{ route('logout') }}" class="m-0">
        @csrf
        <button type="submit" class="guide-nav-btn guide-nav-logout">
            <i class="bi bi-box-arrow-right"></i>
            <span>Logga ut</span>
        </button>
    </form>
</div>

<style>
.guide-topbar {
    position: sticky;
    top: 0;
    z-index: 50;

    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 0.5rem;

    background: rgba(255,255,255,0.95);
    backdrop-filter: blur(10px);

    padding: 0.6rem;
    margin-bottom: 1rem;

    border-radius: 16px;
    box-shadow: 0 6px 16px rgba(0,0,0,0.08);
}

.guide-nav-btn {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;

    gap: 0.25rem;

    padding: 0.55rem 0.4rem;

    font-size: 0.75rem;
    font-weight: 600;

    border-radius: 12px;
    text-decoration: none;

    color: #0f172a;
    background: #f1f5f9;

    border: none;
}

.guide-nav-btn i {
    font-size: 1rem;
}

.guide-nav-btn:active {
    transform: scale(0.97);
}

.guide-nav-logout {
    background: #fee2e2;
    color: #991b1b;
}

@media (min-width: 768px) {
    .guide-topbar {
        max-width: 760px;
        margin: 0 auto 1rem;
    }
}
</style>