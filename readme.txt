=== Thickbox Announcement ===
Contributors: faebu
Tags: announcement, thickbox, lightbox
Requires at least: 2.7
Tested up to: 2.8.4
Stable tag: 1.7

With the Thickbox Announcement plugin you easily can set-up an announcement, which will be displayed in a thickbox (lightbox).

== Description ==

With the Thickbox Announcement you easily can set-up an announcement, which will be displayed in a thickbox (lightbox).
Several options let you control the apperenace and the frequency of the announcement. You can display any external
content using an iframe or an inline content which can be edited using the standard wordpress editor.

= Update Information =
I've **renamed** my plugin since the plugin named looked very irritating. Since Version 1.0.3 the plugin is simply called *Thickbox Announcement*. 
If you are still using Version 1.0.2 no updates are displayed in the plugin overview of your wp installation. To perform an update 
you have to use the function *Add New* and search for this plugin. Click on *Update* to perform the automatic update.

== Installation ==
1. Unpack the download package

2. Upload folder include all files to the `/wp-content/plugins/` directory.

3. Activate the plugin through the `Plugins` menu in WordPress

3. Go to `Options` > `Thickbox Announcement` menu and set-up your announcement

== Frequently Asked Questions ==

= I have set-up an announcement, but no announcement is displayed =

Both hooks `wp_head` and `wp_footer` are being used. Check your template for the call of `wp_head()` and `wp_footer()`.

= For testing purposes i want to display the announcement again and again =

In the options page there is a preview functionality. If this does not help your needs (e.g. because of other styles in the admin interface),
you may delete the cookie tb_cookie in your browser for the current host.

== Screenshots ==

1. The options panel
2. A sample announcement in a thickbox

== Changelog ==

= 1.0.6 =
* FIXED Announcement possibly not displayed when using mode 'once' or 'everytime the user enters the site'

= 1.0.5 =
* FIXED If the page for the internal content is deleted a new page will not be created automatically

= 1.0.4 =
* FIXED External resource is not displayed (empty box) at the frontend

= 1.0.3 =
* FIXED No announcement displayed if date fields are empty
* FIXED I18n not working
* ADDED Calender Date Picker for Dates
* Optimization of settings page (unnecessary elements will be hidden)

= 1.0.2 =
* FIXED Error that after save the ID of the internal post gets lost
* Redesign of Settings page
* Validity of announcement by date from/to

= 1.0.1 =
* I18n

= 1.0.0 =
* Initial Release