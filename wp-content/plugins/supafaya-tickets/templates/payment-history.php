<?php
/**
 * Template for displaying user payment history
 * 
 * This template displays the user's payment history with pagination and
 * provides a button to view detailed information for each transaction.
 */
?>

<div class="supafaya-payment-history">
  <div class="payment-history-header">
    <h2>Payment History</h2>
    <div class="filter-section">
      <?php if (!empty($atts['organization_id'])): ?>
        <div class="organization-filter">
          <span class="filter-label">Organization:</span>
          <span class="filter-value"><?php echo esc_html($atts['organization_id']); ?></span>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <div class="payment-history-content">
    <div class="payment-history-list">
      <!-- Payments will be loaded here via AJAX -->
      <div class="loading-indicator">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="spinner">
          <circle cx="12" cy="12" r="10"></circle>
          <path d="M12 2a10 10 0 0 1 10 10"></path>
        </svg>
        <span>Loading payment history...</span>
      </div>
    </div>
    
    <div class="payment-history-empty" style="display: none;">
      <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M21 5H3a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h18a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2Z"></path>
        <path d="M4 17h16"></path>
        <path d="M4 12h16"></path>
        <path d="M7 5V3"></path>
        <path d="M17 5V3"></path>
      </svg>
      <p>No payment history found.</p>
    </div>
    
    <div class="payment-history-error" style="display: none;">
      <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="#FF3333" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <circle cx="12" cy="12" r="10"></circle>
        <line x1="15" y1="9" x2="9" y2="15"></line>
        <line x1="9" y1="9" x2="15" y2="15"></line>
      </svg>
      <p>Failed to load payment history. Please try again later.</p>
      <div class="error-details" style="display: none;"></div>
      <button class="retry-button">Retry</button>
    </div>
    
    <div class="pagination-container" style="display: none;">
      <div class="pagination-controls">
        <button class="pagination-button prev-page" disabled>
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="m15 18-6-6 6-6"></path>
          </svg>
          <span>Previous</span>
        </button>
        <div class="pagination-info">
          Page <span class="current-page">1</span> of <span class="total-pages">1</span>
        </div>
        <button class="pagination-button next-page" disabled>
          <span>Next</span>
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="m9 18 6-6-6-6"></path>
          </svg>
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Payment Detail Dialog -->
<div class="payment-detail-dialog" style="display: none;">
  <div class="payment-detail-dialog-overlay"></div>
  <div class="payment-detail-dialog-content">
    <div class="dialog-header">
      <h3>Payment Details</h3>
      <button class="close-dialog">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <line x1="18" y1="6" x2="6" y2="18"></line>
          <line x1="6" y1="6" x2="18" y2="18"></line>
        </svg>
      </button>
    </div>
    <div class="dialog-content">
      <!-- Payment details will be loaded here -->
      <div class="detail-loading">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="spinner">
          <circle cx="12" cy="12" r="10"></circle>
          <path d="M12 2a10 10 0 0 1 10 10"></path>
        </svg>
        <span>Loading details...</span>
      </div>
    </div>
  </div>
</div>

