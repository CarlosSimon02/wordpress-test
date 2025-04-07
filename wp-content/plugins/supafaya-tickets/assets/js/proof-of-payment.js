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
                <button class="proof-of-payment-button">
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
                            <form id="proof-of-payment-form">
                                <div class="form-status"></div>
                                
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
                                
                                <div class="form-actions">
                                    <button type="button" class="cancel-button">Cancel</button>
                                    <button type="submit" class="submit-button">Submit Proof</button>
                                </div>
                            </form>
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
        }
        
        // Open the dialog
        function openDialog() {
            debug('Opening proof of payment dialog');
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
            debug('Submitting proof of payment form');
            
            // Validate form
            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }
            
            // Get cart data
            const cartData = getCurrentCartData();
            if (!cartData || Object.keys(cartData.tickets).length === 0 && Object.keys(cartData.addons).length === 0) {
                showStatus('Your cart is empty. Please add tickets or items to your cart before submitting payment proof.', 'error');
                return;
            }
            
            // Show loading state
            showStatus('Submitting your proof of payment...', 'loading');
            
            // Disable form
            const formElement = $(form);
            formElement.find('input, button, textarea').prop('disabled', true);
            
            // Create FormData object
            const formData = new FormData(form);
            formData.append('event_id', currentEventId);
            formData.append('action', 'supafaya_proof_of_payment');
            formData.append('nonce', supafayaTickets.nonce);
            
            // Add cart data to the form data
            formData.append('cart_data', JSON.stringify(cartData));
            
            // Submit the form
            $.ajax({
                url: supafayaTickets.ajaxUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    // Re-enable form
                    formElement.find('input, button, textarea').prop('disabled', false);
                    
                    if (response.success) {
                        // Show success message
                        showStatus('Proof of payment submitted successfully! We will verify your payment and update your order status.', 'success');
                        
                        // Clear the cart
                        clearCart();
                        
                        // Close dialog after a delay
                        setTimeout(function() {
                            closeDialog();
                            
                            // Redirect to success page if provided
                            if (response.data && response.data.redirect_url) {
                                window.location.href = response.data.redirect_url;
                            }
                        }, 3000);
                    } else {
                        // Show error message
                        showStatus(response.message || 'An error occurred while submitting your proof of payment. Please try again.', 'error');
                    }
                },
                error: function() {
                    // Re-enable form
                    formElement.find('input, button, textarea').prop('disabled', false);
                    
                    // Show error message
                    showStatus('An error occurred while connecting to the server. Please try again later.', 'error');
                }
            });
        }
        
        // Get current cart data
        function getCurrentCartData() {
            // Check if we have a cart in localStorage
            try {
                const allCarts = JSON.parse(localStorage.getItem('supafaya_carts') || '{}');
                return allCarts[currentEventId] || null;
            } catch (e) {
                debug('Error getting cart data', e);
                return null;
            }
        }
        
        // Clear the cart after successful submission
        function clearCart() {
            debug('Clearing cart');
            try {
                const allCarts = JSON.parse(localStorage.getItem('supafaya_carts') || '{}');
                delete allCarts[currentEventId];
                localStorage.setItem('supafaya_carts', JSON.stringify(allCarts));
                
                // Update the UI to reflect empty cart
                $('.summary-items').empty();
                $('.total-amount').text('₱0.00');
                $('.checkout-button').prop('disabled', true);
            } catch (e) {
                debug('Error clearing cart', e);
            }
        }
        
        // Initialize the module
        function init() {
            debug('Initializing proof of payment module');
            appendProofOfPaymentUI();
            initEventListeners();
        }
        
        // Run initialization
        init();
    }
    
    $(document).ready(function() {
        initProofOfPayment();
    });
    
})(jQuery); 