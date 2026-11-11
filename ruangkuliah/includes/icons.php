<?php
/**
 * Ikon SVG (gaya garis, sama dengan template asli).
 * Pemakaian: <?= icon('bell') ?>
 */
function icon(string $name): string
{
    static $paths = [
        // dari template
        'brand'    => '<path d="M5 18V8.5L12 5l7 3.5V18M8 18v-5h8v5M3 19h18" stroke-width="1.8"/>',
        'grid'     => '<rect x="3.5" y="3.5" width="7" height="7" rx="1.5"/><rect x="13.5" y="3.5" width="7" height="7" rx="1.5"/><rect x="3.5" y="13.5" width="7" height="7" rx="1.5"/><rect x="13.5" y="13.5" width="7" height="7" rx="1.5"/>',
        'calendar' => '<rect x="3.5" y="5" width="17" height="15.5" rx="2"/><path d="M7.5 3.5v3M16.5 3.5v3M3.5 9.5h17M8 13h2M14 13h2M8 16.5h2"/>',
        'calendar-plain' => '<rect x="3.5" y="5" width="17" height="15.5" rx="2"/><path d="M7.5 3.5v3M16.5 3.5v3M3.5 9.5h17"/>',
        'book'     => '<path d="M4 5.5A2.5 2.5 0 0 1 6.5 3H20v16H6.5A2.5 2.5 0 0 0 4 21V5.5ZM4 17.5A2.5 2.5 0 0 1 6.5 15H20M8 7h8M8 10h8"/>',
        'book-sm'  => '<path d="M4 5.5A2.5 2.5 0 0 1 6.5 3H20v16H6.5A2.5 2.5 0 0 0 4 21V5.5ZM4 17.5A2.5 2.5 0 0 1 6.5 15H20"/>',
        'users'    => '<path d="M16 20v-1.5a3.5 3.5 0 0 0-3.5-3.5h-5A3.5 3.5 0 0 0 4 18.5V20M10 11a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7ZM16 4.2a3.5 3.5 0 0 1 0 6.7M17 15h.5a3.5 3.5 0 0 1 3.5 3.5V20"/>',
        'logout'   => '<path d="M10 4H5.5A1.5 1.5 0 0 0 4 5.5v13A1.5 1.5 0 0 0 5.5 20H10M15 16l4-4-4-4M19 12H9"/>',
        'print'    => '<path d="M7 8V4h10v4M7 17H5a2 2 0 0 1-2-2v-4a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v4a2 2 0 0 1-2 2h-2M7 14h10v6H7z"/>',
        'flask'    => '<path d="M9 3h6M10 3v6l-5 9a2 2 0 0 0 1.8 3h10.4a2 2 0 0 0 1.8-3l-5-9V3M8 15h8"/>',
        'minus'    => '<circle cx="12" cy="12" r="8.5"/><path d="M8.5 12h7"/>',

        // tambahan
        'bell'     => '<path d="M6 9a6 6 0 0 1 12 0c0 6 2.5 7.5 2.5 7.5h-17S6 15 6 9ZM10 20a2 2 0 0 0 4 0"/>',
        'plus'     => '<path d="M12 5v14M5 12h14"/>',
        'edit'     => '<path d="M4 20h4L19 9a2.1 2.1 0 0 0-3-3L5 17v3ZM14.5 7.5l3 3"/>',
        'trash'    => '<path d="M4 7h16M10 11v6M14 11v6M6 7l1 12a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2l1-12M9 7V4h6v3"/>',
        'user-plus'=> '<path d="M15 20v-1.5a3.5 3.5 0 0 0-3.5-3.5h-4A3.5 3.5 0 0 0 4 18.5V20M9.5 11a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7ZM19 8v6M16 11h6"/>',
        'check'    => '<path d="M5 12.5l4.5 4.5L19 7.5"/>',
        'info'     => '<circle cx="12" cy="12" r="8.5"/><path d="M12 11v5M12 8h.01"/>',
        'alert'    => '<circle cx="12" cy="12" r="8.5"/><path d="M12 7.5v5M12 16h.01"/>',
        'clock'    => '<circle cx="12" cy="12" r="8.5"/><path d="M12 7.5V12l3 2"/>',
        'arrow'    => '<path d="M5 12h14M13 6l6 6-6 6"/>',
        'x'        => '<path d="M6 6l12 12M18 6L6 18"/>',
    ];

    $inner = $paths[$name] ?? '';
    return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" '
         . 'stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $inner . '</svg>';
}
