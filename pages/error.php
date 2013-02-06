<?php
/**
 * kkm
 * Error page
 * 
 * This page is displayed when a handler runs into an error.
 */
if (!defined('KKM_REQUEST') || KKM_REQUEST !== true) die;

echo('<div class="kkm_error_message">'.__('There was an error processing your request. Please go back to the previous page and try again.', 'kkm').'</div>');
?>