<?php
/**
 * FoodBridge — logo.php
 * Heart + leaf brand mark (compassion + freshness), drawn as inline SVG.
 * Usage: fb_logo(36);  // size in px
 */
function fb_logo(int $size = 36): string
{
    return '<svg width="' . $size . '" height="' . $size . '" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">'
        . '<path d="M24 41C24 41 6 30 6 17.5C6 11.7 10.7 7 16.5 7C20 7 23 8.8 24 11.4C25 8.8 28 7 31.5 7C37.3 7 42 11.7 42 17.5C42 30 24 41 24 41Z" fill="#10B981"/>'
        . '<path d="M24 33C24 26 28 20 35 18C33 26 29 31 24 33Z" fill="#D1FAE5"/>'
        . '<path d="M24 33C24 26 20 20 13 18C15 26 19 31 24 33Z" fill="#ffffff" opacity="0.45"/>'
        . '</svg>';
}