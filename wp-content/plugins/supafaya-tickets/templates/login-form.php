<div class="supafaya-login-form">
    <h2>Connect Your Supafaya Account</h2>
    
    <?php if (is_user_logged_in()): ?>
        <?php 
            $user = wp_get_current_user();
            $token_data = get_user_meta($user->ID, 'supafaya_auth_token', true);
            $is_connected = !empty($token_data);
        ?>
        
        <?php if ($is_connected): ?>
            <div class="connection-status connected">
                <p>Your WordPress account is connected to Supafaya.</p>
                <button id="disconnect-account" class="disconnect-button">Disconnect Account</button>
            </div>
        <?php else: ?>
            <p>Your WordPress account is not connected to Supafaya yet. Connect your accounts to access tickets and events.</p>
            
            <form id="supafaya-connect-form">
                <div class="form-group">
                    <label for="supafaya-email">Email</label>
                    <input type="email" id="supafaya-email" name="email" required>
                </div>
                
                <div class="form-group">
                    <label for="supafaya-password">Password</label>
                    <input type="password" id="supafaya-password" name="password" required>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="connect-button">Connect Account</button>
                </div>
            </form>
            
            <div id="connection-result" style="display: none;"></div>
        <?php endif; ?>
    <?php else: ?>
        <p>Please log in to your WordPress account first to connect with Supafaya.</p>
        <a href="<?php echo esc_url(wp_login_url(get_permalink())); ?>" class="login-button">Log In</a>
    <?php endif; ?>
</div>