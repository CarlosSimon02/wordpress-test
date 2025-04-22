<?php
/**
 * Template for displaying payment result
 *
 * Available variables:
 * $status - The payment status (success, failed, cancelled, or unknown)
 * $transaction_id - The transaction ID if available
 * $event_id - The event ID if available
 */
// Get status from URL parameters
$status = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : 'unknown';
$event_id = isset($_GET['event_id']) ? sanitize_text_field($_GET['event_id']) : '';
$transaction_id = isset($_GET['transaction_id']) ? sanitize_text_field($_GET['transaction_id']) : '';

// If no transaction ID in URL but it's in session storage, use that
if (empty($transaction_id)) {
    echo '<script>
        if (sessionStorage.getItem("supafaya_transaction_id")) {
            var txnId = sessionStorage.getItem("supafaya_transaction_id");
            if (txnId) {
                // Update URL with transaction ID without reloading
                var url = new URL(window.location.href);
                url.searchParams.set("transaction_id", txnId);
                window.history.replaceState({}, "", url);
                
                // Set the transaction ID for PHP
                var txnIdElement = document.createElement("span");
                txnIdElement.id = "transaction_id_from_js";
                txnIdElement.style.display = "none";
                txnIdElement.textContent = txnId;
                document.body.appendChild(txnIdElement);
            }
        }
    </script>';
}

// Default content (will be overridden based on status)
$title = 'Payment Status';
$message = 'Your payment has been processed.';
$icon = '<svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>';
$button_text = 'Go Back';
$button_url = site_url();
$button_class = 'primary-button';
$status_class = 'status-unknown';
$show_support_button = false;

// Content based on status
switch ($status) {
    case 'success':
        $title = 'Payment Successful!';
        $message = 'Your ticket purchase was successful. You can view your tickets in your account.';
        $icon = '<svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="#4BB543" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>';
        $button_text = 'Back To Home';
        $button_url = site_url('/');
        $status_class = 'status-success';
        break;
    case 'failed':
        $title = 'Payment Failed';
        $message = 'Your payment could not be processed. Please try again or contact support if the problem persists.';
        $icon = '<svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="#FF3333" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>';
        $button_text = 'Try Again';
        $button_url = !empty($event_id) ? site_url('/?event_id=' . $event_id) : site_url();
        $status_class = 'status-error';
        $show_support_button = true;
        break;
    case 'cancelled':
        $title = 'Payment Cancelled';
        $message = 'Your payment was cancelled. No charges were made to your account.';
        $icon = '<svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="#FFA500" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="8" y1="12" x2="16" y2="12"></line></svg>';
        $button_text = 'Return to Event';
        $button_url = !empty($event_id) ? site_url('/?event_id=' . $event_id) : site_url();
        $status_class = 'status-warning';
        break;
    default:
        // Unknown status - use defaults
        $show_support_button = true;
        break;
}

// Add transaction ID to message if available
if (!empty($transaction_id)) {
    $message .= '<div class="transaction-info">Transaction ID: <span class="transaction-id">' . esc_html($transaction_id) . '</span></div>';
}
?>

