<?php
/**
 * WHMCS Action Hook: Cancel Order on Invoice Cancelled by ZIGetik Webservices
 *
 * @package     WHMCS
 * @copyright   ZIGetik Webservices
 * @link        https://zigetik.com
 * @author      ZIGetik Webservices <kontakt@zigetik.com>
 * @version     2.0
 * @compatible  WHMCS 9.0+ | PHP 8.2+
 *
 * Original concept by Katamaze
 * Enhanced and secured by ZIGetik Webservices
 */

use WHMCS\Database\Capsule;

add_hook('InvoiceCancelled', 1, function($vars) {
    // Validierung: Invoice ID muss vorhanden sein
    if (empty($vars['invoiceid'])) {
        logActivity('Cancel Order Hook: No invoice ID provided');
        return;
    }
    
    try {
        // Suche Order ID zur Invoice
        $order = Capsule::table('tblorders')
            ->where('invoiceid', $vars['invoiceid'])
            ->select('id', 'userid')
            ->first();
        
        if (!$order) {
            // Keine Order gefunden - normaler Fall bei manuell erstellten Rechnungen
            logActivity("Cancel Order Hook: No order found for invoice #{$vars['invoiceid']}");
            return;
        }
        
        // Order über API stornieren
        $result = localAPI('CancelOrder', [
            'orderid' => $order->id
        ]);
        
        // Erfolgreiches Logging
        if ($result['result'] === 'success') {
            logActivity(
                "Order #{$order->id} automatically cancelled due to invoice #{$vars['invoiceid']} cancellation",
                $order->userid
            );
        } else {
            // Fehler beim Stornieren
            $errorMsg = $result['message'] ?? 'Unknown error';
            logActivity(
                "Cancel Order Hook Error: Failed to cancel order #{$order->id} - {$errorMsg}",
                $order->userid
            );
        }
        
    } catch (Exception $e) {
        // Exception-Handling
        logActivity('Cancel Order Hook Exception: ' . $e->getMessage());
    }
});
