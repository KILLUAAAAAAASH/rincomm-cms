# Rincomm Database Migration Guide

This folder contains the database structure for the Rincomm Internet Service Customer Management System.

Migration filenames are kept unchanged because Laravel records each migration name in the `migrations` table after it runs.

Do not rename migrations that have already been executed.

---

## Laravel Core

These migrations are part of the basic Laravel application setup.

- `0001_01_01_000000_create_users_table.php`
  - Creates the main user account table.

- `0001_01_01_000001_create_cache_table.php`
  - Creates Laravel cache storage tables.

- `0001_01_01_000002_create_jobs_table.php`
  - Creates Laravel queue/job tables.

---

## User Accounts and Access

- `2026_08_26_042333_add_role_and_account_status_to_users_table.php`
  - Adds user roles and account status.
  - Supports Admin, Staff, Customer, and Technician access.

---

## Service Plans

- `2026_08_23_112343_create_service_plans_table.php`
  - Creates the internet service plans table.

- `2026_08_23_133153_add_plan_details_to_service_plans_table.php`
  - Adds additional plan information such as speed and monthly fee.

- `2026_08_23_151642_remove_price_from_service_plans_table.php`
  - Removes the older price field after the plan structure was updated.

---

## Customers / Subscribers

- `2026_08_23_112400_create_customers_table.php`
  - Creates customer/subscriber records.

- `2026_08_23_130051_add_customer_details_to_customers_table.php`
  - Adds additional customer information.

- `2026_09_03_193850_add_unique_user_id_to_customers_table.php`
  - Ensures one User account cannot be linked to multiple Customer records.

- `2026_09_03_200606_add_pending_status_to_customers_table.php`
  - Adds support for pending customer status.

- `2026_09_07_050027_replace_terminated_with_disconnected_in_customers_status.php`
  - Replaces the old terminated status with disconnected.

- `2026_09_07_170610_create_customer_status_histories_table.php`
  - Records customer status changes for history and audit purposes.

---

## Technicians

- `2026_08_23_112450_create_technicians_table.php`
  - Creates technician profiles and technician codes.

---

## Subscriptions

- `2026_08_23_112500_create_subscriptions_table.php`
  - Creates customer subscriptions.

- `2026_08_23_150129_add_subscription_details_to_subscriptions_table.php`
  - Adds additional subscription information.

- `2026_09_03_192256_make_start_date_nullable_in_subscriptions_table.php`
  - Allows an approved application to create a pending subscription before service activation.

---

## Service Coverage

- `2026_08_31_000420_create_service_areas_table.php`
  - Creates Rincomm serviceable-area records.

- `2026_08_31_010726_add_coordinates_to_service_areas_table.php`
  - Adds latitude and longitude for service-area map display.

---

## Service Applications

- `2026_08_31_001642_create_service_applications_table.php`
  - Stores new connection/service applications.
  - Links applicants to a selected service area and internet plan.

---

## Tickets / Service Requests

- `2026_08_23_112600_create_service_requests_table.php`
  - Creates the original service request structure.

- `2026_08_23_152722_update_service_requests_for_ticketing.php`
  - Extends service requests into the ticketing workflow.

- `2026_08_23_153505_create_ticket_messages_table.php`
  - Stores ticket conversation/messages.

- `2026_08_23_154056_create_ticket_notes_table.php`
  - Stores internal ticket notes.

---

## Job Orders

- `2026_08_23_112700_create_job_orders_table.php`
  - Creates technician job orders.

- `2026_08_23_154735_create_job_order_notes_table.php`
  - Stores notes associated with job orders.

---

## Billing / Invoices

- `2026_08_24_092007_create_invoices_table.php`
  - Creates invoice records.

- `2026_08_24_093527_create_invoice_items_table.php`
  - Stores individual invoice items.

These migrations provide database foundations. Their presence does not automatically mean the complete billing UI and workflow are finished.

---

## Payments / Receipts

- `2026_08_24_094132_create_payments_table.php`
  - Creates payment records.

- `2026_08_24_094624_create_payment_receipts_table.php`
  - Creates payment receipt records.

- `2026_08_24_123047_add_unique_payment_id_to_payment_receipts_table.php`
  - Adds a unique payment reference to receipts.

These migrations provide database foundations. Full payment functionality is developed according to the project roadmap.

---

## Public Website Hero Carousel

- `2026_08_25_140818_create_hero_slides_table.php`
  - Stores landing-page carousel slides, content, scheduling, CTA links, and active status.

---

# Migration Rules

1. Do not rename a migration after it has already run.
2. Do not manually change Laravel's `migrations` table.
3. Use a new migration when changing an existing database table.
4. Give new migrations descriptive names.
5. Test migrations before committing them.
6. Keep schema changes consistent with the approved FRS.
7. Use `php artisan migrate:status` to check migration state.
8. Use `php artisan migrate` for normal schema updates.
9. Avoid `migrate:fresh` unless intentionally resetting development data.
10. Database migrations define structure. They do not by themselves prove that a complete system feature is finished.