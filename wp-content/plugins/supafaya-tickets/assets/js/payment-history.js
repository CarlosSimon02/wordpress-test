(function($) {
  'use strict';
  
  // Helper function for debug logging
  function debug(message, data) {
      console.log('Payment History Debug:', message, data !== undefined ? data : '');
      
      // Only log to console when debug mode is enabled
      // if (supafayaPaymentHistory && supafayaPaymentHistory.debug) {
      //     if (data !== undefined) {
      //         console.log('Payment History Debug:', message, data);
      //     } else {
      //         console.log('Payment History Debug:', message);
      //     }
      // }
  }
  
  // Main payment history functionality
  function initPaymentHistory() {
      const container = $('.supafaya-payment-history');
      if (container.length === 0) {
          debug('Payment history container not found');
          return;
      }
      
      debug('Initializing payment history', {
          organizationId: supafayaPaymentHistory.organizationId,
          limit: supafayaPaymentHistory.limit,
          page: supafayaPaymentHistory.currentPage
      });
      
      // Add user info if available
      if (supafayaPaymentHistory.userInfo) {
          const userInfoEl = $('<div class="user-info-display"></div>')
              .text(supafayaPaymentHistory.userInfo)
              .appendTo(container.find('.payment-history-header'));
          
          debug('Added user info to header');
      }
      
      // Elements
      const historyList = $('.payment-history-list');
      const emptyState = $('.payment-history-empty');
      const errorState = $('.payment-history-error');
      const loadingIndicator = $('.loading-indicator');
      const paginationContainer = $('.pagination-container');
      const prevButton = $('.prev-page');
      const nextButton = $('.next-page');
      const currentPageEl = $('.current-page');
      const totalPagesEl = $('.total-pages');
      const retryButton = $('.retry-button');
      const refreshButton = $('.refresh-button');
      
      // Payment detail dialog elements
      const detailDialog = $('.payment-detail-dialog');
      const dialogOverlay = $('.payment-detail-dialog-overlay');
      const closeDialogButton = $('.close-dialog');
      const dialogContent = $('.dialog-content');
      
      // Pagination state
      let currentPage = supafayaPaymentHistory.currentPage || 1;
      let totalPages = 1;
      let isLoading = false;
      
      // Load payment history
      function loadPaymentHistory(page = 1) {
          if (isLoading) {
              debug('Already loading, request ignored');
              return;
          }
          
          isLoading = true;
          showLoading();
          
          debug('Loading payment history for page ' + page);
          
          const requestData = {
              action: 'supafaya_load_payment_history',
              nonce: supafayaPaymentHistory.nonce,
              page: page,
              limit: supafayaPaymentHistory.limit,
              organization_id: supafayaPaymentHistory.organizationId
          };
          
          debug('Request payload', requestData);
          
          // Get Firebase token from cookie
          let firebaseToken = getCookie('firebase_user_token');
          debug('Firebase token available:', !!firebaseToken);
          
          $.ajax({
              url: supafayaPaymentHistory.ajaxUrl,
              type: 'POST',
              data: requestData,
              beforeSend: function(xhr) {
                  // Add Firebase token as a header if available
                  if (firebaseToken) {
                      xhr.setRequestHeader('X-Firebase-Token', firebaseToken);
                  }
              },
              success: function(response) {
                  isLoading = false;
                  debug('Response received', response);
                  
                  // Log full response structure
                  debug('Full response structure', JSON.stringify(response));
                  
                  if (response.success) {
                      // Update pagination state
                      currentPage = page;
                      
                      // Get data from response - Handle nested structure correctly
                      const responseData = response.data || {};
                      // The API response has data nested inside data
                      const data = responseData.data || {};
                      debug('Response data structure', data);
                      
                      // Try different locations where payments might be
                      let payments = [];
                      if (data.payments && Array.isArray(data.payments)) {
                          // Structure: response.data.data.payments - Correct path
                          payments = data.payments;
                          debug('Found payments at data.data.payments', payments.length + ' items');
                      } else if (responseData.payments && Array.isArray(responseData.payments)) {
                          // Fallback: response.data.payments
                          payments = responseData.payments;
                          debug('Found payments at data.payments', payments.length + ' items');
                      } else if (data.data && data.data.results && Array.isArray(data.data.results)) {
                          // Other possible structure
                          payments = data.data.results;
                          debug('Found payments at data.data.results', payments.length + ' items');
                      } else if (Array.isArray(data)) {
                          // Last resort if data itself is the array
                          payments = data;
                          debug('Found payments directly in data array', payments.length + ' items');
                      }
                      
                      const pagination = data.pagination || {};
                      
                      debug('Parsed response data', { 
                          payments: payments.length + ' items',
                          pagination: pagination
                      });
                      
                      totalPages = Math.ceil(pagination.total / supafayaPaymentHistory.limit) || 1;
                      
                      if (payments.length > 0) {
                          renderPayments(payments);
                          updatePagination();
                          showContent();
                      } else {
                          debug('No payments found');
                          showEmpty();
                      }
                  } else {
                      const errorMessage = response.message || 'Failed to load payment history';
                      debug('Error response', errorMessage);
                      showError(errorMessage, response.data?.error_details || '');
                  }
              },
              error: function(xhr, status, error) {
                  isLoading = false;
                  const errorInfo = {
                      xhr: xhr.responseText,
                      status: status,
                      error: error
                  };
                  debug('AJAX Error', errorInfo);
                  showError('Connection error. Please try again later.', JSON.stringify(errorInfo, null, 2));
              }
          });
      }
      
      // Helper function to get cookie value by name
      function getCookie(name) {
          const value = `; ${document.cookie}`;
          const parts = value.split(`; ${name}=`);
          if (parts.length === 2) return parts.pop().split(';').shift();
          return null;
      }
      
      // Render payment items
      function renderPayments(payments) {
          debug('Rendering ' + payments.length + ' payment items');
          let html = '';
          
          payments.forEach(function(payment) {
              // Format payment data
              const status = getStatusDisplay(payment.status);
              const formattedDate = formatDate(payment.createdAt);
              const formattedAmount = formatCurrency(payment.amount, payment.currency);
              const eventName = payment.eventId || 'Unknown Event';
              
              debug('Payment item data', {
                  id: payment.id,
                  status: status,
                  date: formattedDate,
                  amount: formattedAmount,
                  event: eventName
              });
              
              html += `
                  <div class="payment-item" data-id="${payment.id}" data-payment='${JSON.stringify(payment)}'>
                      <div class="payment-info">
                          <div class="payment-event">${escapeHtml(eventName)}</div>
                          <div class="payment-time">
                              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                  <circle cx="12" cy="12" r="10"></circle>
                                  <polyline points="12 6 12 12 16 14"></polyline>
                              </svg>
                              ${formattedDate}
                          </div>
                      </div>
                      <div class="payment-meta">
                          <div class="payment-amount">${`฿ ${payment.amount.toFixed(2)}`}</div>
                          <div class="payment-status status-${status.class}">${status.text}</div>
                          <button class="view-details-button" data-id="${payment.id}">
                              View Details
                              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                  <path d="M9 18l6-6-6-6"></path>
                              </svg>
                          </button>
                      </div>
                  </div>
              `;
          });
          
          historyList.html(html);
          debug('Payment items rendered');
      }
      
      // Get status display info
      function getStatusDisplay(status) {
          switch (status?.toLowerCase()) {
              case 'completed':
              case 'success':
                  return { text: 'Completed', class: 'completed' };
              case 'pending':
              case 'processing':
                  return { text: 'Pending', class: 'pending' };
              case 'failed':
                  return { text: 'Failed', class: 'failed' };
              case 'refunded':
                  return { text: 'Refunded', class: 'refunded' };
              default:
                  return { text: status || 'Unknown', class: 'pending' };
          }
      }
      
      // Format date
      function formatDate(dateString) {
        if (!dateString) return 'Unknown date';
    
        const date = new Date(dateString);
        if (isNaN(date.getTime())) return 'Invalid date';
    
        // Format the date in a more readable and consistent way
        return date.toLocaleString(undefined, {
            year: 'numeric',
            month: 'short',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
            hour12: true,
            timeZoneName: 'short'
        });
    }
      
      // Format currency
      function formatCurrency(amount, currency) {
          if (amount === undefined || amount === null) return 'N/A';
          
          const formatter = new Intl.NumberFormat('en-US', {
              style: 'currency',
              currency: currency || 'USD',
              minimumFractionDigits: 2
          });
          
          return formatter.format(amount);
      }
      
      // Escape HTML
      function escapeHtml(unsafe) {
          if (!unsafe) return '';
          return unsafe
              .replace(/&/g, "&amp;")
              .replace(/</g, "&lt;")
              .replace(/>/g, "&gt;")
              .replace(/"/g, "&quot;")
              .replace(/'/g, "&#039;");
      }
      
      // Update pagination controls
      function updatePagination() {
          currentPageEl.text(currentPage);
          totalPagesEl.text(totalPages);
          
          prevButton.prop('disabled', currentPage <= 1);
          nextButton.prop('disabled', currentPage >= totalPages);
          
          paginationContainer.show();
      }
      
      // Display states
      function showLoading() {
          loadingIndicator.show();
          historyList.find('.payment-item').hide();
          emptyState.hide();
          errorState.hide();
      }
      
      function showContent() {
          loadingIndicator.hide();
          emptyState.hide();
          errorState.hide();
      }
      
      function showEmpty() {
          loadingIndicator.hide();
          historyList.find('.payment-item').hide();
          emptyState.show();
          errorState.hide();
          paginationContainer.hide();
      }
      
      function showError(message, details) {
          loadingIndicator.hide();
          historyList.find('.payment-item').hide();
          emptyState.hide();
          errorState.show();
          errorState.find('p').text(message || 'An error occurred. Please try again.');
          
          const errorDetails = errorState.find('.error-details');
          if (details) {
              errorDetails.text(details).show();
          } else {
              errorDetails.hide();
          }
          
          paginationContainer.hide();
      }
      
      // Pagination event handlers
      prevButton.on('click', function() {
          if (currentPage > 1) {
              loadPaymentHistory(currentPage - 1);
          }
      });
      
      nextButton.on('click', function() {
          if (currentPage < totalPages) {
              loadPaymentHistory(currentPage + 1);
          }
      });
      
      // Retry button handler
      retryButton.on('click', function() {
          loadPaymentHistory(currentPage);
      });
      
      // Refresh button handler
      refreshButton.on('click', function() {
          loadPaymentHistory(currentPage);
      });
      
      // View details button handler
      historyList.on('click', '.view-details-button', function() {
          const paymentId = $(this).data('id');
          openDetailDialog(paymentId);
      });
      
      // Dialog handlers
      closeDialogButton.on('click', closeDetailDialog);
      dialogOverlay.on('click', closeDetailDialog);
      
      // Handle ESC key to close dialog
      $(document).on('keydown', function(e) {
          if (e.key === 'Escape' && detailDialog.is(':visible')) {
              closeDetailDialog();
          }
      });
      
      // Open payment detail dialog
      function openDetailDialog(paymentId) {
          dialogContent.html(`
              <div class="detail-loading">
                  <div class="spinner-container">
                      <div class="spinner"></div>
                  </div>
                  <span>Loading details...</span>
              </div>
          `);
          
          detailDialog.css('display', 'flex');
          $('body').css('overflow', 'hidden'); // Prevent scrolling
          
          // Find the payment in the DOM
          const paymentItem = historyList.find(`.payment-item[data-id="${paymentId}"]`);
          if (paymentItem.length) {
              // Get payment data from the DOM
              const eventName = paymentItem.find('.payment-event').text();
              const date = paymentItem.find('.payment-time').text().trim();
              const amount = paymentItem.find('.payment-amount').text();
              const status = paymentItem.find('.payment-status').text();
              
              // Find the payment in our current data
              const payment = historyList.find(`.payment-item[data-id="${paymentId}"]`).data('payment');
              if (!payment) {
                  dialogContent.html('<p>Payment details not found.</p>');
                  return;
              }
              
              const detailHtml = `
                  <div class="payment-detail-section">
                      <h4 class="detail-section-title">Payment Information</h4>
                      <div class="detail-row">
                          <div class="detail-label">Transaction ID</div>
                          <div class="detail-value">${payment.id}</div>
                      </div>
                      <div class="detail-row">
                          <div class="detail-label">Event</div>
                          <div class="detail-value">${eventName}</div>
                      </div>
                      <div class="detail-row">
                          <div class="detail-label">Date</div>
                          <div class="detail-value">${date}</div>
                      </div>
                      <div class="detail-row">
                          <div class="detail-label">Amount</div>
                          <div class="detail-value">${amount}</div>
                      </div>
                      <div class="detail-row">
                          <div class="detail-label">Status</div>
                          <div class="detail-value">${status}</div>
                      </div>
                      <div class="detail-row">
                          <div class="detail-label">Payment Method</div>
                          <div class="detail-value">${payment.method}</div>
                      </div>
                      <div class="detail-row">
                          <div class="detail-label">Provider</div>
                          <div class="detail-value">${payment.provider}</div>
                      </div>
                  </div>
                  
                  <div class="payment-detail-section">
                      <h4 class="detail-section-title">Purchased Items</h4>
                      ${payment.items.map(item => `
                          <div class="ticket-item">
                              <div class="ticket-name">${escapeHtml(item.name)}</div>
                              <div class="ticket-details">
                                  <span>${item.quantity} × ${item.type}</span>
                                  ${item.ticketType ? `<span class="ticket-type">${item.ticketType}</span>` : ''}
                                  <span>${formatCurrency(item.unitPrice, payment.currency)}</span>
                              </div>
                          </div>
                      `).join('')}
                  </div>
                  
                  <div class="payment-detail-section">
                      <h4 class="detail-section-title">Customer Information</h4>
                      <div class="detail-row">
                          <div class="detail-label">Name</div>
                          <div class="detail-value">${escapeHtml(payment.customer.name)}</div>
                      </div>
                      <div class="detail-row">
                          <div class="detail-label">Email</div>
                          <div class="detail-value">${escapeHtml(payment.customer.email)}</div>
                      </div>
                      ${payment.customer.phone ? `
                          <div class="detail-row">
                              <div class="detail-label">Phone</div>
                              <div class="detail-value">${escapeHtml(payment.customer.phone)}</div>
                          </div>
                      ` : ''}
                  </div>
              `;
              
              dialogContent.html(detailHtml);
          } else {
              dialogContent.html('<p>Payment details not found.</p>');
          }
      }
      
      // Close payment detail dialog
      function closeDetailDialog() {
          detailDialog.css('display', 'none');
          $('body').css('overflow', ''); // Restore scrolling
      }
      
      // Initial load
      debug('Starting initial load of payment history');
      loadPaymentHistory(currentPage);
  }
  
  // Initialize when document is ready
  $(function() {
      debug('Document ready, initializing payment history');
      initPaymentHistory();
  });
  
})(jQuery);
