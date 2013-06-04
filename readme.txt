=== Plugin Name ===
Contributors: atalanta-agency
Tags: blog metrics, blog analytics
Requires at least: 3.0.1
Tested up to: 3.5
Stable tag: 1.5
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Learn more about your readers and how they react to your posts. That way you could improve your blog performance.

== Description ==

Advanced Blog Metrics is an analytics tool dedicated to bloggers. This plugin allows you to improve your blog performance by tracking the following features:


= Features =

Posts:

*   Total number of posts on your blog
*   Average number of posts per day
*   Average number of words per post


Comments:

*   Total number of comments on your blog
*   Average number of comments per day
*   Average number of comments per post
*   Average number of words per comment
*   5 authors who comment the most
*   5 posts which generate the most comments

Best time to publish:

*   When do your posts generate the most comments?
*   When do you post the most?

Facebook:

*   5 posts which generate the most Facebook shares and likes

Multilanguage:
*	English
*	Brazilian
*	French

== Installation ==

1. Upload advanced-blog-metrics.zip
2. Activate Advanced Blog Metrics through the 'Plugins' menu in WordPress.
3. The 5 widgets of the plugin will appear in your Dashboard. You can drag every widget in your favorite area.

Note that you need to check "Users must be registered and logged in to comment" in the Wordpress Settings->Discussion to see data in the "5 authors who comments the most" widget.

== Screenshots ==

1. The 7 widgets that Advanced Blog Metrics provide


== Changelog ==
= 1.5 =
*  allow only the stats to be shown to Admin/Editors
*  the widgets are also displayed on a separate page outside of the WordPress dashboard: in a submenu under the Advanced Blog Metrics menu item

= 1.4.5 =
* Forgot the po/mo files in the 1.4.4 version... Sorry about having been a dummy on that one!

= 1.4.4 =
* Multilanguage version: adding Brazilian translation. Thank you Andrecarrano. We appreciate.
* Multilanguage version: adding French translation.

= 1.4.3 =
* Bugfix: fixes the issue when you have no comment or no post. Divided per 0 made a bug in our calculations.

= 1.4.2 =
* Bugfix: optimization of the Facebook widget in order to minimize the data loading time (100x faster)

= 1.4.1 =
* Bugfix: Automatic requests to Facebook made the "Facebook shares and likes" widget crash when the number of posts was very high. We fixed it by turning it into a "get data" button.
* Bugfix: Starting date: average number's calculation of comments and posts per day use the good date range.

= 1.4 =
* Adds the "5 posts which generate the most Facebook shares and likes" widget.
* Allows you to learn more about the virality of your posts.

= 1.3 =
* Adds the "when do you post the most?" widget
* Uses the wordpress option start_of_week for the "When du your posts generate the most comments?" and "When do you post the most?" widgets

= 1.2 =
* Adds the Starting Date option