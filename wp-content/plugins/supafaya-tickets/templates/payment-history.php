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
    <div class="header-content">
      <h2>Payment History</h2>
      <?php if (!empty($atts['organization_id'])): ?>
        <div class="organization-filter">
          <span class="filter-label">Organization:</span>
          <span class="filter-value"><?php echo esc_html($atts['organization_id']); ?></span>
        </div>
      <?php endif; ?>
    </div>
    <div class="header-actions">
      <div class="search-box">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
          stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="11" cy="11" r="8"></circle>
          <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
        </svg>
        <input type="text" placeholder="Search payments..." class="search-input">
      </div>
    </div>
  </div>

  <div class="payment-history-content">
    <div class="payment-history-list">
      <!-- Payments will be loaded here via AJAX -->
      <div class="loading-indicator">
        <div class="spinner-container">
          <div class="spinner"></div>
        </div>
        <span>Loading payment history...</span>
      </div>
    </div>

    <div class="payment-history-empty" style="display: none;">
      <div class="empty-state">
        <svg xmlns="http://www.w3.org/2000/svg" width="80" height="80" viewBox="0 0 24 24" fill="none"
          stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
          <path d="M21 5H3a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h18a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2Z"></path>
          <path d="M4 17h16"></path>
          <path d="M4 12h16"></path>
          <path d="M7 5V3"></path>
          <path d="M17 5V3"></path>
        </svg>
        <h3>No Payments Found</h3>
        <p>You haven't made any payments yet.</p>
        <button class="refresh-button">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21 12a9 9 0 0 0-9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"></path>
            <path d="M3 3v5h5"></path>
            <path d="M3 12a9 9 0 0 0 9 9 9.75 9.75 0 0 0 6.74-2.74L21 16"></path>
            <path d="M16 16h5v5"></path>
          </svg>
          Refresh
        </button>
      </div>
    </div>

    <div class="payment-history-error" style="display: none;">
      <div class="error-state">
        <svg xmlns="http://www.w3.org/2000/svg" width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="#FF4D4F"
          stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="12" cy="12" r="10"></circle>
          <line x1="15" y1="9" x2="9" y2="15"></line>
          <line x1="9" y1="9" x2="15" y2="15"></line>
        </svg>
        <h3>Error Loading Payments</h3>
        <p>Failed to load payment history. Please try again later.</p>
        <div class="error-details" style="display: none;"></div>
        <button class="retry-button">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21 12a9 9 0 0 0-9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"></path>
            <path d="M3 3v5h5"></path>
            <path d="M3 12a9 9 0 0 0 9 9 9.75 9.75 0 0 0 6.74-2.74L21 16"></path>
            <path d="M16 16h5v5"></path>
          </svg>
          Retry
        </button>
      </div>
    </div>

    <div class="pagination-container" style="display: none;">
      <div class="pagination-controls">
        <button class="pagination-button prev-page" disabled>
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="m15 18-6-6 6-6"></path>
          </svg>
          <span>Previous</span>
        </button>
        <div class="pagination-info">
          Page <span class="current-page">1</span> of <span class="total-pages">1</span>
        </div>
        <button class="pagination-button next-page" disabled>
          <span>Next</span>
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
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
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
          stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <line x1="18" y1="6" x2="6" y2="18"></line>
          <line x1="6" y1="6" x2="18" y2="18"></line>
        </svg>
      </button>
    </div>
    <div class="dialog-content">
      <!-- Payment details will be loaded here -->
      <div class="detail-loading">
        <div class="spinner-container">
          <div class="spinner"></div>
        </div>
        <span>Loading details...</span>
      </div>
    </div>
  </div>
</div>

