=== Map It! by Two Row Studio ===
Contributors: tworowstudio
Tags: Google Maps, locations, geolocation, extensible
Requires at least: 4.6
Tested up to: 6.6.2
Stable tag: 1.0.7
Requires PHP: 7.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Map your posts and pages - customize the look, feel, and data.

== Description ==

Map It! is a flexible, developer-friendly plugin to help take the tedious coding out of adding maps to a website that can mark geographic locations related to posts. The whole point is to let you choose the types of posts, the display and the formatting of maps markers and data to best fit your needs. Over time, more specific customization features will be made available to developers as well as extensions for the non-technical user to take advantage of the base plugin in new and useful ways.

If you are a developer and are missing a way to add a customization, drop us a line at support@tworowstudio.com so we can make sure we consider it for future releases.


== Installation ==

This section describes how to install the plugin and get it working.

e.g.

1. Upload `trs_mapit` to the `/wp-content/plugins/` directory or extract trs_mapit.zip in that location
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Get your free Google Maps API key (https://developers.google.com/maps/documentation/javascript/get-api-key)
4. Add the key to the settings package
5. Select post types and start adding coordinates
6. Add the shortcode where ever you want your map to appear.

== Frequently Asked Questions ==

= Can this work with all post types? =

Conceivably, yes. The plugin setup screen allows you to select which post types should allow the settings for mapping to be entered. Can't say there isn't a post type out there that won't work, though. Really?? Prove a negative?!?!? ;)

= What about different markers? Things for each type of post? Categories? Address lookups? =

We're working on those as extensions to the basic plugin as well as trying to add hooks for developers to do their own thing with it. If you have a suggestion let us know!

== Screenshots ==

1. Example page of how a post can be given map coordinates. Once saved, the marker can be dragged to a new location on the map to update the coordinates.
2. A simple shortcode will embed a Google map on a page and show the points within the search area related to any posts or media provided with coordinates.
3. Example of the map embedded on a separate page or post.
4. The default Info Window showing the post title, its distance from the search point and a link to the item itself
5. The settings page where the needed Google API key and presentation defaults are loaded and stored.

== Changelog ==
= 1.0.7 =
- Adjusted map result refresh rules to trigger more naturally
- added Zoom rules to trigger map refreshes

= 1.0.6 =
- Corrected Filter operation
- Corrected archive page errors
- added new key for Server to Server API access
- Tested for WP 5.7.1

= 1.0.5 =
- Test for WP core version 5.6
- Correct issue where activating for a post type without a defined Google API Key causes error

= 1.0.4 =
- Correct bad application of patch for admin Screens

= 1.0.3 =
- Test to WP core 5.3.2
- Correct distance limitation error
- Fix Info Window security / Nonce failure
- remove bade donate link
- fix marker on admin pages for pages and custom post types

= 1.0.1 =
Readme file updates

= 1.0 =
First stable release that maps posts:
- Select post types that can be mapped
- metabox for post edit screens to enter the Longitude and
Latitude for the post
- Shortcode for insertion of map on another post page

== Upgrade Notice ==

= 1.0 =
Initial public release



`<?php code(); // goes in backticks ?>`
