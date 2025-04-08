(function($) {
    'use strict';
    
    function initProofOfPayment() {
        const eventPage = $('.supafaya-event-single');
        if (eventPage.length === 0) return;
        
        // Get current event ID from URL
        const urlParams = new URLSearchParams(window.location.search);
        const currentEventId = urlParams.get('event_id');
        
        if (!currentEventId) {
            console.error('Event ID not found in URL');
            return;
        }
        
        // Enable debug mode
        if (urlParams.has('debug') || localStorage.getItem('supafaya_debug') === 'true') {
            window.supafayaDebug = true;
            localStorage.setItem('supafaya_debug', 'true');
            console.log('Supafaya Debug Mode Enabled');
        }
        
        // Debug mode
        const debug = function(message, data) {
            if (window.supafayaDebug) {
                console.log('[Proof of Payment Debug] ' + message, data || '');
            }
        };

        // Append proof of payment button and dialog to the DOM
        function appendProofOfPaymentUI() {
            debug('Initializing Proof of Payment UI');
            
            // Create the button and append after checkout button
            const checkoutButton = eventPage.find('.checkout-button');
            if (checkoutButton.length === 0) {
                debug('Checkout button not found, cannot add proof of payment button');
                return;
            }
            
            // Create and insert the button
            const popButton = $(`
                <button class="proof-of-payment-button" disabled>
                    Send Proof of Payment
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M4 17l6-6-6-6"></path>
                        <path d="M12 3h7a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-7"></path>
                    </svg>
                </button>
            `);
            
            checkoutButton.after(popButton);
            debug('Proof of payment button added');
            
            // Create the dialog
            const dialog = $(`
                <div class="proof-of-payment-dialog" style="display: none;">
                    <div class="dialog-overlay"></div>
                    <div class="dialog-content">
                        <div class="dialog-header">
                            <h2>Send Proof of Payment</h2>
                            <button class="dialog-close">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <line x1="18" y1="6" x2="6" y2="18"></line>
                                    <line x1="6" y1="6" x2="18" y2="18"></line>
                                </svg>
                            </button>
                        </div>
                        <div class="dialog-body">
                            <div class="pop-two-column">
                                <div class="form-column">
                                    <form id="proof-of-payment-form">
                                        <div class="form-field">
                                            <label for="pop-name">Full Name *</label>
                                            <input type="text" id="pop-name" name="name" required>
                                        </div>
                                        
                                        <div class="form-field">
                                            <label for="pop-email">Email Address *</label>
                                            <input type="email" id="pop-email" name="email" required>
                                        </div>
                                        
                                        <div class="form-field">
                                            <label for="pop-phone">Phone Number *</label>
                                            <input type="tel" id="pop-phone" name="phone" required>
                                        </div>
                                        
                                        <div class="form-field">
                                            <label for="pop-reference">Reference/Transaction ID *</label>
                                            <input type="text" id="pop-reference" name="reference" required>
                                        </div>
                                        
                                        <div class="form-field">
                                            <label for="pop-bank">Bank/Payment Provider *</label>
                                            <input type="text" id="pop-bank" name="bank" required>
                                        </div>
                                        
                                        <div class="form-field">
                                            <label for="pop-amount">Amount Paid *</label>
                                            <input type="number" id="pop-amount" name="amount" step="0.01" required>
                                        </div>
                                        
                                        <div class="form-field">
                                            <label for="pop-date">Payment Date *</label>
                                            <input type="date" id="pop-date" name="date" required>
                                        </div>
                                        
                                        <div class="form-field file-upload">
                                            <label for="pop-receipt">Upload Receipt/Screenshot *</label>
                                            <input type="file" id="pop-receipt" name="receipt" accept="image/png, image/jpeg, image/jpg, application/pdf" required>
                                            <div class="file-preview"></div>
                                        </div>
                                        
                                        <div class="form-field">
                                            <label for="pop-notes">Additional Notes</label>
                                            <textarea id="pop-notes" name="notes"></textarea>
                                        </div>

                                        <div class="form-status"></div>
                                        
                                        <div class="form-actions">
                                            <button type="button" class="cancel-button">Cancel</button>
                                            <button type="submit" class="submit-button">Submit Proof</button>
                                        </div>
                                    </form>
                                </div>
                                <div class="cart-column">
                                    <div class="cart-summary">
                                        <h3>Your Order</h3>
                                        <div class="cart-items-list">
                                            <!-- Will be populated dynamically -->
                                        </div>
                                        <div class="cart-total">
                                            <span>Total:</span>
                                            <span class="pop-total-amount">฿0.00</span>
                                        </div>
                                        <div class="payment-instructions">
                                            <h4>Payment Instructions</h4>
                                            <p>If you wish to transact through your e-payment gateways, please send the total order value to the following accounts:</p>
                                            <div class="payment-methods">
                                                <div class="payment-method">
                                                    <strong>2C2P:</strong>
                                                    <span>####-####-####</span>
                                                </div>
                                                <div class="payment-method">
                                                    <strong>OpnPayments:</strong>
                                                    <span>####-####-####</span>
                                                </div>
                                                <div class="payment-method">
                                                    <strong>TrueMoney:</strong>
                                                    <span>####-####-####</span>
                                                </div>
                                                <div class="payment-method">
                                                    <strong>Rabbit LINE:</strong>
                                                    <span>####-####-####</span>
                                                </div>
                                            </div>
                                            <p class="payment-note">Please take a screenshot of the transaction in order for our team to verify the payment. We'll approve within 24 hours.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `);
            
            $('body').append(dialog);
            debug('Proof of payment dialog added to DOM');
        }
        
        // Initialize event listeners
        function initEventListeners() {
            debug('Initializing event listeners');
            
            // Open dialog when the button is clicked
            $(document).on('click', '.proof-of-payment-button', function(e) {
                e.preventDefault();
                openDialog();
            });
            
            // Close dialog when close button or overlay is clicked
            $(document).on('click', '.proof-of-payment-dialog .dialog-close, .proof-of-payment-dialog .dialog-overlay', function(e) {
                e.preventDefault();
                closeDialog();
            });
            
            // Close dialog when cancel button is clicked
            $(document).on('click', '.proof-of-payment-dialog .cancel-button', function(e) {
                e.preventDefault();
                closeDialog();
            });
            
            // File upload preview
            $(document).on('change', '#pop-receipt', function() {
                const fileInput = this;
                const preview = $(this).siblings('.file-preview');
                
                if (fileInput.files && fileInput.files[0]) {
                    const file = fileInput.files[0];
                    const reader = new FileReader();
                    
                    reader.onload = function(e) {
                        // Clear previous preview
                        preview.empty();
                        
                        // Create preview based on file type
                        if (file.type.match('image.*')) {
                            preview.html(`<img src="${e.target.result}" alt="Receipt preview" class="file-image-preview">`);
                        } else {
                            preview.html(`<div class="file-document-preview">${file.name}</div>`);
                        }
                    };
                    
                    reader.readAsDataURL(file);
                } else {
                    preview.empty();
                }
            });
            
            // Form submission
            $(document).on('submit', '#proof-of-payment-form', function(e) {
                e.preventDefault();
                submitProofOfPayment(this);
            });
            
            // Listen for cart updates to toggle proof of payment button state
            $(document).on('cart:updated', function() {
                debug('cart:updated event received');
                updateProofOfPaymentButton();
            });
            
            // Additional cart modification listeners
            $(document).on('click', '.add-to-cart, .add-addon-to-cart', function() {
                debug('Add to cart button clicked, updating button state in 500ms');
                setTimeout(updateProofOfPaymentButton, 1000);
            });
            
            $(document).on('change', '.ticket-quantity, .addon-quantity', function() {
                debug('Quantity changed, updating button state in 500ms');
                setTimeout(updateProofOfPaymentButton, 500);
            });
            
            // Monitor localStorage changes
            window.addEventListener('storage', function(e) {
                if (e.key === 'supafaya_carts') {
                    debug('localStorage cart data changed, updating button state');
                    updateProofOfPaymentButton();
                }
            });
            
            // Poll for cart changes periodically
            setInterval(updateProofOfPaymentButton, 2000);
        }
        
        // Update Proof of Payment button state based on cart contents
        function updateProofOfPaymentButton() {
            const cartData = getCurrentCartData();
            const popButton = $('.proof-of-payment-button');
            
            debug('Updating proof of payment button state', cartData);
            
            if (!popButton.length) {
                debug('Proof of payment button not found in DOM');
                return;
            }
            
            let hasItems = false;
            
            if (cartData) {
                // Check tickets
                if (cartData.tickets && Object.keys(cartData.tickets).length > 0) {
                    hasItems = true;
                    debug('Cart has tickets:', Object.keys(cartData.tickets).length);
                }
                
                // Check addons
                if (cartData.addons && Object.keys(cartData.addons).length > 0) {
                    hasItems = true;
                    debug('Cart has addons:', Object.keys(cartData.addons).length);
                }
            }
            
            // Update button state
            if (hasItems) {
                debug('Enabling proof of payment button');
                popButton.prop('disabled', false);
            } else {
                debug('Disabling proof of payment button');
                popButton.prop('disabled', true);
            }
        }
        
        // Open the dialog
        function openDialog() {
            debug('Opening proof of payment dialog');
            
            // Get cart data and populate the cart items section
            populateCartItems();
            
            $('.proof-of-payment-dialog').fadeIn(300);
            $('body').addClass('dialog-open');
            
            // If user is authenticated, pre-fill form with user data
            if (typeof firebase !== 'undefined' && firebase.auth && firebase.auth().currentUser) {
                const user = firebase.auth().currentUser;
                debug('User is authenticated, pre-filling form data', user.email);
                
                $('#pop-email').val(user.email);
                $('#pop-name').val(user.displayName || '');
                
                // Pre-fill current date
                const today = new Date().toISOString().split('T')[0];
                $('#pop-date').val(today);
            }
            
            // Pre-fill amount with current cart total
            const cartTotal = $('.total-amount').text().trim().replace(/[^0-9.]/g, '');
            if (cartTotal && !isNaN(parseFloat(cartTotal))) {
                $('#pop-amount').val(parseFloat(cartTotal));
            }
        }
        
        // Populate cart items in dialog
        function populateCartItems() {
            const cartData = getCurrentCartData();
            const cartItemsList = $('.cart-items-list');
            const popTotalAmount = $('.pop-total-amount');
            
            cartItemsList.empty();
            
            if (!cartData) {
                cartItemsList.html('<p class="empty-cart">Your cart is empty</p>');
                popTotalAmount.text('฿0.00');
                return;
            }
            
            let totalAmount = 0;
            let itemsHtml = '';
            
            // Add tickets to the list
            if (cartData.tickets && Object.keys(cartData.tickets).length > 0) {
                Object.keys(cartData.tickets).forEach(ticketId => {
                    const ticket = cartData.tickets[ticketId];
                    const quantity = ticket.quantity;
                    const price = ticket.price;
                    const subtotal = price * quantity;
                    
                    totalAmount += subtotal;
                    
                    itemsHtml += `
                        <div class="cart-item">
                            <div class="item-details">
                                <div class="item-name">${ticket.name}</div>
                                <div class="item-quantity">x${quantity}</div>
                            </div>
                            <div class="item-price">฿${subtotal.toFixed(2)}</div>
                        </div>
                    `;
                });
            }
            
            // Add addons to the list
            if (cartData.addons && Object.keys(cartData.addons).length > 0) {
                Object.keys(cartData.addons).forEach(addonId => {
                    const addon = cartData.addons[addonId];
                    const quantity = addon.quantity;
                    const price = addon.price;
                    const subtotal = price * quantity;
                    
                    totalAmount += subtotal;
                    
                    itemsHtml += `
                        <div class="cart-item addon-item">
                            <div class="item-details">
                                <div class="item-name">${addon.name} (Add-on)</div>
                                <div class="item-quantity">x${quantity}</div>
                            </div>
                            <div class="item-price">฿${subtotal.toFixed(2)}</div>
                        </div>
                    `;
                });
            }
            
            if (itemsHtml === '') {
                cartItemsList.html('<p class="empty-cart">Your cart is empty</p>');
            } else {
                cartItemsList.html(itemsHtml);
            }
            
            popTotalAmount.text(`฿${totalAmount.toFixed(2)}`);
        }
        
        // Close the dialog
        function closeDialog() {
            debug('Closing proof of payment dialog');
            $('.proof-of-payment-dialog').fadeOut(300);
            $('body').removeClass('dialog-open');
            
            // Reset form status
            $('.form-status').empty().removeClass('error success');
        }
        
        // Show form status message
        function showStatus(message, type) {
            const statusElement = $('.form-status');
            statusElement.removeClass('error success loading').addClass(type);
            
            if (type === 'loading') {
                statusElement.html(`
                    <div class="loading-spinner"></div>
                    <p>${message}</p>
                `);
            } else {
                statusElement.html(`<p>${message}</p>`);
            }
            
            statusElement.show();
        }
        
        // Submit proof of payment form
        function submitProofOfPayment(form) {
            const formStatus = $(form).find('.form-status');
            const submitButton = $(form).find('.submit-button');
            const cartData = getCurrentCartData();
            const formData = new FormData(form);
            
            // Add event ID and cart data to the form
            formData.append('event_id', currentEventId);
            formData.append('cart_data', JSON.stringify(cartData));
            formData.append('action', 'supafaya_proof_of_payment');
            formData.append('nonce', supafayaTickets.nonce);
            
            // Disable submit button and show loading message
            submitButton.prop('disabled', true).text('Submitting...');
            showStatus('Uploading proof of payment...', 'info');
            
            debug('Submitting proof of payment form', {
                formData: '(FormData object)',
                eventId: currentEventId,
                cartDataLength: JSON.stringify(cartData).length
            });
            
            $.ajax({
                url: supafayaTickets.ajaxUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    debug('Proof of payment response', response);
                    
                    if (response.success) {
                        // Success
                        const proofUrlDisplay = response.data?.proofUrl 
                            ? `<p>Your uploaded proof: <a href="${response.data.proofUrl}" target="_blank">View Image</a></p>` 
                            : '';
                            
                        showStatus(`
                            <h3>Payment Submitted!</h3>
                            <p>${response.message || 'Your proof of payment has been submitted successfully.'}</p>
                            <p>Your payment is now pending approval from the event organizer. Once approved, you will receive your tickets via email.</p>
                            <p>Payment ID: ${response.data?.paymentId || 'N/A'}</p>
                            <p>Status: ${response.data?.status || 'PENDING_APPROVAL'}</p>
                            ${proofUrlDisplay}
                        `, 'success');
                        
                        // Remove submit button
                        $(form).find('.form-actions').remove();
                        
                        // Clear cart
                        setTimeout(function() {
                            clearCart();
                        }, 1000);
                        
                        // Redirect after a delay if there's a redirect URL
                        if (response.data && response.data.redirect_url) {
                            setTimeout(function() {
                                window.location.href = response.data.redirect_url;
                            }, 3000);
                        }
                    } else {
                        // Error
                        submitButton.prop('disabled', false).text('Submit Proof');
                        showStatus(`
                            <h3>Error</h3>
                            <p>${response.message || 'Failed to submit proof of payment. Please try again.'}</p>
                        `, 'error');
                    }
                },
                error: function(xhr, status, error) {
                    debug('Proof of payment error', { xhr, status, error });
                    
                    // Enable submit button again
                    submitButton.prop('disabled', false).text('Submit Proof');
                    
                    // Show error
                    showStatus(`
                        <h3>Server Error</h3>
                        <p>An error occurred while submitting your payment proof. Please try again later.</p>
                        <p>Error: ${error}</p>
                    `, 'error');
                }
            });
        }
        
        // Get current cart data
        function getCurrentCartData() {
            // Check if we have a cart in localStorage
            try {
                debug('Getting cart data from localStorage');
                
                // Check if event ID is valid
                if (!currentEventId) {
                    debug('Error: Invalid event ID', currentEventId);
                    return null;
                }
                
                // Get all carts from localStorage
                const allCartsStr = localStorage.getItem('supafaya_carts');
                if (!allCartsStr) {
                    debug('No carts found in localStorage');
                    return null;
                }
                
                // Parse carts
                const allCarts = JSON.parse(allCartsStr);
                debug('All carts from localStorage', allCarts);
                
                // Get current event's cart
                const currentCart = allCarts[currentEventId];
                debug('Current event cart', currentCart);
                
                return currentCart || null;
            } catch (e) {
                debug('Error getting cart data', e);
                console.error('Error getting cart data', e);
                return null;
            }
        }
        
        // Clear the cart after successful submission
        function clearCart() {
            debug('Clearing cart');
            try {
                // Clear from localStorage
                const allCarts = JSON.parse(localStorage.getItem('supafaya_carts') || '{}');
                delete allCarts[currentEventId];
                localStorage.setItem('supafaya_carts', JSON.stringify(allCarts));
                
                // Update the UI to reflect empty cart
                $('.summary-items').empty();
                $('.total-amount').text('฿0.00');
                
                // Also update the in-memory cart object if it exists
                if (window.cart) {
                    window.cart.tickets = {};
                    window.cart.addons = {};
                    window.cart.total = 0;
                    debug('In-memory cart object cleared');
                }
                
                // Explicitly call updateOrderSummary to ensure checkout button is hidden
                if (typeof window.updateOrderSummary === 'function') {
                    window.updateOrderSummary();
                    debug('updateOrderSummary called to hide checkout button');
                } else {
                    // Fallback: manually hide the checkout button
                    $('.checkout-button').hide().prop('disabled', true);
                    debug('Manually hiding checkout button as fallback');
                }
                
                // Trigger cart:updated event to ensure all components update properly
                $(document).trigger('cart:updated');
                
                debug('Cart cleared and cart:updated event triggered');
            } catch (e) {
                debug('Error clearing cart', e);
            }
        }
        
        // Initialize the module
        function init() {
            debug('Initializing proof of payment module');
            appendProofOfPaymentUI();
            initEventListeners();
            updateProofOfPaymentButton(); // Set initial button state
        }
        
        // Run initialization
        init();
    }
    
    $(document).ready(function() {
        initProofOfPayment();
    });
    
})(jQuery); 