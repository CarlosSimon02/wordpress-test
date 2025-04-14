(function($) {
    'use strict';
    
    function checkFirebaseAuth() {
        if (typeof firebase !== 'undefined' && firebase.auth) {
            const user = firebase.auth().currentUser;
            return !!user;
        }
        
        if (window.supafayaFirebase && typeof window.supafayaFirebase.isLoggedIn === 'function') {
            return window.supafayaFirebase.isLoggedIn();
        }
        
        return false;
    }

    function getFirebaseToken() {
        return new Promise((resolve, reject) => {
            if (typeof firebase !== 'undefined' && firebase.auth && firebase.auth().currentUser) {
                firebase.auth().currentUser.getIdToken(true)
                    .then(token => {
                        document.cookie = 'firebase_user_token=' + token + '; path=/; max-age=3600; SameSite=Lax';
                        resolve(token);
                    })
                    .catch(error => {
                        reject(error);
                    });
                return;
            }
            
            if (window.supafayaFirebase && typeof window.supafayaFirebase.getToken === 'function') {
                window.supafayaFirebase.getToken()
                    .then(resolve)
                    .catch(reject);
                return;
            }
            
            const match = document.cookie.match(new RegExp('(^| )firebase_user_token=([^;]+)'));
            if (match) {
                resolve(match[2]);
                return;
            }
            
            reject(new Error('Firebase authentication not available'));
        });
    }

    $.ajaxPrefilter(function(options, originalOptions, jqXHR) {
        if (options.url && options.url.indexOf(supafayaTickets.ajaxUrl) !== -1) {
            const oldBeforeSend = options.beforeSend;
            
            options.beforeSend = function(xhr) {
                if (oldBeforeSend) oldBeforeSend(xhr);
                
                getFirebaseToken()
                    .then(token => {
                        xhr.setRequestHeader('X-Firebase-Token', token);
                    })
                    .catch(error => {});
            };
        }
    });
    
    function initAuthForms() {
        $('#supafaya-connect-form').on('submit', function(e) {
            e.preventDefault();
            
            const email = $('#supafaya-email').val();
            const password = $('#supafaya-password').val();
            
            $('#connection-result').hide();
            
            $.ajax({
                url: supafayaTickets.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'supafaya_connect_account',
                    email: email,
                    password: password,
                    nonce: supafayaTickets.nonce
                },
                success: function(response) {
                    $('#connection-result')
                        .html(`<p class="${response.success ? 'success' : 'error'}">${response.message}</p>`)
                        .show();
                    
                    if (response.success) {
                        setTimeout(function() {
                            window.location.reload();
                        }, 2000);
                    }
                },
                error: function() {
                    $('#connection-result')
                        .html('<p class="error">An error occurred. Please try again.</p>')
                        .show();
                }
            });
        });
        
        $('#disconnect-account').on('click', function() {
            if (confirm('Are you sure you want to disconnect your Supafaya account?')) {
                $.ajax({
                    url: supafayaTickets.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'supafaya_disconnect_account',
                        nonce: supafayaTickets.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            window.location.reload();
                        } else {
                            alert(response.message || 'An error occurred');
                        }
                    }
                });
            }
        });
    }
    
    function initEventsListing() {
        $('.supafaya-events-grid').on('click', '.load-more-button', function() {
            const button = $(this);
            const organizationId = button.data('organization');
            const nextCursor = button.data('next-cursor');
            
            button.prop('disabled', true).text('Loading...');
            
            $.ajax({
                url: supafayaTickets.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'supafaya_load_events',
                    organization_id: organizationId,
                    next_cursor: nextCursor,
                    nonce: supafayaTickets.nonce
                },
                success: function(response) {
                    if (response.success) {
                        const events = response.data.data.results;
                        const pagination = response.data.data.pagination;
                        
                        if (!pagination.has_more) {
                            button.parent().remove();
                        } else {
                            button.data('next-cursor', pagination.next_page_cursor);
                            button.prop('disabled', false).text('Load More Events');
                        }
                        
                        events.forEach(function(event) {
                            appendEvent(event);
                        });
                    } else {
                        button.parent().html('<p class="error">Error: ' + (response.message || 'Failed to load more events') + '</p>');
                    }
                },
                error: function() {
                    button.parent().html('<p class="error">Error: Failed to connect to the server</p>');
                }
            });
        });
    }
    
    function initTicketCheckout() {
        const eventPage = $('.supafaya-event-single');
        if (eventPage.length === 0) return;

        const urlParams = new URLSearchParams(window.location.search);
        const currentEventId = urlParams.get('event_id');
        
        if (!currentEventId) {
            return;
        }

        const allCarts = loadCartsFromStorage() || {};
        
        if (!allCarts[currentEventId]) {
            allCarts[currentEventId] = {
                tickets: {},
                addons: {},
                total: 0
            };
        }
        
        const cart = allCarts[currentEventId];
        
        window.cart = cart;
        
        function saveCartsToStorage() {
            localStorage.setItem('supafaya_carts', JSON.stringify(allCarts));
            $(document).trigger('cart:updated');
        }
        
        function loadCartsFromStorage() {
            const savedCarts = localStorage.getItem('supafaya_carts');
            if (savedCarts) {
                try {
                    return JSON.parse(savedCarts);
                } catch (e) {
                    return null;
                }
            }
            return null;
        }

        $(document).off('click', '.ticket-item .quantity-decrease').on('click', '.ticket-item .quantity-decrease', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const input = $(this).siblings('.ticket-quantity');
            const currentVal = parseInt(input.val()) || 1;
            if (currentVal > 1) {
                input.val(currentVal - 1).trigger('change');
            }
        });

        $(document).off('click', '.ticket-item .quantity-increase').on('click', '.ticket-item .quantity-increase', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const input = $(this).siblings('.ticket-quantity');
            const currentVal = parseInt(input.val()) || 1;
            const max = parseInt(input.attr('max'));
            const effectiveMax = max === -1 ? 1000 : (max || 10);
            
            if (currentVal < effectiveMax) {
                input.val(currentVal + 1).trigger('change');
            }
        });

        $(document).off('click', '.addon-item .addon-quantity-decrease').on('click', '.addon-item .addon-quantity-decrease', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const input = $(this).siblings('.addon-quantity');
            const currentVal = parseInt(input.val()) || 1;
            if (currentVal > 1) {
                input.val(currentVal - 1).trigger('change');
            }
        });

        $(document).off('click', '.addon-item .addon-quantity-increase').on('click', '.addon-item .addon-quantity-increase', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const input = $(this).siblings('.addon-quantity');
            const currentVal = parseInt(input.val()) || 1;
            const max = parseInt(input.attr('max'));
            const effectiveMax = max === -1 ? 1000 : (max || 10);
            
            if (currentVal < effectiveMax) {
                input.val(currentVal + 1).trigger('change');
            }
        });

        $(document).off('change', '.ticket-quantity, .addon-quantity').on('change', '.ticket-quantity, .addon-quantity', function() {
            const input = $(this);
            let currentVal = parseInt(input.val()) || 1;
            const max = parseInt(input.attr('max'));
            const effectiveMax = max === -1 ? 1000 : (max || 10);
            
            if (currentVal < 1) currentVal = 1;
            if (currentVal > effectiveMax) currentVal = effectiveMax;
            
            if (currentVal !== parseInt(input.val())) {
                input.val(currentVal);
            }
        });

        $(document).on('click', '.add-to-cart:not(.add-addon-to-cart)', function() {
            const ticketItem = $(this).closest('.ticket-item');
            const ticketId = $(this).data('ticket-id');
            const ticketName = ticketItem.find('.ticket-name').text();
            const ticketPrice = parseFloat(ticketItem.find('.ticket-price').text().replace('฿', '').replace('$', '').replace(',', ''));
            const quantityToAdd = parseInt(ticketItem.find('.ticket-quantity').val() || 1);
            
            addTicketToCart(ticketId, quantityToAdd, {
                name: ticketName,
                price: ticketPrice
            });

            ticketItem.find('.ticket-quantity').val(1);
            
            const $button = $(this);
            
            if (!$button.data('original-text')) {
                $button.data('original-text', $button.html());
            }
            
            $button.html('<span>Added</span> <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>');
            
            setTimeout(() => {
                $button.html($button.data('original-text'));
            }, 1000);
            
            const $notification = $('<div class="cart-notification">Added to cart</div>');
            $('body').append($notification);
            setTimeout(() => $notification.addClass('show'), 10);
            setTimeout(() => {
                $notification.removeClass('show');
                setTimeout(() => $notification.remove(), 300);
            }, 1500);
        });
        
        $(document).on('click', '.add-addon-to-cart', function() {
            const addonItem = $(this).closest('.addon-item');
            const addonId = $(this).data('addon-id');
            const addonName = addonItem.find('.ticket-name').text();
            const addonPrice = parseFloat(addonItem.find('.ticket-price').text().replace('฿', '').replace('$', '').replace(',', ''));
            const quantityToAdd = parseInt(addonItem.find('.addon-quantity, .ticket-quantity').val() || 1);

            addAddonToCart(addonId, quantityToAdd, {
                name: addonName,
                price: addonPrice
            });

            addonItem.find('.addon-quantity, .ticket-quantity').val(1);
            
            const $button = $(this);
            
            if (!$button.data('original-text')) {
                $button.data('original-text', $button.html());
            }
            
            $button.html('<span>Added</span> <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>');
            
            setTimeout(() => {
                $button.html($button.data('original-text'));
            }, 1000);

            const $notification = $('<div class="cart-notification">Added to cart</div>');
            $('body').append($notification);
            setTimeout(() => $notification.addClass('show'), 10);
            setTimeout(() => {
                $notification.removeClass('show');
                setTimeout(() => $notification.remove(), 300);
            }, 1500);
        });
        
        function removeItemFromCart(itemId, isAddon) {
            if (isAddon) {
                delete cart.addons[itemId];
            } else {
                delete cart.tickets[itemId];
            }
            
            updateCartTotal();
            
            updateOrderSummary();
            
            saveCartsToStorage();
            
            $(document).trigger('cart:updated');
        }

        $(document).on('click', '.checkout-button', function() {
            if (!checkFirebaseAuth()) {
                document.cookie = 'supafaya_checkout_redirect=' + window.location.href + '; path=/; max-age=3600';
                
                window.location.href = supafayaTickets.loginUrl;
                return;
            }
            
            const tickets = [];
            const addons = [];

            for (const id in cart.tickets) {
                tickets.push({
                    ticket_id: id,
                    quantity: cart.tickets[id].quantity,
                    name: cart.tickets[id].name,
                    price: cart.tickets[id].price,
                    description: cart.tickets[id].description || '',
                    type: cart.tickets[id].type || 'regular'
                });
            }

            for (const id in cart.addons) {
                addons.push({
                    addon_id: id,
                    quantity: cart.addons[id].quantity,
                    name: cart.addons[id].name,
                    price: cart.addons[id].price,
                    ticket_id: cart.addons[id].ticketId || ''
                });
            }

            const phoneNumber = $('#customer_phone').val() || '';
            
            const currentUser = firebase.auth().currentUser;
            let userEmail = '';
            let userName = '';
            
            if (currentUser) {
                userEmail = currentUser.email || '';
                userName = currentUser.displayName || '';
            }

            if (!currentEventId) {
                alert('Event ID not found in URL');
                return;
            }

            if (!userEmail) {
                alert('Cannot proceed without user email. Please ensure you are properly logged in.');
                return;
            }

            $(this).prop('disabled', true).text('Processing...');

            const firebaseToken = getFirebaseToken();
            
            $.ajax({
                url: supafayaTickets.ajaxUrl,
                type: 'POST',
                headers: {
                    'X-Firebase-Token': firebaseToken
                },
                data: {
                    action: 'supafaya_purchase_ticket',
                    nonce: supafayaTickets.nonce,
                    event_id: currentEventId,
                    tickets: tickets,
                    addons: addons,
                    phone: phoneNumber,
                    email: userEmail,
                    name: userName,
                    firebase_token: firebaseToken,
                    payment_redirect_urls: {
                        success: supafayaTickets.paymentResultUrl ? 
                            (window.location.protocol + '//' + window.location.host + supafayaTickets.paymentResultUrl + 
                            (supafayaTickets.paymentResultUrl.includes('?') ? '&' : '?') + 
                            'status=success&event_id=' + currentEventId) : '',
                        failed: supafayaTickets.paymentResultUrl ? 
                            (window.location.protocol + '//' + window.location.host + supafayaTickets.paymentResultUrl + 
                            (supafayaTickets.paymentResultUrl.includes('?') ? '&' : '?') + 
                            'status=failed&event_id=' + currentEventId) : '',
                        cancel: window.location.href
                    }
                },
                success: function(response) {
                    $('.checkout-button').prop('disabled', false).text('Proceed to Checkout');

                    if (response.success) {
                        allCarts[currentEventId] = {
                            tickets: {},
                            addons: {},
                            total: 0
                        };
                        saveCartsToStorage();
                        
                        if (response.data && response.data.checkoutUrl) {
                            sessionStorage.setItem('supafaya_checkout_event_id', currentEventId);
                            if (response.data.id) {
                                sessionStorage.setItem('supafaya_transaction_id', response.data.id);
                            }
                            
                            window.location.href = response.data.checkoutUrl;
                            return;
                        }
                        
                        if (supafayaTickets.paymentResultUrl) {
                            const successUrl = supafayaTickets.paymentResultUrl + (supafayaTickets.paymentResultUrl.includes('?') ? '&' : '?') + 
                                'status=success&event_id=' + currentEventId + 
                                (response.data.id ? '&transaction_id=' + response.data.id : '');
                            window.location.href = successUrl;
                            return;
                        }
                        
                        $('.event-right-column').html(`
                            <div class="checkout-success">
                                <h3>Thank you for your purchase!</h3>
                                <p>${response.message || 'Your tickets have been booked successfully.'}</p>
                                <p>Reference: ${response.data?.id || ''}</p>
                                <p><a href="/my-tickets" class="view-tickets-button">View My Tickets</a></p>
                            </div>
                        `);
                    } else {
                        if (supafayaTickets.paymentResultUrl) {
                            const failureUrl = supafayaTickets.paymentResultUrl + (supafayaTickets.paymentResultUrl.includes('?') ? '&' : '?') + 
                                'status=failed&event_id=' + currentEventId + 
                                (response.data && response.data.id ? '&transaction_id=' + response.data.id : '');
                            window.location.href = failureUrl;
                            return;
                        }
                        
                        alert('Error: ' + (response.message || 'An error occurred'));
                    }
                },
                error: function(xhr, status, error) {
                    $('.checkout-button').prop('disabled', false).text('Proceed to Checkout');
                    
                    if (supafayaTickets.paymentResultUrl) {
                        const failureUrl = supafayaTickets.paymentResultUrl + (supafayaTickets.paymentResultUrl.includes('?') ? '&' : '?') + 
                            'status=failed&event_id=' + currentEventId;
                        window.location.href = failureUrl;
                        return;
                    }
                    
                    alert('A network error occurred. Please try again.');
                }
            });
        });

        function updateOrderSummary() {
            const summaryContainer = $('.summary-items');
            let html = '';
            let total = 0;
            let totalItems = 0;

            for (const id in cart.tickets) {
                const ticket = cart.tickets[id];
                const itemTotal = ticket.price * ticket.quantity;
                total += itemTotal;
                totalItems += ticket.quantity;

                html += `
                    <div class="summary-item" data-id="${id}" data-type="ticket">
                        <div class="item-info">
                            <span class="item-name">${ticket.name}</span>
                            <span class="item-quantity">x${ticket.quantity}</span>
                        </div>
                        <div class="item-price">฿${itemTotal.toFixed(2)}</div>
                        <button class="remove-item" title="Remove item">×</button>
                    </div>
                `;
            }

            for (const id in cart.addons) {
                const addon = cart.addons[id];
                const itemTotal = addon.price * addon.quantity;
                total += itemTotal;
                totalItems += addon.quantity;

                html += `
                    <div class="summary-item" data-id="${id}" data-type="addon">
                        <div class="item-info">
                            <span class="item-name">${addon.name} (Add-on)</span>
                            <span class="item-quantity">x${addon.quantity}</span>
                        </div>
                        <div class="item-price">฿${itemTotal.toFixed(2)}</div>
                        <button class="remove-item" title="Remove item">×</button>
                    </div>
                `;
            }

            if (totalItems === 0) {
                html = '<div class="empty-cart-message">Your cart is empty</div>';
            }

            summaryContainer.html(html);
            $('.total-amount').text(`฿${total.toFixed(2)}`);
            cart.total = total;
            
            if ($('.cart-count').length) {
                $('.cart-count').text(totalItems > 0 ? totalItems : '');
            }
            
            if (totalItems > 0) {
                $('.checkout-button').show().prop('disabled', false);
            } else {
                $('.checkout-button').hide().prop('disabled', true);
            }
        }

        window.updateOrderSummary = updateOrderSummary;

        $(document).on('click', '.remove-item', function() {
            const item = $(this).closest('.summary-item');
            const id = item.data('id');
            const type = item.data('type');
            
            removeItemFromCart(id, type === 'addon');
        });
        
        $(document).on('click', '.clear-cart', function() {
            cart.tickets = {};
            cart.addons = {};
            cart.total = 0;
            
            updateOrderSummary();
            
            saveCartsToStorage();
            
            $(document).trigger('cart:updated');
        });

        updateOrderSummary();
        
        function updateCartTotal() {
            let total = 0;
            
            for (const id in cart.tickets) {
                const ticket = cart.tickets[id];
                total += ticket.price * ticket.quantity;
            }
            
            for (const id in cart.addons) {
                const addon = cart.addons[id];
                total += addon.price * addon.quantity;
            }
            
            cart.total = total;
            
            $('.total-amount').text(`฿${total.toFixed(2)}`);
        }
        
        function addTicketToCart(ticketId, quantity, ticketInfo) {
            if (cart.tickets[ticketId]) {
                cart.tickets[ticketId].quantity += quantity;
            } else {
                cart.tickets[ticketId] = {
                    id: ticketId,
                    quantity: quantity,
                    price: ticketInfo.price,
                    name: ticketInfo.name
                };
            }
            
            updateCartTotal();
            
            updateOrderSummary();
            
            saveCartsToStorage();
            
            $(document).trigger('cart:updated');
        }
        
        function addAddonToCart(addonId, quantity, addonInfo, ticketId) {
            if (cart.addons[addonId]) {
                cart.addons[addonId].quantity += quantity;
            } else {
                cart.addons[addonId] = {
                    id: addonId,
                    quantity: quantity,
                    price: addonInfo.price,
                    name: addonInfo.name,
                    ticket_id: ticketId
                };
            }
            
            updateCartTotal();
            
            updateOrderSummary();
            
            saveCartsToStorage();
            
            $(document).trigger('cart:updated');
        }
    }
    
    function initAdminSettings() {
        $('#test-connection').on('click', function() {
            const $button = $(this);
            const $result = $('#connection-test-result');
            
            $button.prop('disabled', true).text('Testing...');
            $result.html('<p>Testing connection...</p>');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'supafaya_test_connection',
                    nonce: supafayaTickets.nonce
                },
                success: function(response) {
                    $button.prop('disabled', false).text('Test Connection');
                    
                    if (response.success) {
                        $result.html(`<p class="success">Connection successful! API version: ${response.data?.version || 'Unknown'}</p>`);
                    } else {
                        $result.html(`<p class="error">Connection failed: ${response.message || 'Unknown error'}</p>`);
                    }
                },
                error: function() {
                    $button.prop('disabled', false).text('Test Connection');
                    $result.html('<p class="error">A network error occurred. Please check your API URL.</p>');
                }
            });
        });
    }
    
    function appendEvent(event) {
        const container = $('.supafaya-events-grid .events-container');
        
        let imageHtml = '';
        if (event.poster_image) {
            imageHtml = `
                <div class="event-image">
                    <img src="${event.poster_image}" alt="${event.title}" loading="lazy">
                    <div class="event-date-badge">
                        <span class="event-day">${new Date(event.start_date).getDate()}</span>
                        <span class="event-month">${new Date(event.start_date).toLocaleString('default', { month: 'short' })}</span>
                    </div>
                </div>
            `;
        } else if (event.banner_image_url) {
            imageHtml = `
                <div class="event-image">
                    <img src="${event.banner_image_url}" alt="${event.title}" loading="lazy">
                    <div class="event-date-badge">
                        <span class="event-day">${new Date(event.start_date).getDate()}</span>
                        <span class="event-month">${new Date(event.start_date).toLocaleString('default', { month: 'short' })}</span>
                    </div>
                </div>
            `;
        }
        
        let locationHtml = '';
        if (event.location) {
            locationHtml = `
                <div class="meta-item">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                        <circle cx="12" cy="10" r="3"></circle>
                    </svg>
                    <span>${event.location}</span>
                </div>
            `;
        }
        
        let descriptionHtml = '';
        if (event.description) {
            const words = event.description.split(' ');
            const truncated = words.length > 15 ? words.slice(0, 15).join(' ') + '...' : event.description;
            descriptionHtml = `<div class="event-description">${truncated}</div>`;
        }
        
        const eventPageUrl = window.location.href.split('?')[0];
        
        const eventHtml = `
            <div class="event-card">
                ${imageHtml}
                <div class="event-content">
                    <div class="event-category">Event</div>
                    <h3 class="event-title">
                        <a href="${eventPageUrl}?event_id=${event.id}">
                            ${event.title}
                        </a>
                    </h3>
                    <div class="event-meta">
                        ${locationHtml}
                        <div class="meta-item">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"></circle>
                                <polyline points="12 6 12 12 16 14"></polyline>
                            </svg>
                            <span>${new Date(event.start_date).toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' })}</span>
                        </div>
                    </div>
                    ${descriptionHtml}
                    <div class="event-footer">
                        <a href="${eventPageUrl}?event_id=${event.id}" class="event-button">
                            View Details
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="5" y1="12" x2="19" y2="12"></line>
                                <polyline points="12 5 19 12 12 19"></polyline>
                            </svg>
                        </a>
                    </div>
                </div>
            </div>
        `;
        
        container.append(eventHtml);
    }
    
    $(function() {
        $('head').append('<link rel="stylesheet" type="text/css" href="' + supafayaTickets.pluginUrl + 'assets/css/supafaya-tickets.css">');
        
        initAuthForms();
        initEventsListing();
        initTicketCheckout();
        
        if (typeof ajaxurl !== 'undefined') {
            initAdminSettings();
        }
    });
    
})(jQuery);