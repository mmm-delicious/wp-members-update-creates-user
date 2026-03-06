# MMM Username Registration API

A WordPress plugin that exposes a REST API endpoint for user registration, designed for use with Elementor forms and WP-Members.

## Features

- REST endpoint at `POST /wp-json/mmm/v1/register`
- Accepts JSON or form-encoded body, including Elementor's nested `fields` structure
- Creates new users with `subscriber` role and WP-Members pending activation state
- If email already exists and last name matches, updates the user's contact info instead of creating a duplicate
- Auto-generates a username (`firstinitial + lastname`) if none provided, with numeric suffix to avoid conflicts
- Maps submitted fields to WP-Members and WooCommerce-compatible user meta keys
- All error cases return silent success (200) to avoid exposing registration state to bots

## Requirements

- WordPress 5.0+
- PHP 7.4+
- WP-Members plugin

## Installation

1. Upload the `wp-members-update-creates-user` folder to `/wp-content/plugins/`
2. Activate the plugin through the Plugins menu in WordPress
3. Point your Elementor form's webhook action to `/wp-json/mmm/v1/register`

> **Note:** If upgrading from a version where the main file was named `wp-members-update-create.php`, deactivate the old plugin and activate the renamed version.

## Endpoint

```
POST /wp-json/mmm/v1/register
Content-Type: application/json
```

### Accepted Fields

| Field | Meta Key | Notes |
|-------|----------|-------|
| `email` | `user_email` | Required |
| `first_name` | `first_name` | Required |
| `last_name` | `last_name` | Required |
| `username` | `user_login` | Optional — auto-generated if blank or taken |
| `phone` / `mobile_phone` | `phone1` | |
| `address` | `billing_address_1` | |
| `city` | `billing_city` | |
| `state` | `billing_state` | |
| `zip` | `billing_postcode` | |
| `ssn` | `LastSNN` | Last 4 digits for verification |
| `job_title` | `job_title` | |
| `job_classification` / `classification` | `job_classification` | |
| `accept` | `newsletter` | |

## Security Notes

- The endpoint is publicly accessible (`permission_callback: __return_true`) by design — it is a registration endpoint
- All input is sanitized before use or storage
- Silent 200 responses on all error paths prevent enumeration of existing users

## Changelog

### 3.1
- Renamed main file to match plugin folder name (`wp-members-update-creates-user.php`)
- Standardized ABSPATH guard syntax
- Added plugin header fields: `Requires at least`, `Requires PHP`, `Tested up to`
- Added README.md

### 3.0
- Added support for existing user update (matched by email + last name)
- Added Elementor nested field flattening

### Earlier
- Initial registration endpoint
