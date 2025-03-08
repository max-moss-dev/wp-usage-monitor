=== Block Usage ===
Contributors: zenblocks
Tags: blocks, gutenberg, usage, analytics, admin, editor
Requires at least: 5.8
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Track and analyze Gutenberg block usage across your entire WordPress site. Find which blocks are used where.

== Description ==

Block Usage provides a comprehensive overview of all Gutenberg blocks registered on your WordPress site and tracks how they're being used across your content.

**Why Track Block Usage?**

* Identify unused blocks from plugins that could be disabled
* Understand which blocks your content creators use most
* Find all instances of specific blocks when planning design changes
* Troubleshoot by locating where problematic blocks are used
* Make informed decisions about which block plugins to keep

**Key Features:**

* **Complete Block Inventory** - View all blocks from WordPress core, themes, and plugins in one place
* **Smart Usage Statistics** - See exactly how many times each block is used
* **Detailed Location Tracking** - Find which posts, pages, or templates use a specific block
* **Content Type Breakdown** - View usage organized by post type (pages, posts, templates, etc.)
* **Simple Filtering** - Filter to see only used or unused blocks
* **Full Site Editing Support** - Tracks blocks in templates and template parts

Block Usage automatically detects when content changes and alerts you when statistics need to be updated, ensuring you always have accurate information about your site's block usage.

== Installation ==

1. Upload the `block-usage` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to Tools → Block Usage to view your block statistics

== Frequently Asked Questions ==

= How accurate are the usage statistics? =

Block Usage scans all content in your database to find block instances. The statistics are updated whenever you run a scan, and the plugin notifies you when content has changed since the last scan.

= Will this plugin slow down my site? =

No. Block Usage only runs scans when you specifically request them from the admin interface, and the admin-only functionality doesn't affect your site's front-end performance.

= Does it work with Full Site Editing and templates? =

Yes! Block Usage tracks blocks used in FSE templates and template parts, giving you a complete picture of block usage across your entire site.

= How do I find where a specific block is used? =

Simply click on any block name in the list, and a sidebar will appear showing all posts, pages, and templates where that block appears. You can click any item to go directly to the editor for that content.


== Changelog ==

= 1.0.0 =
* Initial release

== Upgrade Notice ==

= 1.0.0 =
Initial release of Block Usage 