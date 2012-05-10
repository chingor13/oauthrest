=== OAuthRest ===
Contributors: chingor13
Tags: api,rest,oauth,server
Requires at least: 2.8
Tested up to: 3.3.2
Stable tag: 0.2.0
License: GPLv2
License URI: http://www.gnu.org/licenses/gpl-2.0.html

OAuthRest is an implementation of a resourceful REST api.  It uses authentication via the oauth-provider plugin.

== Description ==

This plugin enables a resourceful REST interface for posts and comments.  Authentication is handled via the [oauth-provider plugin](http://wordpress.org/extend/plugins/oauth-provider/).

For more information about resourceful routing, see [here](http://guides.rubyonrails.org/routing.html).

Acceptable return formats:

* JSON

Endpoints:

* GET - /api/posts.:format
* GET - /api/posts/:id.:format
* POST - /api/posts.:format
* PUT - /api/posts/:id.:format
* GET - /api/comments.:format
* GET - /api/comments/:id.:format
* POST - /api/comments.:format
* PUT - /api/comments/:id.:format

== Installation ==

1. Upload `oauthrest` to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Create a consumer using the 'OAuth Provider' menu
1. Profit

== Frequently Asked Questions ==

= How can I contribute to this plugin? =

Fork my repo on [github](http://github.com/chingor13/oauthrest) and send me a pull request.

= Can I limit which endpoints require authentication? =

No, not at this time.  Feel free to help me out and send me a pull request.

== To Do ==

1. Limit authentication to specific endpoints (customizable)

== Changelog ==

**0.1.0 - 2012-05-03**
Initial Release.

**0.1.1 - 2012-05-04**
Remove XML format. JSON is much more widely accepted and encoding/decoding is built into PHP.

**0.1.2 - 2012-05-04**
Fix comments resource endpoint.

**0.1.3 - 2012-05-07**
Fix trackback count for posts.  Fix comment dates.

**0.2.0 - 2012-05-10**
Use the build-in functions when interacting with posts and comments.  Fix routing so that we don't require an "api" page to be created.
