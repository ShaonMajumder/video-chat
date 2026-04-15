@extends('layouts.app')

@section('title', 'VideoChat | Ultra-Fast LAN Collaboration')
@section('page', 'landing')
@section('body_class', 'site-body landing-body')

@section('content')
<div class="marketing-page">
    <header class="marketing-nav">
        <div class="marketing-nav__inner">
            <div class="marketing-nav__left">
                <a class="marketing-logo" href="{{ route('landing') }}">VideoChat</a>
                <nav class="marketing-nav__links">
                    <a href="#product">Product</a>
                    <a href="#solutions">Solutions</a>
                    <a href="#enterprise">Enterprise</a>
                    <a href="#pricing">Pricing</a>
                </nav>
            </div>
            <div class="marketing-nav__actions">
                <a class="marketing-link" href="{{ route('login') }}">Log In</a>
                <a class="marketing-button marketing-button--primary" href="{{ route('login') }}">Get Started</a>
            </div>
        </div>
    </header>

    <main class="marketing-main">
        <section class="hero-section" id="product">
            <div class="hero-copy">
                <span class="hero-pill">SECURE LAN SPEED</span>
                <h1>Insane Speed.<br>Utter Silence.</h1>
                <p>
                    Zero latency video for high-security teams. Stop paying for external bandwidth.
                    Start communicating at the speed of your internal backbone.
                </p>
                <div class="hero-actions">
                    <a class="marketing-button marketing-button--primary marketing-button--large" href="{{ route('login') }}">Get Started Free</a>
                    <a class="marketing-button marketing-button--ghost marketing-button--large" href="#solutions">Watch Demo</a>
                </div>
            </div>
            <div class="hero-visual">
                <div class="hero-visual__card">
                    <div class="hero-visual__glow"></div>
                    <div class="hero-visual__screen">
                        <div class="hero-visual__grid">
                            @for ($i = 0; $i < 9; $i++)
                                <div class="hero-visual__tile"></div>
                            @endfor
                        </div>
                    </div>
                    <div class="hero-latency">
                        <span>LATENCY</span>
                        <strong>0.02ms</strong>
                    </div>
                </div>
            </div>
        </section>

        <section class="brand-strip">
            <p>TRUSTED BY THE WORLD'S FASTEST INTERNAL TEAMS</p>
            <div>
                <span>VERTEX</span>
                <span>LUMINA</span>
                <span>AXIOM</span>
                <span>STRATA</span>
                <span>QUANTUM</span>
            </div>
        </section>

        <section class="marketing-section" id="solutions">
            <div class="section-heading">
                <h2>Why LAN-First?</h2>
                <p>Modern collaboration should not depend on the public internet. Take control of your infrastructure.</p>
            </div>
            <div class="feature-grid">
                <article class="feature-card">
                    <div class="feature-icon">
                        <span class="material-symbols-outlined">speed</span>
                    </div>
                    <h3>Fiber Speed</h3>
                    <p>Instant 4K video. No lag. No buffering. Just raw speed.</p>
                </article>
                <article class="feature-card feature-card--active" id="enterprise">
                    <div class="feature-icon feature-icon--light">
                        <span class="material-symbols-outlined">encrypted</span>
                    </div>
                    <h3>Hardened Security</h3>
                    <p>Data stays inside your firewall. 100% compliant with internal standards.</p>
                </article>
                <article class="feature-card">
                    <div class="feature-icon">
                        <span class="material-symbols-outlined">savings</span>
                    </div>
                    <h3>Zero Bandwidth</h3>
                    <p>Offload video traffic from your ISP bill to your local backbone.</p>
                </article>
            </div>
        </section>

        <section class="product-section" id="pricing">
            <div class="product-shot">
                <div class="product-shot__frame">
                    <div class="product-shot__window product-shot__window--wide"></div>
                    <div class="product-shot__window-row">
                        <div class="product-shot__window"></div>
                        <div class="product-shot__window"></div>
                    </div>
                </div>
            </div>
            <div class="product-copy">
                <span>ENGINEERED FOR PERFORMANCE</span>
                <h2>Focus on Content.<br>Forget the Link.</h2>
                <ul class="marketing-checks">
                    <li>
                        <span class="material-symbols-outlined">check_circle</span>
                        <div>
                            <strong>SSO Native</strong>
                            <p>Instant deployment with your existing identity stack.</p>
                        </div>
                    </li>
                    <li>
                        <span class="material-symbols-outlined">check_circle</span>
                        <div>
                            <strong>Hardware Acceleration</strong>
                            <p>Smooth 60fps interaction on standard workstation GPUs.</p>
                        </div>
                    </li>
                </ul>
            </div>
        </section>

        <section class="cta-section">
            <div class="cta-card">
                <h2>Stop Waiting. Start Doing.</h2>
                <p>Experience the world's fastest local communication tool. Free for up to 10 users.</p>
                <div class="cta-actions">
                    <a class="marketing-button marketing-button--cyan" href="{{ route('login') }}">Start Free Trial</a>
                    <a class="marketing-button marketing-button--outline" href="{{ route('login') }}">Enterprise Quote</a>
                </div>
            </div>
        </section>
    </main>

    <footer class="marketing-footer">
        <div>
            <strong>VideoChat</strong>
            <p>© 2026 VideoChat Inc. Built for the future of work.</p>
        </div>
        <nav>
            <a href="#pricing">Privacy</a>
            <a href="#pricing">Terms</a>
            <a href="#pricing">Security</a>
        </nav>
    </footer>
</div>
@endsection