<style>
  /* Modern CSS Variables */
  :root {
    --primary-color: #4361ee;
    --primary-hover: #3a56d4;
    --success-color: #4BB543;
    --warning-color: #FFA500;
    --error-color: #FF4D4F;
    --text-primary: #1a1a1a;
    --text-secondary: #666;
    --text-tertiary: #999;
    --border-color: #e0e0e0;
    --border-light: #f0f0f0;
    --bg-light: #f8f9fa;
    --bg-white: #ffffff;
    --shadow-sm: 0 1px 3px rgba(0, 0, 0, 0.1);
    --shadow-md: 0 4px 6px rgba(0, 0, 0, 0.1);
    --shadow-lg: 0 10px 25px rgba(0, 0, 0, 0.1);
    --radius-sm: 4px;
    --radius-md: 8px;
    --radius-lg: 12px;
    --transition: all 0.2s ease;
  }

  /* Base Styles */
  .supafaya-payment-history {
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Helvetica Neue', sans-serif;
    max-width: 100%;
    margin: 0 auto;
    padding: 24px;
    color: var(--text-primary);
  }

  /* Header Styles */
  .payment-history-header {
    display: flex;
    flex-wrap: wrap;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
    padding-bottom: 16px;
    border-bottom: 1px solid var(--border-color);
  }

  .header-content {
    display: flex;
    align-items: center;
    gap: 24px;
  }

  .payment-history-header h2 {
    font-size: 24px;
    font-weight: 600;
    margin: 0;
    color: var(--text-primary);
  }

  .organization-filter {
    display: inline-flex;
    align-items: center;
    padding: 6px 12px;
    background-color: rgba(67, 97, 238, 0.1);
    border-radius: var(--radius-sm);
    font-size: 14px;
  }

  .filter-label {
    font-weight: 500;
    margin-right: 8px;
    color: var(--primary-color);
  }

  .filter-value {
    color: var(--text-primary);
    font-weight: 500;
  }

  /* Search Box */
  .search-box {
    position: relative;
    display: flex;
    align-items: center;
  }

  .search-box svg {
    position: absolute;
    left: 12px;
    color: var(--text-tertiary);
  }

  .search-input {
    padding: 8px 12px 8px 36px;
    border: 1px solid var(--border-color);
    border-radius: var(--radius-md);
    font-size: 14px;
    width: 240px;
    transition: var(--transition);
  }

  .search-input:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: 0 0 0 2px rgba(67, 97, 238, 0.2);
  }

  /* Loading States */
  .loading-indicator,
  .payment-history-empty,
  .payment-history-error {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 60px 20px;
    text-align: center;
  }

  .empty-state,
  .error-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    max-width: 400px;
    margin: 0 auto;
  }

  .empty-state svg,
  .error-state svg {
    margin-bottom: 16px;
    color: var(--text-tertiary);
  }

  .empty-state h3,
  .error-state h3 {
    font-size: 18px;
    font-weight: 600;
    margin: 0 0 8px;
    color: var(--text-primary);
  }

  .empty-state p,
  .error-state p {
    font-size: 14px;
    color: var(--text-secondary);
    margin: 0 0 16px;
  }

  /* Spinner Animation */
  .spinner-container {
    margin-bottom: 16px;
  }

  .spinner {
    width: 40px;
    height: 40px;
    border: 3px solid rgba(67, 97, 238, 0.2);
    border-radius: 50%;
    border-top-color: var(--primary-color);
    animation: spin 1s ease-in-out infinite;
  }

  @keyframes spin {
    to {
      transform: rotate(360deg);
    }
  }

  /* Buttons */
  .retry-button,
  .refresh-button,
  .view-details-button {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 16px;
    background-color: var(--primary-color);
    border: none;
    border-radius: var(--radius-sm);
    color: white;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: var(--transition);
  }

  .retry-button:hover,
  .refresh-button:hover,
  .view-details-button:hover {
    background-color: var(--primary-hover);
    transform: translateY(-1px);
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
    border-radius: var(--radius-md);
    background-color: var(--bg-white);
    box-shadow: var(--shadow-sm);
    margin-bottom: 12px;
    transition: var(--transition);
  }

  .payment-item:hover {
    box-shadow: var(--shadow-md);
    transform: translateY(-2px);
  }

  .payment-info {
    flex: 1;
    min-width: 200px;
  }

  .payment-event {
    font-weight: 600;
    font-size: 16px;
    color: var(--text-primary);
    margin-bottom: 4px;
  }

  .payment-time {
    color: var(--text-secondary);
    font-size: 14px;
    display: flex;
    align-items: center;
    margin-top: 4px;
  }

  .payment-time svg {
    margin-right: 6px;
    width: 16px;
    height: 16px;
  }

  .payment-amount {
    font-weight: 700;
    font-size: 16px;
    color: var(--text-primary);
    text-align: right;
    margin: 8px 16px;
  }

  .payment-meta {
    display: flex;
    align-items: center;
    gap: 12px;
  }

  .payment-status {
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
  }

  .status-completed {
    background-color: rgba(75, 181, 67, 0.1);
    color: var(--success-color);
  }

  .status-pending {
    background-color: rgba(255, 165, 0, 0.1);
    color: var(--warning-color);
  }

  .status-failed {
    background-color: rgba(255, 77, 79, 0.1);
    color: var(--error-color);
  }

  .status-refunded {
    background-color: rgba(134, 142, 150, 0.1);
    color: #868e96;
  }

  .view-details-button {
    background-color: transparent;
    border: 1px solid var(--primary-color);
    color: var(--primary-color);
  }

  .view-details-button:hover {
    background-color: var(--primary-color);
    color: white;
  }

  /* Pagination */
  .pagination-container {
    margin-top: 32px;
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
    color: var(--text-secondary);
  }

  .pagination-button {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 8px 16px;
    background-color: rgba(67, 97, 238, 0.1);
    border: none;
    border-radius: var(--radius-sm);
    color: var(--primary-color);
    font-weight: 500;
    cursor: pointer;
    transition: var(--transition);
  }

  .pagination-button:hover:not(:disabled) {
    background-color: var(--primary-color);
    color: white;
  }

  .pagination-button:disabled {
    opacity: 0.5;
    cursor: not-allowed;
    background-color: var(--bg-light);
  }

  /* Dialog Styles */
  .payment-detail-dialog {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: 1100;
    display: flex;
    align-items: center;
    justify-content: center;
  }

  .payment-detail-dialog-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: rgba(0, 0, 0, 0.6);
    backdrop-filter: blur(4px);
    animation: fadeIn 0.2s ease-out;
  }

  @keyframes fadeIn {
    from {
      opacity: 0;
    }

    to {
      opacity: 1;
    }
  }

  .payment-detail-dialog-content {
    position: relative;
    background-color: white;
    border-radius: 16px;
    width: 95%;
    max-width: 650px;
    max-height: 85vh;
    overflow: hidden;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
    transform-origin: center;
    animation: scaleIn 0.2s ease-out;
    display: flex;
    flex-direction: column;
  }

  .dialog-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px 24px;
    border-bottom: 1px solid var(--border-color);
  }

  .dialog-header h3 {
    margin: 0;
    font-size: 20px;
    font-weight: 600;
    color: var(--text-primary);
  }

  .close-dialog {
    background: transparent;
    border: none;
    color: var(--text-secondary);
    cursor: pointer;
    padding: 8px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: var(--transition);
  }

  .close-dialog:hover {
    background-color: var(--bg-light);
    color: var(--text-primary);
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
    color: var(--text-secondary);
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
    color: var(--primary-color);
    margin-bottom: 16px;
    padding-bottom: 8px;
    border-bottom: 1px solid var(--border-color);
  }

  .detail-row {
    display: flex;
    justify-content: space-between;
    padding: 12px 0;
    border-bottom: 1px solid var(--border-light);
  }

  .detail-row:last-child {
    border-bottom: none;
  }

  .detail-label {
    font-weight: 500;
    color: var(--text-secondary);
  }

  .detail-value {
    font-weight: 500;
    color: var(--text-primary);
    text-align: right;
  }

  .ticket-item {
    padding: 12px;
    background-color: var(--bg-light);
    border-radius: var(--radius-sm);
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
    color: var(--text-secondary);
  }

  /* Error Details */
  .error-details {
    margin-top: 16px;
    padding: 12px;
    background-color: rgba(255, 77, 79, 0.1);
    border-radius: var(--radius-sm);
    color: var(--error-color);
    font-family: 'SFMono-Regular', Consolas, 'Liberation Mono', Menlo, monospace;
    max-width: 100%;
    overflow-x: auto;
    text-align: left;
    font-size: 12px;
  }

  /* Responsive adjustments */
  @media (max-width: 768px) {
    .payment-history-header {
      flex-direction: column;
      align-items: flex-start;
      gap: 16px;
    }

    .header-content {
      flex-direction: column;
      align-items: flex-start;
      gap: 12px;
    }

    .header-actions {
      width: 100%;
    }

    .search-input {
      width: 100%;
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
      flex-wrap: wrap;
    }

    .payment-amount {
      text-align: left;
      margin: 8px 0;
    }

    .pagination-controls {
      gap: 8px;
    }

    .pagination-button span {
      display: none;
    }
  }
</style>