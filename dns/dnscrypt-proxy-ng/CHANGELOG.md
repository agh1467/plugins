# 2.1.1_7

  * Add support for boxes in form data
  * Swap tabs for boxes on diagnostics page
  * Add section element around boxes (style)
  * Remove version check from diagnostics page
  * Remove unused resolvers model branch
  * Update configd scripts to handle empty params
  * Update to help messaging in form XML
  * Remove extra field clean up in field controls
  * Fix DNS64 field control
  * Add unique constraints for some fields
  * Wrap long lines on log view instead of forcing wide
  * Add support for dropdown field control
  * Migrate log style to <style> defined in partial
  * Add floppy icon to save button
  * Updates to DocBlocks
  * Add log handler supporting parse, export and clear
  * Update logs page to use columns, and style column widths
  * Add buttons for save/apply functionality
  * Add apply changes box
  * Incorporate an upstream change in layout_partial (#5311)
  * Add support for button groups (dropdown)
  * Add fade/slide style to row and apply changes box

# 2.1.1_6

 * Add About page
 * Add odoh_server setting (new since 2.0.46-beta1)
 * Remove static prefixes for socks/http proxy
 * Fix lb_estimator/lb_strategy in view/configuration
 * Add missing cloak_ttl
 * Add missing blocked_query_response
 * Re-evaluate all settings for required/defaults
 * Adjust min/max for all integer-based settings
 * Address error condition in get_resolvers.py
 * Various style updates to config and views
 * Update to developer documentation
 * Add disabled_server_names (extra)
 * Add diagnostic page to view configuration files
 * Add diagnostic page to see version number
 * Various documentation updates
 * Minor space/style/spelling fixes

# 2.0 (2021-04-14)

* Complete re-write.

# 1.8

* Remove 8 discontinued DNSBL lists and 2 that are not updated any more

# 1.7

* Add comment field to whitelist section

# 1.6

* Removed discontinued DNSBL Zeus Tracker list

# 1.5

* Add update and extra WindowsSpyBlocker list

# 1.4

* Added 7 new DNSBL blacklists

# 1.3

* Add DNS blacklisting

# 1.2

* Add logging to menu

# 1.1

* Allow manual server addition and selection

# 1.0

* Automatic selection of fastest DNS servers
* Allow to set cloaks/overrides
* Allow to set forwarders
* Allow to set whitelists
