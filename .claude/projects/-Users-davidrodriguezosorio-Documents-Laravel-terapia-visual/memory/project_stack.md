---
name: Project Stack & Architecture
description: Technology stack, module architecture, and migration history for terapia-visual
type: project
---

**Stack:** Laravel 13 + Blade + Alpine.js + Tailwind CSS + Filament (admin panel)

**Migration completed (2026-05-12):** Vue 3 + Inertia.js → Blade + Alpine.js

**Frontend:**
- Alpine.js (installed via npm, bootstrapped in `resources/js/app.js`)
- Pure vanilla JS exercise engine: `resources/js/exercise-engine.js`
- `exerciseSession` Alpine component registered in `app.js`
- Tailwind CSS via Vite

**Layouts:**
- `resources/views/components/layouts/app.blade.php` → `<x-layouts.app>` (authenticated)
- `resources/views/components/layouts/guest.blade.php` → `<x-layouts.guest>` (public)

**Patient-facing pages (Blade):**
- `auth/login.blade.php` — Login (phone + birthdate)
- `dashboard.blade.php` — Progress + exercise cards
- `exercises/index.blade.php` — Fullscreen session with Alpine.js canvas engine
- `profile/edit.blade.php` + partials

**Admin panel:** Filament (separate, unaffected by migration)

**Key details:**
- Auth by phone + fecha_nacimiento (no email/password)
- Exercise engine uses canvas + requestAnimationFrame, 20+ exercise types
- `markComplete` uses `fetch()` fire-and-forget, calificacion uses hidden form POST
- No Inertia middleware in bootstrap/app.php
- `HandleInertiaRequests.php` exists but is not registered

**Why:** User wanted to drop Vue entirely and use Blade with Alpine.js for simplicity.
