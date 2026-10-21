# TonniClean Booking System (Trashcan Cleaning Plugin)

A comprehensive, custom WordPress plugin built to manage trashcan cleaning bookings, route planning, subscription credits, and field worker operations. 

Built from scratch using **OOP PHP**, **WooCommerce**, **AJAX**, and **Custom Post Types**.

---

## 🎯 The Business Problem

Independent trashcan cleaning businesses often rely on manual processes to run their operations:
*   **Scattered Bookings:** Managing bookings via WhatsApp and email forces owners to manually cross-reference postcodes, routes, and pricing.
*   **Route Inefficiency:** Without a centralized system, owners struggle to group bookings into efficient geographical routes.
*   **Payment Friction:** Handling one-time payments and recurring cleaning plans manually is error-prone.
*   **No Field Documentation:** Workers have no way to prove they completed a job (e.g., before/after photos, hygiene checklists), leading to customer disputes.
*   **Lack of Customer Transparency:** Customers have no self-service portal to view their history or manage their addresses.

## 💡 The Solution

This plugin digitizes the entire business operation:
*   **Automated Booking Wizard:** Location-based pricing, route selection, and WooCommerce integration.
*   **Admin Operations:** Create geographic routes, set capacities, and assign workers.
*   **Worker Portal:** Workers see their assigned routes, update job statuses, upload before/after photos, and complete hygiene checklists.
*   **Customer Dashboard:** Self-service portal for managing credits, saved addresses, and viewing service reports.
*   **Standalone Setup:** Auto-creates pages, roles, and WooCommerce products upon activation.

---

## 🚀 Features

### Customer Booking Flow
*   **Location-Based Pricing:** Custom SQL table maps postcode ranges (e.g., `70000-79999`) to specific prices.
*   **6-Step Booking Wizard:** Dynamic AJAX-driven wizard (Address → Trashcan → Date → Extras → Summary → Payment).
*   **Route Selection:** Automatically filters and displays only the routes available for the customer's postcode.
*   **WooCommerce Integration:** Creates a pending WooCommerce order, stores custom booking metadata in the order line items, and redirects to the checkout page.
*   **Credit System:** Customers can purchase "Credit Packs" (WooCommerce products). When paid, credits are added to their account. Credits can be spent at checkout to bypass payment.

### Customer Dashboard
*   **Frontend Portal:** Shortcode-based dashboard where users can view available credits, manage saved addresses, and view past bookings.
*   **Service Reports:** Customers can view before/after photos, hygiene checklists, and worker notes for completed jobs.

### Worker Portal (Field Operations)
*   **Custom User Role:** Dedicated `TC Worker` role with restricted capabilities.
*   **Route Management:** Workers see only the routes assigned to them and the bookings on those routes.
*   **Job Workflow:** Workers can "Start Job" (changes status to In Progress) and "Complete Job" (changes status to Completed).
*   **Photo Documentation:** AJAX-based file uploads for Before/After images, saving them to the WordPress Media Library.
*   **Hygiene Checklists:** Structured completion data (Inspected, Cleaned, Sanitized, Deodorized, Damage Check).

### Admin & Operations
*   **Auto-Setup:** On activation, the plugin automatically creates the required pages (Booking, Dashboard, Worker Portal) and the WooCommerce booking product.
*   **Design Settings:** Customizer page for Primary, Secondary, and Accent colors using CSS variables.
*   **CSV Export:** Export all bookings with worker assignments, report checklists, and completion dates.
*   **Automated Emails:** Sends booking confirmations and service completion emails with links to reports.

## 🛠️ Technical Architecture

### Directory Structure
```text
trashcan-booking-system/
│
├── admin/
│   ├── class-admin-menu.php
│   ├── class-admin-design-settings.php
│   ├── class-admin-pricing-page.php
│   ├── class-admin-settings-page.php
│   ├── class-admin-booking-meta-boxes.php
│   ├── class-admin-route-meta-boxes.php
│   ├── class-admin-columns.php
│   ├── class-admin-ajax.php
│   └── class-admin-export.php
│
├── assets/
│   ├── booking-wizard.css
│   ├── booking-wizard.js
│   ├── frontend.css
│   ├── frontend.js
│   └── admin.js
│
├── frontend/
│   ├── class-booking-wizard.php
│   ├── class-postcode-checker.php
│   ├── class-customer-dashboard.php
│   └── class-worker-portal.php
│
├── includes/
│   ├── class-activator.php
│   ├── class-addresses.php
│   ├── class-booking-checkout.php
│   ├── class-credits.php
│   ├── class-meta-fields.php
│   ├── class-notifications.php
│   ├── class-order-sync.php
│   ├── class-plugin.php
│   ├── class-post-types.php
│   ├── class-pricing-rules.php
│   ├── class-roles.php
│   ├── class-route-capacity.php
│   ├── class-service-reports.php
│   ├── class-wizard-order.php
│   └── class-woocommerce-foundation.php
│
├── trashcan-booking-system.php
└── README.md
Key Database Customizations
This plugin uses custom Post Types (tbs_booking, tbs_route) and a custom SQL table (wp_tbs_pricing_rules) for postcode matching.

Important Meta Keys:

_tbs_booking_status: (pending_payment, paid, in_progress, completed)

_tbs_route_id: Links a booking to a specific route.

_tbs_assigned_worker: Links a route to a specific user ID.

_tbs_service_report: Serialized array containing checklist data, image IDs, and worker notes.

_tbs_available_credits: User meta tracking the balance of cleaning credits.

⚙️ Installation
Upload the trashcan-booking-system folder to /wp-content/plugins/.

Activate the plugin through the 'Plugins' menu in WordPress.

Upon activation, the plugin will automatically:

Create the Book Now, My Dashboard, and Worker Portal pages.

Create a hidden WooCommerce product ("Trashcan Cleaning Booking") and link it in settings.

Ensure WooCommerce is installed and active (WooCommerce Subscriptions is not required for the core functionality).

Go to Bookings → Settings to configure pricing and surcharges.

Go to Bookings → Design to customize your brand colors.

💻 Developer Notes
Hooks used: The plugin relies heavily on woocommerce_order_status_changed to sync the WooCommerce Order status with the custom Booking CPT status.

AJAX Security: All AJAX endpoints verify nonces (wp_verify_nonce) and user capabilities (current_user_can).

File Uploads: Uses media_handle_upload to securely process worker photo uploads.
