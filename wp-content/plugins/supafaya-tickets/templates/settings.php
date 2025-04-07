<div class="wrap">
    <h1>Supafaya Tickets Settings</h1>
    
    <form method="post" action="options.php">
        <?php settings_fields('supafaya_tickets_options'); ?>
        <?php do_settings_sections('supafaya-tickets-settings'); ?>
        
        <?php submit_button(); ?>
    </form>
    
    <div class="connection-test">
        <h2>Connection Test</h2>
        <p>Test the connection to your Supafaya API:</p>
        <button id="test-connection" class="button">Test Connection</button>
        <div id="connection-test-result"></div>
    </div>
    
    <div class="shortcodes-help">
        <h2>Available Shortcodes</h2>
        
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Shortcode</th>
                    <th>Description</th>
                    <th>Parameters</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><code>[supafaya_events]</code></td>
                    <td>Display a list of events</td>
                    <td>
                        <ul>
                            <li><code>organization_id</code> (optional) - The ID of the organization. If not provided, uses the default from settings</li>
                            <li><code>limit</code> - Number of events to display (default: 10)</li>
                            <li><code>filter</code> - Filter events (upcoming, ongoing, past)</li>
                            <li><code>template</code> - Display template (grid, list, calendar)</li>
                        </ul>
                    </td>
                </tr>
                <tr>
                    <td><code>[supafaya_event]</code></td>
                    <td>Display a single event</td>
                    <td>
                        <ul>
                            <li><code>event_id</code> (required) - The ID of the event</li>
                            <li><code>template</code> - Display template (default, compact)</li>
                        </ul>
                    </td>
                </tr>
                <tr>
                    <td><code>[supafaya_ticket_checkout]</code></td>
                    <td>Display a ticket checkout form for an event</td>
                    <td>
                        <ul>
                            <li><code>event_id</code> (required) - The ID of the event</li>
                        </ul>
                    </td>
                </tr>
                <tr>
                    <td><code>[supafaya_my_tickets]</code></td>
                    <td>Display a list of tickets for the current user</td>
                    <td>
                        <ul>
                            <li><code>limit</code> - Number of tickets to display (default: 10)</li>
                        </ul>
                    </td>
                </tr>
                <tr>
                    <td><code>[supafaya_enhanced_login_form]</code></td>
                    <td>Display a form to connect WordPress account with Supafaya</td>
                    <td>None</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>