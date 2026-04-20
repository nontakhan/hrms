---
name: risk-ui-ux-polish
description: Improve the UI/UX of the Tepha hospital risk management system with concrete, implementation-ready changes. Use when Codex is asked to redesign, polish, modernize, simplify, or make the interface easier to use across dashboard, reporting, admin, team, head, and director screens in this PHP/Tailwind/DataTables project.
---

# Risk UI/UX Polish

Improve the interface in a way that is usable, intentional, and consistent with the existing stack.

Start by reading `references/ui-ux-checklist.md`.

## Workflow

1. Inspect the target screen and the shared layout in `partials/layout_top.php`.
2. Identify the user role, primary task, and likely friction points before editing.
3. Improve hierarchy first:
   - clarify page title and purpose
   - make primary actions obvious
   - reduce visual noise
   - group related controls
4. Improve usability next:
   - shorten scanning paths
   - reduce duplicate buttons and links
   - make statuses, filters, and tables easier to understand
   - keep forms readable on mobile and desktop
5. Improve visual quality without breaking the existing system:
   - keep Sarabun as the main typeface
   - reuse the existing green brand palette unless the user asks otherwise
   - prefer stronger spacing, cards, section headers, and clearer empty states over decorative complexity
6. Preserve behavior and permissions. Do not weaken role separation or workflow rules for the sake of UI changes.
7. After edits, verify syntax and note what changed in `ROADMAP.md` if the change is substantial.

## Design Rules

- Make the main action the most visually prominent element on the page.
- Keep one clear primary action per section.
- Prefer short helper text over long paragraphs.
- Make critical workflow states visible near the top of the page.
- Use tables only when dense comparison matters; otherwise prefer cards or grouped sections.
- When a page mixes summary, filters, and detailed records, separate them into distinct visual sections.
- Preserve the current technology choices: PHP templates, Tailwind CDN, DataTables, Chart.js, SweetAlert.
- Avoid generic “AI dashboard” styling. Keep the interface calm, medical, and operational.

## Page Priorities

Prioritize these areas when the user asks for broad UI/UX improvement:

1. `dashboard.php`
2. `admin/reports.php`
3. `admin/report_detail.php`
4. `team/report_detail.php`
5. `director/dashboard.php`
6. shared navigation and section layout

## Output Expectations

When using this skill:

- make the code changes, not just suggestions
- keep changes scoped to the requested area unless a shared component clearly needs adjustment
- explain the result in plain language with the main UX improvements first
- mention any residual usability gaps that still remain
