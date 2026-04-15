# Design System Specification: Velocity in Stillness

## 1. Overview & Creative North Star
This design system is built upon the Creative North Star of **"Velocity in Stillness."** For a high-end business communication platform, we must balance the high-energy "Velocity" of real-time video with the "Stillness" of a focused, professional workspace. 

We move beyond the "SaaS-standard" look by rejecting rigid, boxy grids in favor of an **Editorial Minimalist** approach. This means prioritizing dramatic white space, intentional asymmetry, and a sense of depth achieved through tonal layering rather than structural lines. The goal is a UI that feels less like a software tool and more like a premium digital concierge—unobtrusive, fast, and sophisticated.

---

## 2. Color & Tonal Architecture
The palette is rooted in deep, authoritative indigos, punctuated by high-kinetic cyans. 

### The "No-Line" Rule
Standard UI relies on 1px borders to separate content. **In this design system, solid borders are prohibited for sectioning.** Boundaries must be defined through background color shifts. For example, a sidebar should be rendered in `surface_container_low` (#f5f2fb) against a main content area of `surface` (#fbf8ff). This creates a "soft edge" that feels integrated rather than partitioned.

### Surface Hierarchy & Nesting
Treat the interface as a physical stack of fine paper. Use the Material tiers to define importance:
*   **Base:** `surface` (#fbf8ff) for the main application background.
*   **De-emphasized:** `surface_container_low` (#f5f2fb) for utility panels or backgrounded content.
*   **Elevated:** `surface_container_lowest` (#ffffff) for primary cards or active work surfaces.
*   **High-Impact:** `surface_container_high` (#eae7ef) for modal backdrops or subtle "nested" inner sections.

### The "Glass & Gradient" Rule
To inject "VideoChat" energy into a minimal layout:
*   **Glassmorphism:** For floating overlays (like video controls or call-to-action toasts), use `surface` at 70% opacity with a `24px` backdrop blur. 
*   **Signature Textures:** Main CTAs or active video states should utilize a subtle linear gradient from `primary` (#000666) to `primary_container` (#1a237e) at a 135-degree angle. This adds "soul" and depth to the deep blue.

---

## 3. Typography: Editorial Authority
We utilize a pairing of **Manrope** for expression and **Inter** for utility.

*   **Display & Headlines (Manrope):** Use `display-lg` (3.5rem) and `headline-lg` (2rem) to create clear, unmissable entry points. These should be set with tight letter-spacing (-0.02em) to feel cohesive and modern.
*   **Body & Labels (Inter):** Use `body-lg` (1rem) for high readability in chat logs. Inter provides the technical precision required for business communication.
*   **Hierarchy Tip:** Always maintain a minimum 2-step jump in the typography scale between adjacent elements to ensure the layout feels intentional and high-contrast.

---

## 4. Elevation & Depth
Depth is a functional tool, not a decoration.

*   **Tonal Layering:** Avoid shadows for static elements. Place a `surface_container_lowest` (#ffffff) card on a `surface_container` (#efecf5) background. This "lift" is perceived by the eye without visual clutter.
*   **Ambient Shadows:** Use shadows only for interactive, floating elements (e.g., a dragged video tile). Shadows must be diffused: `0px 12px 32px rgba(27, 27, 33, 0.06)`. The tint should use the `on_surface` color to feel natural.
*   **The "Ghost Border" Fallback:** If accessibility requires a stroke (e.g., in high-contrast mode), use `outline_variant` (#c6c5d4) at 15% opacity. Never use 100% opaque borders.

---

## 5. Components

### Buttons
*   **Primary:** Background `primary` (#000666), Text `on_primary` (#ffffff). Shape: `full` (9999px). The pill shape communicates movement and approachability.
*   **Secondary:** Background `secondary_fixed` (#9cf0ff), Text `on_secondary_fixed` (#001f24). Use this for the "Energetic" actions (Start Call, Join).

### Input Fields
*   **Style:** Minimalist. No background fill; only a `surface_variant` (#e4e1ea) bottom-border of 2px. 
*   **Focus State:** The border transitions to `secondary` (#006875) with a subtle `secondary_container` glow.

### Cards & Lists
*   **Rule:** Forbid divider lines.
*   **Execution:** Use `0.75rem` (Spacing Scale) of vertical white space to separate chat messages. For list items, use a hover state of `surface_container_high` (#eae7ef) with a `lg` (1rem) corner radius to highlight selection.

### Video Tiles
*   **Rounding:** Always use `xl` (1.5rem) for video containers.
*   **Overlay:** Controls should use the Glassmorphism rule, positioned asymmetrically (e.g., bottom-left or top-right) rather than centered, to keep the user's face clear.

---

## 6. Do’s and Don’ts

### Do
*   **Do use asymmetric margins.** Place main content slightly off-center to create a modern, editorial rhythm.
*   **Do utilize Micro-Animations.** Elements should slide 4px and fade in over 200ms using `cubic-bezier(0.4, 0, 0.2, 1)`.
*   **Do lean on "Primary Fixed Dim" (#bdc2ff).** Use this for secondary text on dark backgrounds to maintain readability without harsh contrast.

### Don't
*   **Don't use 100% Black.** Use `on_background` (#1b1b21) for text to keep the "Soft Minimalism" feel.
*   **Don't use "sm" rounding for large components.** Smaller radii feel dated. Stick to `lg`, `xl`, or `full` for a premium, friendly feel.
*   **Don't crowd the interface.** If a feature isn't essential for the current interaction, hide it. "More is Less."

---

## 7. Interaction States
*   **Hover:** Shift background one tier higher (e.g., `surface_container_low` becomes `surface_container`).
*   **Active/Pressed:** 2px scale down (98%) to provide tactile feedback for "fast interaction."
*   **Error:** Use `error` (#ba1a1a) text only. Do not wrap inputs in heavy red boxes; use a subtle `error_container` (#ffdad6) background fill for the entire field area.