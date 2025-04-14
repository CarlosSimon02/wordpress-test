(function($) {
  'use strict';
  
  function initPaymentHistory() {
      const container = $('.supafaya-payment-history');
      if (container.length === 0) {
          return;
      }
      
      if (supafayaPaymentHistory.userInfo) {
          const userInfoEl = $('<div class="user-info-display"></div>')
              .text(supafayaPaymentHistory.userInfo)
              .appendTo(container.find('.payment-history-header'));
      }
      
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
      
      const detailDialog = $('.payment-detail-dialog');
      const dialogOverlay = $('.payment-detail-dialog-overlay');
      const closeDialogButton = $('.close-dialog');
      const dialogContent = $('.dialog-content');
      
      let currentPage = supafayaPaymentHistory.currentPage || 1;
      let totalPages = 1;
      let isLoading = false;
      
      function loadPaymentHistory(page = 1) {
          if (isLoading) {
              return;
          }
          
          isLoading = true;
          showLoading();
          
          const requestData = {
              action: 'supafaya_load_payment_history',
              nonce: supafayaPaymentHistory.nonce,
              page: page,
              limit: supafayaPaymentHistory.limit,
              organization_id: supafayaPaymentHistory.organizationId
          };

          const cookieToken = getCookie('firebase_user_token');
          
          if (cookieToken) {
              sendAjaxRequest(cookieToken);
          } else {
              if (typeof firebase !== 'undefined') {
                  if (firebase.auth) {
                      const currentUser = firebase.auth().currentUser;
                      
                      if (currentUser) {
                          firebase.auth().currentUser.getIdToken(true)
                              .then(function(token) {
                                  sendAjaxRequest(token);
                              })
                              .catch(function(error) {
                                  if (cookieToken) {
                                      sendAjaxRequest(cookieToken);
                                  } else {
                                      isLoading = false;
                                      showError('Authentication error. Please log in again.');
                                  }
                              });
                      } else {
                          const unsubscribe = firebase.auth().onAuthStateChanged(function(user) {
                              unsubscribe();
                              
                              if (user) {
                                  user.getIdToken(true)
                                      .then(function(token) {
                                          sendAjaxRequest(token);
                                      })
                                      .catch(function(error) {
                                          isLoading = false;
                                          showError('Authentication error. Please try refreshing the page.');
                                      });
                              } else {
                                  isLoading = false;
                                  showError('Authentication required. Please log in to view payment history.');
                              }
                          });
                          
                          setTimeout(function() {
                              unsubscribe();
                              if (isLoading) {
                                  if (cookieToken) {
                                      sendAjaxRequest(cookieToken);
                                  } else {
                                      isLoading = false;
                                      showError('Authentication timed out. Please try refreshing the page.');
                                  }
                              }
                          }, 5000);
                      }
                  } else {
                      if (cookieToken) {
                          sendAjaxRequest(cookieToken);
                      } else {
                          isLoading = false;
                          showError('Authentication not available. Please try refreshing the page.');
                      }
                  }
              } else {
                  if (cookieToken) {
                      sendAjaxRequest(cookieToken);
                  } else {
                      isLoading = false;
                      showError('Authentication not available. Please try refreshing the page.');
                  }
              }
          }
          
          function sendAjaxRequest(token) {
              $.ajax({
                  url: supafayaPaymentHistory.ajaxUrl,
                  type: 'POST',
                  data: requestData,
                  beforeSend: function(xhr) {
                      xhr.setRequestHeader('X-Firebase-Token', token);
                  },
                  success: function(response) {
                      isLoading = false;
                      
                      if (response.success) {
                          currentPage = page;
                          
                          const responseData = response.data || {};
                          const data = responseData.data || {};
                          
                          let payments = [];
                          if (data.payments && Array.isArray(data.payments)) {
                              payments = data.payments;
                          } else if (responseData.payments && Array.isArray(responseData.payments)) {
                              payments = responseData.payments;
                          } else if (data.data && data.data.results && Array.isArray(data.data.results)) {
                              payments = data.data.results;
                          } else if (Array.isArray(data)) {
                              payments = data;
                          }
                          
                          const pagination = data.pagination || {};
                          
                          totalPages = Math.ceil(pagination.total / supafayaPaymentHistory.limit) || 1;
                          
                          if (payments.length > 0) {
                              renderPayments(payments);
                              updatePagination();
                              showContent();
                          } else {
                              showEmpty();
                          }
                      } else {
                          const errorMessage = response.message || 'Failed to load payment history';
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
                      showError('Connection error. Please try again later.', JSON.stringify(errorInfo, null, 2));
                  }
              });
          }
      }
      
      function getCookie(name) {
          const value = `; ${document.cookie}`;
          const parts = value.split(`; ${name}=`);
          if (parts.length === 2) return parts.pop().split(';').shift();
          return null;
      }
      
      function renderPayments(payments) {
          let html = '';
          
          payments.forEach(function(payment) {
              const status = getStatusDisplay(payment.status);
              const formattedDate = formatDate(payment.createdAt);
              const formattedAmount = formatCurrency(payment.amount, payment.currency);
              const eventName = payment.eventId || 'Unknown Event';
              
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
      }
      
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
      
      function formatDate(dateString) {
        if (!dateString) return 'Unknown date';
    
        const date = new Date(dateString);
        if (isNaN(date.getTime())) return 'Invalid date';
    
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
      
      function formatCurrency(amount, currency) {
          if (amount === undefined || amount === null) return 'N/A';
          
          const formatter = new Intl.NumberFormat('en-US', {
              style: 'currency',
              currency: currency || 'USD',
              minimumFractionDigits: 2
          });
          
          return formatter.format(amount);
      }
      
      function escapeHtml(unsafe) {
          if (!unsafe) return '';
          return unsafe
              .replace(/&/g, "&amp;")
              .replace(/</g, "&lt;")
              .replace(/>/g, "&gt;")
              .replace(/"/g, "&quot;")
              .replace(/'/g, "&#039;");
      }
      
      function updatePagination() {
          currentPageEl.text(currentPage);
          totalPagesEl.text(totalPages);
          
          prevButton.prop('disabled', currentPage <= 1);
          nextButton.prop('disabled', currentPage >= totalPages);
          
          paginationContainer.show();
      }
      
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
      
      retryButton.on('click', function() {
          loadPaymentHistory(currentPage);
      });
      
      refreshButton.on('click', function() {
          loadPaymentHistory(currentPage);
      });
      
      historyList.on('click', '.view-details-button', function() {
          const paymentId = $(this).data('id');
          openDetailDialog(paymentId);
      });
      
      closeDialogButton.on('click', closeDetailDialog);
      dialogOverlay.on('click', closeDetailDialog);
      
      $(document).on('keydown', function(e) {
          if (e.key === 'Escape' && detailDialog.is(':visible')) {
              closeDetailDialog();
          }
      });
      
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
          $('body').css('overflow', 'hidden');
          
          const paymentItem = historyList.find(`.payment-item[data-id="${paymentId}"]`);
          if (paymentItem.length) {
              const eventName = paymentItem.find('.payment-event').text();
              const date = paymentItem.find('.payment-time').text().trim();
              const amount = paymentItem.find('.payment-amount').text();
              const status = paymentItem.find('.payment-status').text();
              
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
      
      function closeDetailDialog() {
          detailDialog.css('display', 'none');
          $('body').css('overflow', '');
      }
      
      loadPaymentHistory(currentPage);
  }
  
  $(function() {
      initPaymentHistory();
  });
  
})(jQuery);
