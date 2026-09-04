---
paths:
  - 'resources/views/**'
---

# Views

## GoErion Blade ERP Interface
Blade with Tailwind CSS is GoErion's production ERP frontend. Build complete, accessible, responsive server-rendered interfaces using the existing x-app, x-dashboard, x-home, and x-sidebar structure; use Alpine.js sparingly where already configured.
Keep Blade presentational: use named routes, CSRF protection and correct method spoofing; escape untrusted output; show validation errors; preserve old input; and never query models. Pass all component props explicitly, including company and companies where required.
Group long forms into business sections and share genuine create/edit duplication through partials. Indexes use readable tables, status badges, empty states, required GET filters that preserve active values, and Laravel pagination.
UI visibility is not authorization: hidden controls never secure their endpoints.

## Production Frontend Decision
Blade and Tailwind CSS are the production frontend. Do not introduce or replace them with Livewire, Inertia, Vue, or React without explicit approval; an installed Livewire package is not authorization to use it in new work.
