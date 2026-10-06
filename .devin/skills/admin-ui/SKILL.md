---
name: admin-ui
description: Shadcn-inspired design system for the TSACI admin console (plain PHP + Tailwind CDN). Use when creating or restyling any admin/ page.
---

# Admin UI — shadcn-style design system

The admin console (`admin/`) is plain PHP + Tailwind Play CDN + Font Awesome. shadcn/ui is React-only, so we replicate its visual language with Tailwind utilities plus a shared stylesheet: `admin/assets/admin-theme.css` (loaded once from `admin/includes/sidebar.php`, which every authenticated page includes).

## Design tokens (shadcn "zinc" light theme)

| Token | Value | Usage |
|-------|-------|-------|
| background | `#ffffff` | page + card surfaces |
| foreground | `#09090b` (zinc-950) | headings, strong text |
| muted | `#f4f4f5` (zinc-100) | hover fills, table head, badges |
| muted-foreground | `#71717a` (zinc-500) | secondary text |
| border | `#e4e4e7` (zinc-200) | all card/table/input borders |
| primary | `#18181b` (zinc-900) | primary buttons, active accents |
| primary-foreground | `#fafafa` | text on primary |
| destructive | `#dc2626` (red-600) | delete/danger |
| ring | `#2c5530` | focus rings (`box-shadow: 0 0 0 2px rgba(44,85,48,.15)`) |
| radius | `0.5rem` | `rounded-md` buttons/inputs, `rounded-lg` cards |

Brand green `#2c5530` IS the primary color — surfaces and secondary text stay neutral zinc like shadcn, but primary actions, active states, focus rings, and accents use the green. `#8bc34a` remains the bright accent (login panel highlights).

## Font

Inter (`admin-theme.css` imports it). Do NOT re-add Poppins to admin pages.

## Component recipes

- **Page shell**: fixed left sidebar `w-64` white `border-r border-zinc-200`; content `.lg:ml-64` white, `p-6 lg:p-10`.
- **Card**: `rounded-lg border border-zinc-200 bg-white` (shadow only for dropdowns/popovers: `shadow-lg`).
- **Primary button**: `inline-flex items-center gap-2 h-9 px-4 rounded-md bg-[#2c5530] text-sm font-medium text-white hover:bg-[#22402a] transition-colors`.
- **Outline/ghost button**: same box but `border border-zinc-200 bg-white text-zinc-900 hover:bg-zinc-100`.
- **Destructive**: `text-red-600 hover:bg-red-50` for icon/ghost actions; solid `bg-red-600 text-white` sparingly.
- **Input/select/textarea**: `h-9 w-full rounded-md border border-zinc-200 bg-white px-3 text-sm text-zinc-900 placeholder:text-zinc-400 focus:outline-none focus:ring-2 focus:ring-[#2c5530]/15 focus:border-[#2c5530]` (textarea: `py-2 h-auto`).
- **Table** (`.min-w-full`): header `bg-zinc-50 text-xs font-medium uppercase tracking-wide text-zinc-500`; rows `border-b border-zinc-200 hover:bg-zinc-50`; cells `px-4 py-3 text-sm text-zinc-700`.
- **Badge**: `inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium` — default `bg-zinc-100 text-zinc-700`, success `bg-emerald-50 text-emerald-700`, danger `bg-red-50 text-red-700`.
- **Flash/status messages**: `rounded-md border px-4 py-3 text-sm` — success `border-emerald-200 bg-emerald-50 text-emerald-800`, error `border-red-200 bg-red-50 text-red-800`.
- **Nav item** (sidebar): `flex items-center gap-3 rounded-md px-3 py-2 text-sm` — active `bg-[#e9f1ea] text-[#2c5530] font-medium`, idle `text-zinc-500 hover:bg-zinc-100 hover:text-zinc-900`.
- **Nav group label**: `px-3 pt-4 pb-1.5 text-[11px] font-semibold uppercase tracking-wider text-zinc-400`.
- **Dropdown/popover**: `rounded-md border border-zinc-200 bg-white shadow-lg`.

## Rules when editing admin pages

- Never reintroduce the old palette (`#23332c`, `#3d7a66`, `#60796e`, `#e6ece8`, `#eff4f1`, `#f5f7f5`, `#eef3f0`, `#8a978f`, `#66746c`) — map to zinc equivalents instead.
- `admin-theme.css` already restyles generic elements (tables `.min-w-full`, form controls, `.bg-primary`, `.btn-*` shims); prefer it over per-page `<style>` blocks.
- Keep existing PHP logic, CSRF fields, PRG redirects, `view-toggle.php` hooks, and `.min-w-full` table classes intact — the theme hooks depend on them.
- Sidebar collapse/nav-group behavior and localStorage keys are wired in `sidebar.php`; restyle markup, don't rename IDs (`#sidebar`, `#sidebar-toggle`, `#admin-topbar`, `.nav-group`, `.sidebar-label`, `.sidebar-badge`, `.sidebar-scroll`).
- Keep `admin/includes/navbar.php` (legacy top nav) untouched unless a page actually includes it — `sidebar.php` is the real chrome.
