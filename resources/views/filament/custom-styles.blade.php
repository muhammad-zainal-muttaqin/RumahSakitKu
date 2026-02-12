<style>
    /* ============================================================
       RumahSakitKu - Custom Styles & Tailwind Utility Polyfills
       Injected via PanelsRenderHook::HEAD_END

       Filament 4 only compiles Tailwind classes used in its own
       components. Custom blade views need these utility definitions.
       ============================================================ */

    /* ===================== LAYOUT ===================== */
    .block { display: block; }
    .inline-block { display: inline-block; }
    .inline-flex { display: inline-flex; }
    .flex { display: flex; }
    .grid { display: grid; }
    .hidden { display: none; }

    .flex-1 { flex: 1 1 0%; }
    .flex-wrap { flex-wrap: wrap; }
    .flex-shrink-0 { flex-shrink: 0; }
    .items-center { align-items: center; }
    .items-start { align-items: flex-start; }
    .items-end { align-items: flex-end; }
    .justify-center { justify-content: center; }
    .justify-between { justify-content: space-between; }

    .grid-cols-1 { grid-template-columns: repeat(1, minmax(0, 1fr)); }
    .grid-cols-2 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .grid-cols-3 { grid-template-columns: repeat(3, minmax(0, 1fr)); }
    .grid-cols-4 { grid-template-columns: repeat(4, minmax(0, 1fr)); }
    .grid-cols-5 { grid-template-columns: repeat(5, minmax(0, 1fr)); }
    .grid-cols-6 { grid-template-columns: repeat(6, minmax(0, 1fr)); }
    .grid-cols-7 { grid-template-columns: repeat(7, minmax(0, 1fr)); }
    .grid-cols-9 { grid-template-columns: repeat(9, minmax(0, 1fr)); }

    .gap-1 { gap: 0.25rem; }
    .gap-2 { gap: 0.5rem; }
    .gap-3 { gap: 0.75rem; }
    .gap-4 { gap: 1rem; }
    .gap-6 { gap: 1.5rem; }

    /* =================== SPACING =================== */
    .p-2 { padding: 0.5rem; }
    .p-3 { padding: 0.75rem; }
    .p-4 { padding: 1rem; }
    .px-1\.5 { padding-left: 0.375rem; padding-right: 0.375rem; }
    .px-2 { padding-left: 0.5rem; padding-right: 0.5rem; }
    .px-4 { padding-left: 1rem; padding-right: 1rem; }
    .py-0\.5 { padding-top: 0.125rem; padding-bottom: 0.125rem; }
    .py-2 { padding-top: 0.5rem; padding-bottom: 0.5rem; }
    .py-3 { padding-top: 0.75rem; padding-bottom: 0.75rem; }
    .py-4 { padding-top: 1rem; padding-bottom: 1rem; }
    .py-6 { padding-top: 1.5rem; padding-bottom: 1.5rem; }
    .py-8 { padding-top: 2rem; padding-bottom: 2rem; }
    .pt-3 { padding-top: 0.75rem; }

    .mt-0\.5 { margin-top: 0.125rem; }
    .mt-1 { margin-top: 0.25rem; }
    .mt-2 { margin-top: 0.5rem; }
    .mt-4 { margin-top: 1rem; }
    .mt-6 { margin-top: 1.5rem; }
    .mb-1 { margin-bottom: 0.25rem; }
    .mb-2 { margin-bottom: 0.5rem; }
    .mb-3 { margin-bottom: 0.75rem; }
    .mr-1 { margin-right: 0.25rem; }
    .mr-2 { margin-right: 0.5rem; }
    .ml-2 { margin-left: 0.5rem; }
    .mx-auto { margin-left: auto; margin-right: auto; }

    .space-y-1 > :not([hidden]) ~ :not([hidden]) { margin-top: 0.25rem; }
    .space-y-2 > :not([hidden]) ~ :not([hidden]) { margin-top: 0.5rem; }
    .space-y-3 > :not([hidden]) ~ :not([hidden]) { margin-top: 0.75rem; }
    .space-y-4 > :not([hidden]) ~ :not([hidden]) { margin-top: 1rem; }
    .space-y-6 > :not([hidden]) ~ :not([hidden]) { margin-top: 1.5rem; }

    /* ==================== SIZING ==================== */
    .w-3 { width: 0.75rem; }
    .w-4 { width: 1rem; }
    .w-5 { width: 1.25rem; }
    .w-8 { width: 2rem; }
    .w-12 { width: 3rem; }
    .w-full { width: 100%; }

    .h-1\.5 { height: 0.375rem; }
    .h-2 { height: 0.5rem; }
    .h-3 { height: 0.75rem; }
    .h-4 { height: 1rem; }
    .h-5 { height: 1.25rem; }
    .h-8 { height: 2rem; }
    .h-12 { height: 3rem; }

    .min-w-0 { min-width: 0; }
    .max-h-48 { max-height: 12rem; }
    .max-h-96 { max-height: 24rem; }

    /* ================= TYPOGRAPHY ================= */
    .text-xs { font-size: 0.75rem; line-height: 1rem; }
    .text-sm { font-size: 0.875rem; line-height: 1.25rem; }
    .text-base { font-size: 1rem; line-height: 1.5rem; }
    .text-lg { font-size: 1.125rem; line-height: 1.75rem; }
    .text-xl { font-size: 1.25rem; line-height: 1.75rem; }
    .text-2xl { font-size: 1.5rem; line-height: 2rem; }
    .text-3xl { font-size: 1.875rem; line-height: 2.25rem; }

    .text-center { text-align: center; }
    .text-left { text-align: left; }
    .text-right { text-align: right; }

    .font-normal { font-weight: 400; }
    .font-medium { font-weight: 500; }
    .font-semibold { font-weight: 600; }
    .font-bold { font-weight: 700; }

    .truncate { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .uppercase { text-transform: uppercase; }
    .whitespace-nowrap { white-space: nowrap; }

    /* =================== BORDERS =================== */
    .rounded { border-radius: 0.25rem; }
    .rounded-lg { border-radius: 0.5rem; }
    .rounded-xl { border-radius: 0.75rem; }
    .rounded-full { border-radius: 9999px; }

    .border { border-width: 1px; border-style: solid; }
    .border-2 { border-width: 2px; border-style: solid; }
    .border-b { border-bottom-width: 1px; border-bottom-style: solid; }
    .border-t { border-top-width: 1px; border-top-style: solid; }
    .last\:border-0:last-child { border-width: 0; }

    .divide-y > :not([hidden]) ~ :not([hidden]) { border-top-width: 1px; border-top-style: solid; border-bottom-width: 0; }

    /* ==================== VISUAL ==================== */
    .overflow-hidden { overflow: hidden; }
    .overflow-x-auto { overflow-x: auto; }
    .overflow-y-auto { overflow-y: auto; }

    .shadow-sm { box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05); }
    .shadow-md { box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -2px rgba(0, 0, 0, 0.1); }

    .opacity-50 { opacity: 0.5; }

    .transition-all { transition-property: all; transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1); transition-duration: 150ms; }
    .transition-colors { transition-property: color, background-color, border-color, text-decoration-color, fill, stroke; transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1); transition-duration: 150ms; }
    .duration-300 { transition-duration: 300ms; }
    .duration-500 { transition-duration: 500ms; }

    /* ================== POSITION ================== */
    .relative { position: relative; }
    .absolute { position: absolute; }
    .-top-2 { top: -0.5rem; }
    .-right-2 { right: -0.5rem; }

    /* ==================== MISC ==================== */
    .list-inside { list-style-position: inside; }
    .list-disc { list-style-type: disc; }
    .cursor-pointer { cursor: pointer; }
    .select-none { user-select: none; }

    /* =================== GRADIENT =================== */
    .bg-gradient-to-br {
        background-image: linear-gradient(to bottom right, var(--tw-gradient-from, transparent), var(--tw-gradient-to, transparent));
    }
    .from-white { --tw-gradient-from: #ffffff; }
    .to-gray-50 { --tw-gradient-to: #f9fafb; }

    /* ============ GRAY COLORS (STANDARD) ============ */
    .bg-white { background-color: #ffffff; }
    .bg-gray-50 { background-color: #f9fafb; }
    .bg-gray-100 { background-color: #f3f4f6; }
    .bg-gray-200 { background-color: #e5e7eb; }
    .bg-gray-700 { background-color: #374151; }
    .bg-gray-800 { background-color: #1f2937; }

    .text-white { color: #ffffff; }
    .text-gray-300 { color: #d1d5db; }
    .text-gray-400 { color: #9ca3af; }
    .text-gray-500 { color: #6b7280; }
    .text-gray-600 { color: #4b5563; }
    .text-gray-700 { color: #374151; }
    .text-gray-900 { color: #111827; }

    .border-gray-100 { border-color: #f3f4f6; }
    .border-gray-200 { border-color: #e5e7eb; }
    .border-gray-300 { border-color: #d1d5db; }
    .border-gray-500 { border-color: #6b7280; }
    .border-gray-600 { border-color: #4b5563; }
    .border-gray-700 { border-color: #374151; }

    .divide-gray-200 > :not([hidden]) ~ :not([hidden]) { border-color: #e5e7eb; }

    /* ======= NON-FILAMENT COLORS (PURPLE) ======= */
    .bg-purple-50 { background-color: #faf5ff; }
    .bg-purple-100 { background-color: #f3e8ff; }
    .text-purple-400 { color: #a855f7; }
    .text-purple-600 { color: #9333ea; }

    /* ======= NON-FILAMENT COLORS (ORANGE) ======= */
    .bg-orange-50 { background-color: #fff7ed; }
    .text-orange-400 { color: #fb923c; }
    .text-orange-600 { color: #ea580c; }

    /* ======= NON-FILAMENT COLORS (GREEN) ======= */
    .bg-green-100 { background-color: #dcfce7; }
    .text-green-200 { color: #bbf7d0; }
    .text-green-800 { color: #166534; }

    /* ======= NON-FILAMENT COLORS (BLUE) ======= */
    .bg-blue-100 { background-color: #dbeafe; }
    .text-blue-200 { color: #bfdbfe; }
    .text-blue-800 { color: #1e40af; }

    /* ======= NON-FILAMENT COLORS (YELLOW) ======= */
    .bg-yellow-100 { background-color: #fef9c3; }
    .text-yellow-200 { color: #fef08a; }
    .text-yellow-800 { color: #854d0e; }

    /* ============= HOVER STATES ============= */
    .hover\:bg-gray-50:hover { background-color: #f9fafb; }
    .hover\:shadow-md:hover { box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -2px rgba(0, 0, 0, 0.1); }
    .hover\:underline:hover { text-decoration: underline; }

    /* ========== RESPONSIVE: sm (640px+) ========== */
    @media (min-width: 640px) {
        .sm\:grid-cols-2 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .sm\:grid-cols-3 { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        .sm\:w-auto { width: auto; }
    }

    /* ========== RESPONSIVE: md (768px+) ========== */
    @media (min-width: 768px) {
        .md\:grid-cols-2 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .md\:grid-cols-3 { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        .md\:grid-cols-4 { grid-template-columns: repeat(4, minmax(0, 1fr)); }
        .md\:grid-cols-5 { grid-template-columns: repeat(5, minmax(0, 1fr)); }
        .md\:grid-cols-6 { grid-template-columns: repeat(6, minmax(0, 1fr)); }
    }

    /* ========== RESPONSIVE: lg (1024px+) ========== */
    @media (min-width: 1024px) {
        .lg\:grid-cols-2 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .lg\:grid-cols-3 { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        .lg\:grid-cols-4 { grid-template-columns: repeat(4, minmax(0, 1fr)); }
        .lg\:grid-cols-6 { grid-template-columns: repeat(6, minmax(0, 1fr)); }
        .lg\:grid-cols-7 { grid-template-columns: repeat(7, minmax(0, 1fr)); }
        .lg\:grid-cols-9 { grid-template-columns: repeat(9, minmax(0, 1fr)); }
    }

    /* ========== RESPONSIVE: xl (1280px+) ========== */
    @media (min-width: 1280px) {
        .xl\:grid-cols-4 { grid-template-columns: repeat(4, minmax(0, 1fr)); }
    }

    /* ============== DARK MODE ============== */
    html.dark .dark\:bg-gray-700 { background-color: #374151; }
    html.dark .dark\:bg-gray-800 { background-color: #1f2937; }
    html.dark .dark\:bg-gray-900 { background-color: #111827; }
    html.dark .dark\:bg-white { background-color: #ffffff; }

    html.dark .dark\:text-white { color: #ffffff; }
    html.dark .dark\:text-gray-300 { color: #d1d5db; }
    html.dark .dark\:text-gray-400 { color: #9ca3af; }
    html.dark .dark\:text-gray-600 { color: #4b5563; }

    html.dark .dark\:border-gray-600 { border-color: #4b5563; }
    html.dark .dark\:border-gray-700 { border-color: #374151; }
    html.dark .dark\:border-gray-800 { border-color: #1f2937; }

    html.dark .dark\:divide-gray-700 > :not([hidden]) ~ :not([hidden]) { border-color: #374151; }

    /* Dark non-Filament colors */
    html.dark .dark\:bg-purple-900\/20 { background-color: rgba(88, 28, 135, 0.2); }
    html.dark .dark\:text-purple-400 { color: #a855f7; }

    html.dark .dark\:bg-orange-900\/20 { background-color: rgba(124, 45, 18, 0.2); }
    html.dark .dark\:text-orange-400 { color: #fb923c; }

    html.dark .dark\:bg-green-900 { background-color: #14532d; }
    html.dark .dark\:text-green-200 { color: #bbf7d0; }

    html.dark .dark\:bg-blue-900 { background-color: #1e3a8a; }
    html.dark .dark\:text-blue-200 { color: #bfdbfe; }

    html.dark .dark\:bg-yellow-900 { background-color: #713f12; }
    html.dark .dark\:text-yellow-200 { color: #fef08a; }

    /* Dark gradient */
    html.dark .dark\:from-gray-800 { --tw-gradient-from: #1f2937; }
    html.dark .dark\:to-gray-900 { --tw-gradient-to: #111827; }

    /* Dark hover */
    html.dark .dark\:hover\:bg-gray-700\/50:hover { background-color: rgba(55, 65, 81, 0.5); }
    html.dark .dark\:hover\:bg-gray-800\/50:hover { background-color: rgba(31, 41, 55, 0.5); }

    /* ======= TABLE UTILITY (for report tables) ======= */
    .w-full { width: 100%; }
    table.w-full { border-collapse: collapse; }

    /* ============================================================
       COMPONENT OVERRIDES: Login Page
       ============================================================ */
    .fi-simple-layout {
        background: linear-gradient(135deg, #0f766e 0%, #0d9488 30%, #14b8a6 60%, #10b981 100%) !important;
        min-height: 100vh;
    }

    html.dark .fi-simple-layout {
        background: linear-gradient(135deg, #042f2e 0%, #064e3b 30%, #065f46 60%, #0f766e 100%) !important;
    }

    .fi-simple-main-ctn {
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 100vh;
    }

    .fi-simple-main {
        width: 100%;
        max-width: 28rem;
        position: relative;
    }

    .fi-simple-main > div {
        border-radius: 1rem !important;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25), 0 0 0 1px rgba(255, 255, 255, 0.1) !important;
        overflow: hidden;
        position: relative;
        border: none !important;
    }

    .fi-simple-main > div::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, #14b8a6, #0d9488, #10b981);
        z-index: 10;
    }

    html.dark .fi-simple-main > div {
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5), 0 0 0 1px rgba(255, 255, 255, 0.05) !important;
    }

    .fi-simple-header {
        padding-top: 1.5rem !important;
    }

    .fi-simple-header h1,
    .fi-simple-header .fi-simple-header-heading {
        font-size: 1.5rem !important;
        font-weight: 700 !important;
    }

    /* ============================================================
       COMPONENT OVERRIDES: Dashboard Stat Cards
       ============================================================ */
    .fi-wi-stats-overview-stat {
        border-radius: 0.875rem !important;
        border: 1px solid rgba(148, 163, 184, 0.2) !important;
        transition: all 0.2s ease !important;
    }

    .fi-wi-stats-overview-stat:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.08), 0 4px 6px -2px rgba(0, 0, 0, 0.04);
        border-color: rgba(20, 184, 166, 0.3) !important;
    }

    html.dark .fi-wi-stats-overview-stat {
        border-color: rgba(71, 85, 105, 0.5) !important;
    }

    html.dark .fi-wi-stats-overview-stat:hover {
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.3), 0 4px 6px -2px rgba(0, 0, 0, 0.15);
        border-color: rgba(20, 184, 166, 0.4) !important;
    }

    /* ============================================================
       COMPONENT OVERRIDES: Chart & Table Widgets
       ============================================================ */
    .fi-wi-chart,
    .fi-ta {
        border-radius: 0.875rem !important;
        overflow: hidden;
        border: 1px solid rgba(148, 163, 184, 0.2) !important;
    }

    html.dark .fi-wi-chart,
    html.dark .fi-ta {
        border-color: rgba(71, 85, 105, 0.5) !important;
    }

    .fi-section,
    .fi-wi-stats-overview {
        border-radius: 0.875rem !important;
    }

    /* ============================================================
       COMPONENT OVERRIDES: Sidebar Active Item
       ============================================================ */
    .fi-sidebar-item-button.fi-active {
        background-color: rgba(20, 184, 166, 0.1) !important;
        border-left: 3px solid #14b8a6 !important;
        padding-left: calc(0.75rem - 3px) !important;
    }

    html.dark .fi-sidebar-item-button.fi-active {
        background-color: rgba(20, 184, 166, 0.15) !important;
    }

    .fi-sidebar-item-button.fi-active .fi-sidebar-item-label {
        color: #0d9488 !important;
        font-weight: 600 !important;
    }

    html.dark .fi-sidebar-item-button.fi-active .fi-sidebar-item-label {
        color: #2dd4bf !important;
    }

    .fi-sidebar-item-button.fi-active .fi-sidebar-item-icon {
        color: #0d9488 !important;
    }

    html.dark .fi-sidebar-item-button.fi-active .fi-sidebar-item-icon {
        color: #2dd4bf !important;
    }

    /* ============================================================
       COMPONENT OVERRIDES: Global Border Radius
       ============================================================ */
    .fi-modal-content,
    .fi-dropdown-panel {
        border-radius: 0.875rem !important;
    }

    .fi-btn {
        border-radius: 0.625rem !important;
    }

    .fi-input-wrp {
        border-radius: 0.625rem !important;
    }

    /* ============================================================
       COMPONENT: Login Footer
       ============================================================ */
    .rs-login-footer {
        text-align: center;
        padding: 1rem 0 0;
        color: #94a3b8;
        font-size: 0.75rem;
        line-height: 1.25;
    }

    html.dark .rs-login-footer {
        color: #64748b;
    }

    .rs-login-footer-brand {
        font-weight: 600;
        color: #0d9488;
    }

    html.dark .rs-login-footer-brand {
        color: #2dd4bf;
    }
</style>
