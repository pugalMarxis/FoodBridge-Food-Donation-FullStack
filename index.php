<?php
/**
 * FoodBridge — index.php (entry point)
 * Sends visitors to the right place. The public landing page (Screen 1)
 * will replace this redirect logic once it is built.
 */
require_once __DIR__ . '/includes/config.php';

if (is_logged_in()) {
    redirect(dashboard_for(current_role()));
}
redirect('login.php');