<style>
  /* Payment History Styles */
  .supafaya-payment-history {
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
    max-width: 100%;
    margin: 0 auto;
    padding: 20px 0;
  }
  
  .payment-history-header {
    display: flex;
    flex-wrap: wrap;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
    padding-bottom: 16px;
    border-bottom: 1px solid #e0e0e0;
  }
  
  .payment-history-header h2 {
    font-size: 28px;
    font-weight: 700;
    margin: 0;
    color: #333;
  }
  
  .filter-section {
    display: flex;
    align-items: center;
    margin-top: 12px;
  }
  
  .organization-filter {
    display: inline-flex;
    align-items: center;
    padding: 8px 16px;
    background-color: #f0f4ff;
    border-radius: 6px;
    font-size: 14px;
  }
  
  .filter-label {
    font-weight: 600;
    margin-right: 8px;
    color: #4361ee;
  }
  
  .filter-value {
    color: #333;
  }
  
  /* Loading States */
  .loading-indicator, .payment-history-empty, .payment-history-error {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 40px 20px;
    text-align: center;
    color: #666;
  }
  
  .loading-indicator svg, .payment-history-empty svg, .payment-history-error svg {
    margin-bottom: 16px;
    opacity: 0.7;
  }
  
  .spinner {
    animation: rotate 2s linear infinite;
  }
  
  @keyframes rotate {
    100% {
      transform: rotate(360deg);
    }
  }
  
  .spinner path {
    animation: dash 1.5s ease-in-out infinite;
  }
  
  @keyframes dash {
    0% {
      stroke-dasharray: 1, 150;
      stroke-dashoffset: 0;
    }
    50% {
      stroke-dasharray: 90, 150;
      stroke-dashoffset: -35;
    }
    100% {
      stroke-dasharray: 90, 150;
      stroke-dashoffset: -124;
    }
  }
  
  .retry-button {
    margin-top: 16px;
    padding: 8px 16px;
    background-color: #f0f4ff;
    border: 1px solid #4361ee;
    color: #4361ee;
    border-radius: 4px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
  }
  
  .retry-button:hover {
    background-color: #4361ee;
    color: white;
  }
  
  /* Payment History List */
  .payment-history-list {
    margin-bottom: 24px;
  }
  
  .payment-item {
    display: flex;
    flex-wrap: wrap;
    justify-content: space-between;
    align-items: center;
    padding: 16px;
    border-radius: 8px;
    background-color: white;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    margin-bottom: 12px;
    transition: all 0.2s ease;
  }
  
  .payment-item:hover {
    box-shadow: 0 4px 15px rgba(0,0,0,0.08);
    transform: translateY(-2px);
  }
  
  .payment-info {
    flex: 1;
    min-width: 200px;
  }
  
  .payment-event {
    font-weight: 600;
    font-size: 16px;
    color: #333;
    margin-bottom: 4px;
  }
  
  .payment-time {
    color: #666;
    font-size: 14px;
    display: flex;
    align-items: center;
    margin-top: 4px;
  }
  
  .payment-time svg {
    margin-right: 6px;
  }
  
  .payment-amount {
    font-weight: 700;
    font-size: 18px;
    color: #333;
    text-align: right;
    margin: 8px 16px;
  }
  
  .payment-status {
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin: 8px 0;
  }
  
  .status-completed {
    background-color: rgba(75, 181, 67, 0.1);
    color: #4BB543;
  }
  
  .status-pending {
    background-color: rgba(255, 165, 0, 0.1);
    color: #FFA500;
  }
  
  .status-failed {
    background-color: rgba(255, 51, 51, 0.1);
    color: #FF3333;
  }
  
  .status-refunded {
    background-color: rgba(86, 86, 86, 0.1);
    color: #565656;
  }
  
  .view-details-button {
    padding: 8px 16px;
    background-color: transparent;
    border: 1px solid #4361ee;
    color: #4361ee;
    border-radius: 4px;
    font-weight: 500;
    cursor: pointer;
    display: flex;
    align-items: center;
    transition: all 0.2s ease;
    margin: 8px 0;
  }
  
  .view-details-button:hover {
    background-color: #4361ee;
    color: white;
  }
  
  .view-details-button svg {
    margin-left: 6px;
  }
  
  /* Pagination */
  .pagination-container {
    margin-top: 24px;
    display: flex;
    justify-content: center;
  }
  
  .pagination-controls {
    display: flex;
    align-items: center;
    gap: 16px;
  }
  
  .pagination-info {
    font-size: 14px;
    color: #666;
  }
  
  .pagination-button {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 8px 16px;
    background-color: #f0f4ff;
    border: none;
    border-radius: 4px;
    color: #4361ee;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
  }
  
  .pagination-button:hover:not(:disabled) {
    background-color: #4361ee;
    color: white;
  }
  
  .pagination-button:disabled {
    opacity: 0.5;
    cursor: not-allowed;
  }
  
  /* Dialog Styles */
  .payment-detail-dialog {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: 1000;
    display: flex;
    align-items: center;
    justify-content: center;
  }
  
  .payment-detail-dialog-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(2px);
  }
  
  .payment-detail-dialog-content {
    position: relative;
    width: 90%;
    max-width: 600px;
    max-height: 90vh;
    background-color: white;
    border-radius: 12px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.2);
    overflow: hidden;
    z-index: 1001;
    display: flex;
    flex-direction: column;
  }
  
  .dialog-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16px 24px;
    border-bottom: 1px solid #e0e0e0;
  }
  
  .dialog-header h3 {
    margin: 0;
    font-size: 20px;
    font-weight: 600;
    color: #333;
  }
  
  .close-dialog {
    background: transparent;
    border: none;
    color: #666;
    cursor: pointer;
    padding: 4px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
  }
  
  .close-dialog:hover {
    background-color: #f0f0f0;
    color: #333;
  }
  
  .dialog-content {
    padding: 24px;
    overflow-y: auto;
    max-height: calc(90vh - 70px);
  }
  
  .detail-loading {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 40px 0;
    color: #666;
  }
  
  /* Detail section styles */
  .payment-detail-section {
    margin-bottom: 24px;
  }
  
  .payment-detail-section:last-child {
    margin-bottom: 0;
  }
  
  .detail-section-title {
    font-size: 16px;
    font-weight: 600;
    color: #4361ee;
    margin-bottom: 12px;
    padding-bottom: 8px;
    border-bottom: 1px solid #e0e0e0;
  }
  
  .detail-row {
    display: flex;
    justify-content: space-between;
    padding: 8px 0;
    border-bottom: 1px solid #f0f0f0;
  }
  
  .detail-row:last-child {
    border-bottom: none;
  }
  
  .detail-label {
    font-weight: 500;
    color: #666;
  }
  
  .detail-value {
    font-weight: 500;
    color: #333;
    text-align: right;
  }
  
  .ticket-item {
    padding: 12px;
    background-color: #f8f9fa;
    border-radius: 8px;
    margin-bottom: 12px;
  }
  
  .ticket-item:last-child {
    margin-bottom: 0;
  }
  
  .ticket-name {
    font-weight: 600;
    margin-bottom: 4px;
  }
  
  .ticket-details {
    display: flex;
    justify-content: space-between;
    font-size: 14px;
    color: #666;
  }
  
  /* Responsive adjustments */
  @media (max-width: 768px) {
    .payment-history-header {
      flex-direction: column;
      align-items: flex-start;
    }
    
    .payment-history-header h2 {
      margin-bottom: 12px;
    }
    
    .payment-item {
      flex-direction: column;
      align-items: flex-start;
    }
    
    .payment-info {
      width: 100%;
      margin-bottom: 12px;
    }
    
    .payment-meta {
      width: 100%;
      display: flex;
      flex-wrap: wrap;
      justify-content: space-between;
      align-items: center;
    }
    
    .payment-amount {
      margin: 8px 0;
    }
  }
  
  /* Add these styles */
  .user-info-display {
    background-color: #e9f0ff;
    padding: 6px 12px;
    border-radius: 4px;
    font-size: 14px;
    color: #4361ee;
    margin-top: 8px;
    display: inline-block;
  }
  
  .error-details {
    margin-top: 12px;
    padding: 12px;
    background-color: #fff1f1;
    border-radius: 6px;
    color: #d32f2f;
    font-family: monospace;
    max-width: 100%;
    overflow-x: auto;
    text-align: left;
    font-size: 12px;
  }
</style> 