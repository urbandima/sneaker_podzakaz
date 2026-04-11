<?php
/**
 * Shared auth page styles for login/register flows.
 *
 * @var yii\web\View $this
 */
?>
<style>
:root {
    --auth-dark: #0f0f0f;
    --auth-accent: #0f0f0f;
    --auth-accent-dark: #1f1f1f;
    --auth-muted: #6b7280;
    --auth-border: rgba(0, 0, 0, 0.08);
}

.auth-page {
    position: relative;
    min-height: calc(100vh - 160px);
    padding: clamp(1.5rem, 4vw, 3.5rem) 1rem;
    background: radial-gradient(circle at 20% 20%, rgba(0, 0, 0, 0.04), transparent 55%),
                radial-gradient(circle at 80% 0%, rgba(0, 0, 0, 0.03), transparent 45%),
                #f7f7f7;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
}

.auth-page::before,
.auth-page::after {
    content: '';
    position: absolute;
    width: 420px;
    height: 420px;
    border-radius: 50%;
    filter: blur(80px);
    opacity: 0.15;
    z-index: 0;
}

.auth-page::before {
    background: #374151;
    top: -120px;
    right: -160px;
}

.auth-page::after {
    background: #111827;
    bottom: -160px;
    left: -120px;
}

.auth-container {
    width: 100%;
    max-width: 1100px;
    position: relative;
    z-index: 1;
}

.auth-page.auth-wide {
    min-height: auto;
    padding: clamp(1rem, 3vw, 2rem) clamp(1rem, 4vw, 3.5rem) 2.5rem;
}

.auth-page.auth-wide .auth-container {
    max-width: 1440px;
}

.auth-page.auth-wide .auth-grid {
    grid-template-columns: repeat(auto-fit, minmax(360px, 1fr));
    gap: clamp(1rem, 2vw, 2rem);
}

.auth-page.auth-wide .auth-info,
.auth-page.auth-wide .auth-card {
    border-radius: 20px;
}

.auth-page.auth-wide .auth-info {
    padding: clamp(1.25rem, 3vw, 2.25rem);
}

.auth-page.auth-wide .auth-info h1 {
    font-size: clamp(1.35rem, 2.8vw, 2.1rem);
    margin-bottom: 0.5rem;
}

.auth-page.auth-wide .auth-info p {
    margin-bottom: 1rem;
    font-size: 0.95rem;
}

.auth-page.auth-wide .auth-benefits {
    gap: 0.65rem;
}

.auth-page.auth-wide .auth-stats {
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 0.65rem;
    margin: 1.1rem 0;
}

.auth-page.auth-wide .auth-card {
    box-shadow: 0 18px 40px rgba(15, 23, 42, 0.14);
}

.auth-page.auth-wide .auth-header {
    padding: 1.35rem clamp(1.25rem, 3vw, 2rem) 1rem;
}

.auth-page.auth-wide .auth-header h2 {
    font-size: clamp(1.25rem, 2.4vw, 1.55rem);
}

.auth-page.auth-wide .auth-body {
    padding: 1.35rem clamp(1.25rem, 3vw, 2rem) 1.5rem;
}

.auth-page.auth-wide .auth-form .form-group {
    margin-bottom: 0.85rem;
}

.auth-page.auth-wide .form-grid.two-columns {
    grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
}

.auth-page.auth-wide .form-helpers {
    margin-bottom: 0.9rem;
}

.auth-page.auth-wide .btn-auth {
    padding: 0.9rem 0.9rem;
    font-size: 0.95rem;
}

.auth-page.auth-wide .auth-footer {
    padding: 1rem;
}

.back-to-site {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.9rem;
    color: var(--auth-muted);
    text-decoration: none;
    margin-bottom: 1.5rem;
    padding: 0.35rem 0.75rem;
    border-radius: 999px;
    background: rgba(255,255,255,0.6);
    border: 1px solid rgba(148,163,184,0.25);
    transition: all 0.2s ease;
}

.back-to-site:hover {
    color: #0f172a;
    border-color: rgba(59,130,246,0.4);
    background: rgba(255,255,255,0.9);
}

.auth-grid {
    display: grid;
    grid-template-columns: minmax(320px, 1fr) minmax(360px, 420px);
    gap: clamp(1.5rem, 3vw, 3rem);
    align-items: stretch;
}