<div class="payment-result-container <?php echo esc_attr($status_class); ?>">
    <div class="payment-result-card">
        <div class="status-icon">
            <?php echo $icon; ?>
        </div>
        <h2 class="status-title"><?php echo esc_html($title); ?></h2>
        <div class="status-message">
            <?php echo wp_kses_post($message); ?>
        </div>
        <div class="action-buttons">
            <a href="<?php echo esc_url($button_url); ?>" class="<?php echo esc_attr($button_class); ?>">
                <?php echo esc_html($button_text); ?>
            </a>
            
            <?php if ($show_support_button): ?>
            <button class="secondary-button contact-support-button">Contact Support</button>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
    .payment-result-container {
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
        max-width: 600px;
        margin: 40px auto;
        padding: 20px;
    }
    
    .payment-result-card {
        background: white;
        border-radius: 12px;
        box-shadow: 0 8px 30px rgba(0,0,0,0.08);
        padding: 40px;
        text-align: center;
        transition: all 0.3s ease;
    }
    
    .status-icon {
        margin-bottom: 24px;
    }
    
    .status-title {
        font-size: 28px;
        font-weight: 700;
        margin-bottom: 16px;
        color: #333;
    }
    
    .status-message {
        font-size: 16px;
        line-height: 1.6;
        color: #666;
        margin-bottom: 32px;
    }
    
    .transaction-info {
        margin-top: 16px;
        padding: 12px;
        background-color: #f8f9fa;
        border-radius: 8px;
        font-size: 14px;
        color: #666;
    }
    
    .transaction-id {
        font-family: monospace;
        font-weight: 600;
    }
    
    .action-buttons {
        display: flex;
        flex-direction: column;
        gap: 12px;
        align-items: center;
    }
    
    .primary-button, .secondary-button {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 180px;
        padding: 12px 24px;
        border-radius: 6px;
        font-weight: 600;
        font-size: 16px;
        text-decoration: none;
        cursor: pointer;
        transition: all 0.2s ease;
    }
    
    .primary-button {
        background-color: #8800FF;
        color: white;
        border: none;
    }
    
    .primary-button:hover {
        color: white !important;
        transform: translateY(-2px);
    }
    
    .secondary-button {
        background-color: transparent;
        color: #8800FF;
        border: 2px solid #8800FF;
    }
    
    .secondary-button:hover {
        background-color: transparent;
        border: 2px solid #8800FF !important;
        color: #8800FF !important;
        transform: translateY(-2px);
    }
    
    /* Status-specific styling */
    .status-success .status-title {
        color: #4BB543;
    }
    
    .status-error .status-title {
        color: #FF3333;
    }
    
    .status-warning .status-title {
        color: #FFA500;
    }
    
    /* Responsive design */
    @media (max-width: 768px) {
        .payment-result-container {
            padding: 16px;
            margin: 20px auto;
        }
        
        .payment-result-card {
            padding: 30px 20px;
        }
        
        .status-title {
            font-size: 24px;
        }
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Check for transaction ID added by JS
    var txnIdElement = document.getElementById('transaction_id_from_js');
    if (txnIdElement) {
        var txnId = txnIdElement.textContent;
        var statusMsg = document.querySelector('.status-message');
        
        // If transaction info doesn't already exist and we have a transaction ID
        if (txnId && !document.querySelector('.transaction-info') && statusMsg) {
            var txnInfo = document.createElement('div');
            txnInfo.className = 'transaction-info';
            txnInfo.innerHTML = 'Transaction ID: <span class="transaction-id">' + txnId + '</span>';
            statusMsg.appendChild(txnInfo);
        }
    }
    
    // Add event listener for the contact support button
    var supportBtn = document.querySelector('.contact-support-button');
    if (supportBtn) {
        supportBtn.addEventListener('click', function() {
            alert('Please contact our support team at support@example.com or call +1 (555) 123-4567 for assistance.');
        });
    }
    
    // Add special handling for cancelled payments
    if ('<?php echo esc_js($status); ?>' === 'cancelled') {
        var primaryButton = document.querySelector('.primary-button');
        if (primaryButton) {
            primaryButton.addEventListener('click', function(e) {
                // Clear cart for this event
                if (sessionStorage.getItem('supafaya_checkout_event_id')) {
                    var eventId = sessionStorage.getItem('supafaya_checkout_event_id');
                    try {
                        var allCarts = JSON.parse(localStorage.getItem('supafaya_carts') || '{}');
                        if (allCarts[eventId]) {
                            allCarts[eventId] = { tickets: {}, addons: {}, total: 0 };
                            localStorage.setItem('supafaya_carts', JSON.stringify(allCarts));
                        }
                    } catch (e) {
                        console.error('Error clearing cart:', e);
                    }
                }
            });
        }
    }
});
</script> 