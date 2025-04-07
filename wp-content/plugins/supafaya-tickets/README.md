# Supafaya Tickets WordPress Plugin

This plugin integrates your WordPress site with the Supafaya Ticketing API.

## Firebase Authentication Setup

### Step 1: Create a Firebase Project

1. Go to the [Firebase Console](https://console.firebase.google.com/)
2. Click "Add project"
3. Follow the setup wizard to create your project
4. Once created, click on your project to enter the dashboard

### Step 2: Set Up Firebase Authentication

1. In your Firebase project dashboard, click on "Authentication" in the left sidebar
2. Click "Get started"
3. Enable the sign-in methods you want to use:
   - Email/Password: Click on it, enable it, and save
   - Google: Click on it, enable it, configure OAuth consent in Google Cloud (follow Firebase's instructions), and save
   - Add other providers as needed (Facebook, Twitter, etc.)

### Step 3: Get Your Firebase Configuration

1. In your Firebase project dashboard, click on the gear icon near "Project Overview" and select "Project settings"
2. Scroll down to the "Your apps" section
3. If you don't have an app already, click on the web icon (</>) to add a web app
4. Give your app a nickname (e.g., "WordPress Integration")
5. Register the app
6. You'll see a configuration object that looks like this:
   ```javascript
   const firebaseConfig = {
     apiKey: "YOUR_API_KEY",
     authDomain: "YOUR_PROJECT_ID.firebaseapp.com",
     projectId: "YOUR_PROJECT_ID",
     storageBucket: "YOUR_PROJECT_ID.appspot.com",
     messagingSenderId: "YOUR_MESSAGING_SENDER_ID",
     appId: "YOUR_APP_ID"
   };
   ```
7. Note the `apiKey`, `authDomain`, and `projectId` values - you'll need these for the plugin settings

### Step 4: Configure the Plugin Settings

1. In your WordPress admin dashboard, go to Supafaya Tickets > Settings
2. Find the "Firebase Authentication Settings" section
3. Enter the following values from your Firebase config:
   - Firebase API Key: Your `apiKey` value
   - Firebase Auth Domain: Your `authDomain` value
   - Firebase Project ID: Your `projectId` value
4. In the "Main Settings" section, you can optionally specify a custom Login Page URL if you've created a page with the Firebase login shortcode.
5. Save the settings

### Step 5: Create a Login Page

1. Go to Pages > Add New in your WordPress admin
2. Give it a title (e.g., "Login")
3. Add the shortcode: `[supafaya_firebase_login]`
4. Publish the page
5. The plugin will automatically detect this page, or you can manually set it in the plugin settings

## Cart and Checkout Integration

This plugin includes sophisticated cart management features:

1. **Event-Specific Carts**: Each event has its own separate cart, meaning items from one event won't appear when viewing another event.
2. **Persistent Cart Storage**: Cart contents are saved in localStorage for each event.
3. **Authentication Integration**: When users try to checkout without being logged in, they're redirected to the login page. After authentication, they return to checkout with their cart intact.
4. **Automatic Event Detection**: The current event is detected from the URL parameter.

## User Dropdown Menu

The plugin adds a user dropdown to your site's main menu:

- For logged-in users: Shows their name/avatar with links to My Account, My Tickets, and Logout
- For non-logged-in users: Shows a Login button that redirects to your Firebase login page

## API Configuration

1. Go to Supafaya Tickets > Settings
2. In the "Main Settings" section, configure your API connection:
   - API URL: The URL to your Supafaya API
   - Default Organization ID: Your organization ID in Supafaya
   - Login Page URL: The URL to your custom login page (optional)

## Available Shortcodes

- `[supafaya_firebase_login]`: Displays a login form with Firebase Authentication
- `[supafaya_events_grid]`: Displays a grid of events
- `[supafaya_event_single]`: Displays a single event with details
- `[supafaya_ticket_checkout]`: Displays a ticket checkout form
- `[supafaya_my_tickets]`: Displays the user's purchased tickets

## Proof of Payment Feature

This plugin now supports a "Proof of Payment" feature, which allows users to submit manual payment proofs for their ticket purchases. 

### How It Works

1. Users add tickets to their cart as normal
2. After reviewing their cart, they click the "Send Proof of Payment" button
3. A modal dialog appears where users can:
   - Enter payment details (reference/transaction ID, bank/payment provider, amount, date)
   - Upload a receipt or screenshot as proof of payment
   - Add additional notes
4. Upon submission, the proof is sent to the Supafaya API
5. Administrators can review and verify the payment proofs in the Supafaya dashboard

### Implementation Details

- The feature is automatically integrated into the event page
- The form handles all errors gracefully
- Visual feedback is provided during all states (loading, error, success)
- Mobile-responsive design
- File upload preview for images and documents

## Troubleshooting

### Firebase Login Not Working
- Check your Firebase configuration values in the plugin settings
- Ensure you've enabled the appropriate authentication methods in Firebase
- Check browser console for any JavaScript errors

### Cart Not Persisting
- Make sure localStorage is enabled in your browser
- Check the browser console for any JavaScript errors
- Ensure your checkout redirect is properly set up

### "Security verification failed" Error
- This error typically occurs if the nonce verification fails
- Try refreshing the page to get a new nonce
- Check that your server time is accurate
- Make sure you're using the latest version of the plugin

### API Connection Issues
- Verify your API URL in the plugin settings
- Check that your server can reach the API endpoint
- Ensure your organization ID is correct 