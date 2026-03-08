# WordPress Core Function Compatibility List

This document lists commonly used WordPress core functions prioritized for compatibility layers or alternative runtimes (e.g., WarpPress).

The functions are grouped by subsystem based on real-world usage frequency in plugins and themes.

---

# 1. Hooks API

Core event system used by plugins and themes.

- add_action
- add_filter
- do_action
- apply_filters
- remove_action
- remove_filter
- has_action
- has_filter
- did_action
- current_filter

---

# 2. Options API

Persistent key-value storage used for plugin configuration.

- get_option
- update_option
- add_option
- delete_option
- get_site_option
- update_site_option
- add_site_option
- delete_site_option

---

# 3. Plugin Utilities

Helpers used by plugins to resolve paths and URLs.

- plugin_dir_path
- plugin_dir_url
- plugins_url
- is_plugin_active
- is_plugin_active_for_network
- register_activation_hook
- register_deactivation_hook
- register_uninstall_hook

---

# 4. Post API

Core content model used by most plugins.

- get_post
- get_posts
- wp_insert_post
- wp_update_post
- wp_delete_post
- get_post_field
- get_post_status
- get_post_type
- get_permalink
- get_the_title
- get_the_content
- wp_trim_words

---

# 5. Metadata API

Flexible metadata storage system.

- get_post_meta
- update_post_meta
- add_post_meta
- delete_post_meta

- get_user_meta
- update_user_meta
- add_user_meta
- delete_user_meta

- get_term_meta
- update_term_meta
- add_term_meta
- delete_term_meta

---

# 6. User API

User management and permissions.

- get_user_by
- get_userdata
- get_users
- wp_create_user
- wp_insert_user
- wp_update_user
- wp_delete_user

- wp_get_current_user
- get_current_user_id

- current_user_can
- user_can

---

# 7. Script and Style API

Frontend asset management.

- wp_enqueue_script
- wp_enqueue_style
- wp_register_script
- wp_register_style
- wp_deregister_script
- wp_deregister_style

- wp_add_inline_script
- wp_add_inline_style

- wp_localize_script

---

# 8. URL Helpers

Common helpers for generating WordPress URLs.

- home_url
- site_url
- admin_url
- includes_url
- content_url
- plugins_url
- rest_url
- network_home_url
- network_site_url

---

# 9. Escaping Functions

Output escaping helpers.

- esc_html
- esc_attr
- esc_url
- esc_js
- esc_textarea
- esc_sql

---

# 10. Sanitization Functions

Input sanitization helpers.

- sanitize_text_field
- sanitize_email
- sanitize_file_name
- sanitize_title
- sanitize_user
- sanitize_key

---

# 11. Transients API (Caching)

Temporary cached values stored in database or external cache.

- get_transient
- set_transient
- delete_transient

- get_site_transient
- set_site_transient
- delete_site_transient

---

# 12. HTTP API

External HTTP requests.

- wp_remote_get
- wp_remote_post
- wp_remote_request
- wp_remote_retrieve_body
- wp_remote_retrieve_headers
- wp_remote_retrieve_response_code

---

# 13. REST API Helpers

Utilities related to REST routes and requests.

- register_rest_route
- rest_ensure_response
- rest_url

---

# 14. Cron API

WordPress internal scheduler.

- wp_schedule_event
- wp_schedule_single_event
- wp_clear_scheduled_hook
- wp_next_scheduled
- wp_unschedule_event

---

# 15. Misc Utilities

Various helpers commonly used across plugins.

- wp_die
- wp_nonce_field
- wp_create_nonce
- wp_verify_nonce

- is_admin
- is_user_logged_in
- is_multisite

- wp_redirect
- wp_safe_redirect

- wp_json_encode

---

```

```

Hooks API
Options API
Metadata API
Post API
Script/Style API
User capability checks
URL helpers
Transients

```

```
