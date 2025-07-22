# Google Sign-In Integration Guide

This guide will help you set up Google Sign-In for your application.

## Prerequisites

1. A Google Cloud Platform (GCP) account
2. A project in the Google Cloud Console
3. OAuth consent screen configured
4. OAuth 2.0 Client ID created

## Setup Steps

### 1. Enable Google OAuth API

1. Go to the [Google Cloud Console](https://console.cloud.google.com/)
2. Select your project or create a new one
3. Navigate to "APIs & Services" > "Library"
4. Search for "Google+ API" and enable it
5. Search for "Google OAuth2 API" and enable it

### 2. Configure OAuth Consent Screen

1. Go to "APIs & Services" > "OAuth consent screen"
2. Select "External" user type and click "Create"
3. Fill in the required app information:
   - App name: Your App Name
   - User support email: Your email
   - Developer contact information: Your email
4. Click "Save and Continue"
5. Add the following scopes:
   - `.../auth/userinfo.email`
   - `.../auth/userinfo.profile`
6. Click "Save and Continue"
7. Add test users (optional)
8. Click "Back to Dashboard"

### 3. Create OAuth 2.0 Credentials

1. Go to "APIs & Services" > "Credentials"
2. Click "Create Credentials" > "OAuth client ID"
3. Select "Web application" as the application type
4. Add authorized JavaScript origins:
   - `http://localhost`
   - `http://localhost:8080` (if using a different port)
   - `https://yourdomain.com` (your production domain)
5. Add authorized redirect URIs:
   - `http://localhost/Food/google_auth.php`
   - `https://yourdomain.com/Food/google_auth.php` (your production URL)
6. Click "Create"
7. Copy the Client ID and Client Secret

### 4. Update Configuration

1. Install the Google API Client Library:
   ```bash
   composer require google/apiclient:^2.0
   ```

2. Update `google_auth.php` with your credentials:
   ```php
   $client->setClientId('YOUR_GOOGLE_CLIENT_ID');
   $client->setClientSecret('YOUR_GOOGLE_CLIENT_SECRET');
   $client->setRedirectUri('http://' . $_SERVER['HTTP_HOST'] . '/Food/google_auth.php');
   ```

3. Update your database schema by running the SQL in `update_users_table.sql`

### 5. Testing

1. Try signing in with Google on the login page
2. Verify that new users are created in your database
3. Verify that existing users can log in with their Google account

## Troubleshooting

- **Redirect URI mismatch**: Ensure the redirect URI in your Google Cloud Console matches exactly with the one in your code
- **API not enabled**: Make sure you've enabled the required APIs
- **Insufficient permissions**: Check that you've requested the necessary scopes
- **HTTPS required**: In production, your site must use HTTPS for OAuth to work

## Security Notes

1. Never commit your client secret to version control
2. Use environment variables for sensitive information
3. Always validate the state parameter in production
4. Implement rate limiting to prevent abuse
5. Regularly review authorized applications in your Google Cloud Console
