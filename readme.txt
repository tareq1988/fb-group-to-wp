=== Facebook Group to WordPress ===
Contributors: tareq1988
Donate Link: https://tareq.co/donate/
Tags: facebook, group, cron, post, thread, wordpress, import, comments
Requires at least: 3.6
Tested up to: 4.6.1
Stable tag: trunk
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Pull your facebook group feed to WordPress

== Description ==

A simple plugin that imports posts from **public** facebook groups to your WordPress blog, every half hour!

= What it does & doesn't =

* Imports from facebook group and inserts as `fb_group_post` post type
* No chance for duplication
* It imports comments as well
* Runs every half hour via WordPress cron system
* Adds group id, author name and ID, post link as post meta
* If you want to trigger the importing manually, go to `http://example.com/?fb2wp_test`
* Import historical (paginated) posts. To do this, go to `http://example.com/?fb2wp_hist` and it'll automatically start the import process. Only admins can run this task.


= Contribute =
This may have bugs and lack of many features. If you want to contribute on this project, you are more than welcome. Please fork the repository from [Github](https://github.com/tareq1988/fb-group-to-wp).

= Author =
Brought to you by [Tareq Hasan](http://tareq.wedevs.com) from [weDevs](http://wedevs.com)

== Installation ==

Extract the zip file and just drop the contents in the wp-content/plugins/ directory of your WordPress installation and then activate the Plugin from Plugins page.

* [Create](https://developers.facebook.com/?ref=pf) a facebook app
* Fillup facebook app ID and secret
* Find the **numeric** group ID for your group. Use [this tool](http://lookup-id.com/) if needed.
* Insert your group ID in the settings
* Now you are done. It'll pull posts automatically.

== Frequently Asked Questions ==

Nothing here yet

== Screenshots ==

1. Settings
2. Posts
3. Posts in frontend

== Changelog ==

= 1.0 - 19 Sep 2016 =

- [fix] Graph API v2.7 compatibility
- [new] Post thumbnail support added

= 0.1 =
Initial version released


== Upgrade Notice ==

Nothing here
