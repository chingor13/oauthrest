=== OAuthRest ===
Contributors: chingor13
Tags: api,rest,oauth,server
Requires at least: 2.8
Tested up to: 3.3.2
Stable tag: 0.1
License: GPLv2
License URI: http://www.gnu.org/licenses/gpl-2.0.html

OAuthRest is an implementation of a REST api.  It uses authentication via the oauth-provider plugin.

== Description ==

This plugin enables a REST interface for posts and comments.  Authentication is handled via the [oauth-provider plugin](http://wordpress.org/extend/plugins/oauth-provider/).

Acceptable return formats:

* JSON (preferred)
* XML

Endpoints:

* GET - /api/posts
* GET - /api/posts/:id
* POST - /api/posts
* PUT - /api/posts/:id

== Installation ==

1. Upload `oauth-rest` to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Create a consumer using the 'OAuth Provider' menu
1. Profit

== Frequently Asked Questions ==

== Screenshots ==

== Changelog ==