.auth-info {
    background: linear-gradient(135deg, #0f0f0f 0%, #1a1a1a 55%, #2d2d2d 100%);
    border-radius: 26px;
    padding: clamp(1.75rem, 3vw, 2.75rem);
    color: #f8fafc;
    position: relative;
    overflow: hidden;
    box-shadow: 0 30px 80px rgba(15, 23, 42, 0.35);
}

.register-page .auth-info {
    background: linear-gradient(135deg, #0f0f0f 0%, #111111 45%, #252525 100%);
}

.auth-info::after {
    content: '';
    position: absolute;
    inset: 0;
    background: radial-gradient(circle at 40% 0%, rgba(255, 255, 255, 0.08), transparent 55%);
    opacity: 0.7;
    pointer-events: none;
}

.auth-info > * {
    position: relative;
    z-index: 1;
}

.info-pill {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    font-size: 0.8rem;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: rgba(248,250,252,0.8);
    padding: 0.35rem 0.9rem;
    border-radius: 999px;
    border: 1px solid rgba(248,250,252,0.2);
    margin-bottom: 1.2rem;
}

.auth-logo {
    display: flex;
    align-items: center;
    gap: 0.85rem;
    margin-bottom: 1.25rem;
}

.auth-logo img {
    width: 56px;
    height: 56px;
    border-radius: 16px;
    border: 1px solid rgba(255,255,255,0.35);
    box-shadow: 0 10px 30px rgba(0,0,0,0.35);
}

.auth-logo span {
    font-size: 1.35rem;
    font-weight: 800;
    letter-spacing: 0.08em;
}

.auth-info h1 {
    font-size: clamp(1.45rem, 3vw, 2.15rem);
    margin-bottom: 0.6rem;
}

.auth-info p {
    color: rgba(248,250,252,0.8);
    line-height: 1.5;
    font-size: 0.95rem;
    margin-bottom: 1.1rem;
}

.auth-benefits {
    list-style: none;
    margin: 0;
    padding: 0;
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.auth-benefits li {
    display: grid;
    grid-template-columns: auto 1fr;
    gap: 0.9rem;
    align-items: flex-start;
}

.icon-ring {
    width: 42px;
    height: 42px;
    border-radius: 14px;
    background: rgba(15,23,42,0.45);
    border: 1px solid rgba(248,250,252,0.2);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
}

.auth-benefits strong {
    display: block;
    font-size: 0.9rem;
}

.auth-benefits small {
    color: rgba(248,250,252,0.7);
    font-size: 0.8rem;
}

.auth-stats {
    display: grid;
    grid-template-columns: repeat(3, minmax(90px, 1fr));
    gap: 0.75rem;
    margin: 1.75rem 0;
}

.auth-stats .stat {
    padding: 0.75rem;
    border-radius: 16px;
    background: rgba(15,23,42,0.55);
    border: 1px solid rgba(248,250,252,0.1);
    text-align: center;
}

.auth-stats .stat span {
    display: block;
    font-size: 1.15rem;
    font-weight: 700;
}

.auth-stats .stat small {
    font-size: 0.72rem;
    color: rgba(248,250,252,0.65);
}

.auth-info-footer {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding-top: 1rem;
    border-top: 1px solid rgba(248,250,252,0.15);
    color: rgba(248,250,252,0.8);
    font-size: 0.9rem;
}

.auth-card {
    background: #fff;
    border-radius: 26px;
    box-shadow: 0 25px 60px rgba(15, 23, 42, 0.12);
    border: 1px solid var(--auth-border);
    overflow: hidden;
    display: flex;
    flex-direction: column;
}

.auth-header {
    padding: 2rem clamp(1.5rem, 3vw, 2.5rem) 1.5rem;
    background: linear-gradient(180deg, rgba(59,130,246,0.08), transparent);
    border-bottom: 1px solid rgba(15,23,42,0.05);
}

.mini-pill {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    font-size: 0.78rem;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: #0f172a;
    background: rgba(59,130,246,0.12);
    padding: 0.3rem 0.85rem;
    border-radius: 999px;
    font-weight: 600;
}

.auth-header h2 {
    margin: 0.8rem 0 0.4rem;
    font-size: 1.4rem;
    color: #0f172a;
}

.auth-header p {
    margin: 0;
    color: #475569;
    line-height: 1.4;
    font-size: 0.9rem;
}

.auth-body {
    padding: 1.4rem clamp(1.25rem, 3vw, 2rem) 1.6rem;
    flex: 1;
    display: flex;
    flex-direction: column;
}

.auth-form .form-group {
    margin-bottom: 1.1rem;
}

.auth-form label {
    display: block;
    font-weight: 600;
    font-size: 0.78rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin-bottom: 0.35rem;
    color: #475569;
}

.auth-form .form-control {
    width: 100%;
    border-radius: 14px;
    border: 1.5px solid #e2e8f0;
    padding: 0.7rem 0.85rem;
    font-size: 0.95rem;
    background: #f8fafc;
    transition: all 0.2s ease;
}

.auth-form .form-control:focus {
    outline: none;
    border-color: var(--auth-accent);
    background: #fff;
    box-shadow: 0 0 0 4px rgba(59,130,246,0.15);
}

.auth-form .form-control.is-invalid {
    border-color: #ef4444;
    background: #fff5f5;
}

.auth-form .invalid-feedback {
    color: #ef4444;
    font-size: 0.75rem;
    margin-top: 0.35rem;
}

.form-grid {
    display: grid;
    gap: 0.85rem;
}

.form-grid.two-columns {
    grid-template-columns: repeat(2, minmax(0, 1fr));
}

.form-helpers {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    margin-bottom: 1rem;
}

.form-check-slim {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

.form-check-slim input[type="checkbox"],
.form-check-extended input[type="checkbox"] {
    width: 18px;
    height: 18px;
    accent-color: var(--auth-accent);
    margin: 0;
}

.form-check-slim span {
    font-size: 0.85rem;
    color: #475569;
}

.form-check-extended {
    display: flex;
    gap: 0.75rem;
    align-items: flex-start;
    padding: 0.9rem 1rem;
    border-radius: 18px;
    border: 1px solid rgba(15,23,42,0.08);
    background: #f8fafc;
    margin-bottom: 0.75rem;
}

.form-check-extended label {
    margin: 0;
    font-size: 0.85rem;
    color: #475569;
    line-height: 1.5;
    cursor: pointer;
}

.form-check-extended label a {
    color: var(--auth-accent);
    text-decoration: none;
    font-weight: 600;
}

.form-check-extended label a:hover {
    color: var(--auth-accent-dark);
}

.link-muted {
    font-size: 0.85rem;
    color: var(--auth-accent);
    text-decoration: none;
    font-weight: 600;
}

.link-muted:hover {
    color: var(--auth-accent-dark);
}

.btn-auth {
    width: 100%;
    border: none;
    border-radius: 16px;
    padding: 0.85rem 1rem;
    background: linear-gradient(120deg, var(--auth-accent) 0%, var(--auth-accent-dark) 80%);
    color: #fff;
    font-size: 0.95rem;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    cursor: pointer;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.btn-auth:hover {
    transform: translateY(-2px);
    box-shadow: 0 20px 35px rgba(59,130,246,0.35);
}

.secure-badge {
    margin-top: 0.9rem;
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    font-size: 0.78rem;
    color: #64748b;
}

.secure-badge i {
    color: #22c55e;
}

.form-note {
    margin-top: 1rem;
    color: #64748b;
    font-size: 0.85rem;
    line-height: 1.5;
}

.auth-divider {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin: 1.75rem 0 1rem;
    color: #94a3b8;
    font-size: 0.85rem;
    text-transform: uppercase;
    letter-spacing: 0.08em;
}

.auth-divider::before,
.auth-divider::after {
    content: '';
    flex: 1;
    height: 1px;
    background: rgba(148,163,184,0.4);
}

.social-login {
    display: flex;
    gap: 0.65rem;
    flex-wrap: wrap;
}

.social-btn {
    flex: 1 1 30%;
    min-width: 150px;
    padding: 0.75rem 0.85rem;
    border-radius: 14px;
    border: 1px solid #e2e8f0;
    background: #fff;
    color: #0f172a;
    font-weight: 600;
    font-size: 0.9rem;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.4rem;
    transition: all 0.2s ease;
}

.social-btn i {
    font-size: 1rem;
}

.social-btn:hover {
    border-color: var(--auth-accent);
    color: var(--auth-accent-dark);
    box-shadow: 0 8px 18px rgba(15,23,42,0.12);
}

.social-btn.google {
    background: #fef3c7;
}

.social-btn.yandex {
    background: #ffe4e6;
}

.social-btn.telegram {
    background: #e0f2ff;
}

.support-links {
    margin-top: 1.25rem;
    display: flex;
    flex-direction: column;
    gap: 0.6rem;
    font-size: 0.9rem;
}

.support-links a {
    color: #0f172a;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
}

.support-links small {
    color: #94a3b8;
}

.auth-footer {
    padding: 1.4rem;
    background: #f8fafc;
    border-top: 1px solid rgba(15,23,42,0.05);
    text-align: center;
}

.auth-footer a {
    color: var(--auth-accent);
    font-weight: 600;
    text-decoration: none;
}

.auth-footer a:hover {
    color: var(--auth-accent-dark);
}

@media (max-width: 992px) {
    .auth-grid {
        grid-template-columns: 1fr;
    }

    .auth-info {
        order: 2;
    }

    .auth-card {
        order: 1;
    }
}

@media (max-width: 600px) {
    .auth-page {
        padding: 1.5rem 1rem 2.5rem;
    }

    .auth-info,
    .auth-card {
        border-radius: 20px;
    }

    .auth-stats {
        grid-template-columns: repeat(2, minmax(100px, 1fr));
    }

    .form-helpers {
        flex-direction: column;
        align-items: flex-start;
    }

    .form-grid.two-columns {
        grid-template-columns: 1fr;
    }

    .social-btn {
        flex: 1 1 100%;
    }
}
</style>
