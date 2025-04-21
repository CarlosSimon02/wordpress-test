(function($) {
    'use strict';
    
    // Create a global object to expose functions
    window.supafayaProofOfPayment = {};
    
    function initProofOfPayment() {
        const eventPage = $('.supafaya-event-single');
        if (eventPage.length === 0) return;
        
        const urlParams = new URLSearchParams(window.location.search);
        const currentEventId = urlParams.get('event_id');
        
        if (!currentEventId) {
            return;
        }

        function appendProofOfPaymentUI() {
            const checkoutButton = eventPage.find('.choose-payment-button, .checkout-button');
            if (checkoutButton.length === 0) {
                return;
            }
            
            const dialog = $(`
                <div class="proof-of-payment-dialog" style="display: none;">
                    <div class="dialog-overlay"></div>
                    <div class="dialog-content">
                        <div class="dialog-header">
                            <h2>Bank Transfer</h2>
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
                                            <p>If you wish to transact through your e-payment gateways, please send the total order value to the following account:</p>
                                            <div class="payment-methods">
                                               <img src="${supafayaTickets.pluginUrl}/assets/images/kasikornbank.png" alt="Kasikornbank" class="bank-logo" style="max-width: 200px; margin-bottom: 15px;">
                                                <div class="payment-method">
                                                    <strong>Bank:</strong>
                                                    <span>KASIKORNBANK</span>
                                                </div>
                                                <div class="payment-method">
                                                    <strong>Account Name:</strong>
                                                    <span>Kinnect Co.,LTD.</span>
                                                </div>
                                                <div class="payment-method">
                                                    <strong>Account Number:</strong>
                                                    <span>199-3-08772-2</span>
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
        }
        
        function initEventListeners() {
            // We no longer need this event since we're using the payment method dialog
            // $(document).on('click', '.proof-of-payment-button', function(e) {
            //     e.preventDefault();
            //     openDialog();
            // });
            
            $(document).on('click', '.proof-of-payment-dialog .dialog-close, .proof-of-payment-dialog .dialog-overlay', function(e) {
                e.preventDefault();
                closeDialog();
            });
            
            $(document).on('click', '.proof-of-payment-dialog .cancel-button', function(e) {
                e.preventDefault();
                closeDialog();
            });
            
            $(document).on('change', '#pop-receipt', function() {
                const fileInput = this;
                const preview = $(this).siblings('.file-preview');
                
                if (fileInput.files && fileInput.files[0]) {
                    const file = fileInput.files[0];
                    const reader = new FileReader();
                    
                    reader.onload = function(e) {
                        preview.empty();
                        
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
            
            $(document).on('submit', '#proof-of-payment-form', function(e) {
                e.preventDefault();
                submitProofOfPayment(this);
            });
            
            $(document).on('cart:updated', function() {
                updateProofOfPaymentButton();
            });
            
            $(document).on('click', '.add-to-cart, .add-addon-to-cart', function() {
                setTimeout(updateProofOfPaymentButton, 1000);
            });
            
            $(document).on('change', '.ticket-quantity, .addon-quantity', function() {
                setTimeout(updateProofOfPaymentButton, 500);
            });
            
            window.addEventListener('storage', function(e) {
                if (e.key === 'supafaya_carts') {
                    updateProofOfPaymentButton();
                }
            });
            
            setInterval(updateProofOfPaymentButton, 2000);
        }
        
        function updateProofOfPaymentButton() {
            getCurrentCartData();
        }
        
        function openDialog() {
            populateCartItems();
            
            $('.proof-of-payment-dialog').fadeIn(300);
            $('body').addClass('dialog-open');
            
            if (typeof firebase !== 'undefined' && firebase.auth && firebase.auth().currentUser) {
                const user = firebase.auth().currentUser;
                
                $('#pop-email').val(user.email);
                $('#pop-name').val(user.displayName || '');
                
                const today = new Date().toISOString().split('T')[0];
                $('#pop-date').val(today);
            }
            
            // Get cart total from the DOM or from the cart data
            const cartTotal = getCartTotal();
            if (cartTotal > 0) {
                $('#pop-amount').val(cartTotal);
            }
        }
        
        function getCartTotal() {
            // Try to get total from DOM first
            const domTotal = $('.total-amount').text().trim().replace(/[^0-9.]/g, '');
            if (domTotal && !isNaN(parseFloat(domTotal))) {
                return parseFloat(domTotal);
            }
            
            // If DOM total is not available, calculate from cart data
            const cartData = getCurrentCartData();
            if (!cartData) return 0;
            
            let total = 0;
            
            if (cartData.tickets) {
                Object.values(cartData.tickets).forEach(ticket => {
                    total += (ticket.price * ticket.quantity);
                });
            }
            
            if (cartData.addons) {
                Object.values(cartData.addons).forEach(addon => {
                    total += (addon.price * addon.quantity);
                });
            }
            
            return total;
        }
        
        function populateCartItems() {
            const cartData = getCurrentCartData();
            const cartItemsList = $('.cart-items-list');
            const popTotalAmount = $('.pop-total-amount');
            
            cartItemsList.empty();
            
            // Debug log to check cartData
            console.log('Cart data for proof of payment:', cartData);
            
            if (!cartData || 
                ((!cartData.tickets || Object.keys(cartData.tickets).length === 0) && 
                (!cartData.addons || Object.keys(cartData.addons).length === 0))) {
                cartItemsList.html('<p class="empty-cart">Your cart is empty</p>');
                popTotalAmount.text('฿0.00');
                return;
            }
            
            let totalAmount = 0;
            let itemsHtml = '';
            
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
        
        function closeDialog() {
            $('.proof-of-payment-dialog').fadeOut(300);
            $('body').removeClass('dialog-open');
            
            $('.form-status').empty().removeClass('error success');
        }
        
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
        
        function submitProofOfPayment(form) {
            const formStatus = $(form).find('.form-status');
            const submitButton = $(form).find('.submit-button');
            const cartData = getCurrentCartData();
            const formData = new FormData(form);
            
            formData.append('event_id', currentEventId);
            formData.append('cart_data', JSON.stringify(cartData));
            formData.append('action', 'supafaya_proof_of_payment');
            formData.append('nonce', supafayaTickets.nonce);
            
            submitButton.prop('disabled', true).text('Submitting...');
            showStatus('Uploading proof of payment...', 'info');
            
            if (typeof firebase !== 'undefined' && firebase.auth && firebase.auth().currentUser) {
                firebase.auth().currentUser.getIdToken(true)
                    .then(function(token) {
                        sendFormData(token);
                    })
                    .catch(function(error) {
                        sendFormData();
                    });
            } else {
                sendFormData();
            }
            
            function sendFormData(token) {
                const ajaxConfig = {
                    url: supafayaTickets.ajaxUrl,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.success) {
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
                            
                            $(form).find('.form-actions').remove();
                            
                            setTimeout(function() {
                                clearCart();
                            }, 1000);
                            
                            if (response.data && response.data.redirect_url) {
                                setTimeout(function() {
                                    window.location.href = response.data.redirect_url;
                                }, 3000);
                            }
                        } else {
                            submitButton.prop('disabled', false).text('Submit Proof');
                            showStatus(`
                                <h3>Error</h3>
                                <p>${response.message || 'Failed to submit proof of payment. Please try again.'}</p>
                            `, 'error');
                        }
                    },
                    error: function(xhr, status, error) {
                        submitButton.prop('disabled', false).text('Submit Proof');
                        
                        showStatus(`
                            <h3>Server Error</h3>
                            <p>An error occurred while submitting your payment proof. Please try again later.</p>
                            <p>Error: ${error}</p>
                        `, 'error');
                    }
                };
                
                if (token) {
                    ajaxConfig.beforeSend = function(xhr) {
                        xhr.setRequestHeader('X-Firebase-Token', token);
                    };
                }
                
                $.ajax(ajaxConfig);
            }
        }
        
        function getCurrentCartData() {
            try {
                if (!currentEventId) {
                    return null;
                }
                
                // Try to get cart from window.cart first (for active session)
                if (window.cart) {
                    if ((window.cart.tickets && Object.keys(window.cart.tickets).length > 0) || 
                        (window.cart.addons && Object.keys(window.cart.addons).length > 0)) {
                        return window.cart;
                    }
                }
                
                // Fallback to localStorage
                const allCartsStr = localStorage.getItem('supafaya_carts');
                if (!allCartsStr) {
                    return null;
                }
                
                const allCarts = JSON.parse(allCartsStr);
                const currentCart = allCarts[currentEventId];
                
                return currentCart || null;
            } catch (e) {
                console.error('Error getting cart data:', e);
                return null;
            }
        }
        
        function clearCart() {
            try {
                const allCarts = JSON.parse(localStorage.getItem('supafaya_carts') || '{}');
                delete allCarts[currentEventId];
                localStorage.setItem('supafaya_carts', JSON.stringify(allCarts));
                
                $('.summary-items').empty();
                $('.total-amount').text('฿0.00');
                
                if (window.cart) {
                    window.cart.tickets = {};
                    window.cart.addons = {};
                    window.cart.total = 0;
                }
                
                if (typeof window.updateOrderSummary === 'function') {
                    window.updateOrderSummary();
                } else {
                    $('.checkout-button').hide().prop('disabled', true);
                }
                
                $(document).trigger('cart:updated');
            } catch (e) {
            }
        }
        
        function init() {
            appendProofOfPaymentUI();
            initEventListeners();
            
            // Expose functions to the global scope
            window.supafayaProofOfPayment.openDialog = openDialog;
            window.supafayaProofOfPayment.populateCartItems = populateCartItems;
            window.supafayaProofOfPayment.closeDialog = closeDialog;
        }
        
        init();
    }
    
    $(document).ready(function() {
        initProofOfPayment();
    });
    
})(jQuery); 