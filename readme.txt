=== Plugin Name ===
Contributors: cyberws
Donate link: http://www.cyberws.com/cleverwise-plugins/
Tags: quotes, quote, daily, tip, tips, snippets, snippet, rotate, dynamic, content, daily
Requires at least: 3.0.1
Tested up to: 5.9
Stable tag: 3.4
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Adds unlimited daily changing quotes, tips, information. Control categories, multiple theming, multipart quotes, HTML formatting support, plus many other features.

== Description ==

<p>Have you ever wanted to display a daily quote? Perhaps a daily tip? A daily snippet?</p>

<p>Do you believe you should have that ability with easy management inside your familiar Wordpress panel?</p>

<p>Well...</p>

<p>You have arrived at the solution! That's right armed with this plugin you are able to easily add daily changing content, including HTML markup, for things like quotes, tips, snippets, etc (we'll just call it quotes from now on). Plus it is super easy and dare I say fun?!</p>

<p>Once this plugin is setup you simply point and click and watch the content goodness.  But wait there is more! (Okay cheesy commercial spinoffs aside) You do get some pretty cool features.</p>

<p>For starters there are no limits, short of your hosting infrastructure, to the number of daily quote sections.  You aren't locked into a single (one) daily quote section.  Nope not at all.  Want five? Great! Ten? Awesome! 50? You are a daily quote machine.  300?! You are a daily quote section god!</p>

<p>Now you are probably wondering but what if I only want a specific daily quote section to be displayed on a certain category? That's easy! Can you click a mouse? Sure you got to this page.  That is how easy it is to accomplish.  When setting up (or editing) a quote section the plugin will display your WordPress category list and you simply check the boxes for each category you wish those daily quotes to appear in.  That could be one category or 50 categories (assuming you had 50).</p>

<p>I know your next question! Yes there is a display in all categories (single click) option. However there is another display option which is to skip certain categories.  So you have three options to display a daily section which are in all your categories, exclude specific categories and display in rest, or include specific categories and skip the rest.  Wait! Before we move on there is really a fourth option which is hide/turn off.  This way you can keep a daily section in your database but prevent it from displaying on your site.</p>

<p>Also there is no limit to the number of daily quotes per category.  You could have a category with one and another category with 10.  Its totally up to you and changeable at any time.  When a category has multiple quote sections the plugin will display them in alphabetical order based on the daily section title.  If a post is assigned to multiple categories the first category is the one used.</p>

<p>You may be thinking what about static pages? You know the non post kind?  WordPress doesn't use categories for pages, well not in the same way as it does for posts.  The good news is this plugin has you covered! You are able to load a specific daily section using a special shortcode, provided by the system, that you insert into the page. When you combine this technique with the display hide/off for the daily section then the information will only appear on that static page! Oh yeah there is no limit on the number of specific daily section shortcodes that may be added to a page.</p>

<p>This plugin has a default/general quote layout/theme.  You are able to easily edit this theme to fit your site's design.  However it isn't one theme and done; nope.  Every daily quote section has the option for its own custom theme.  That way if you want a specific quote section to be themed differently no problem.  Therefore if you had ten quote sections seven may use the default/general one and the three others use custom layouts unique to them.  Another advantage to this design is you could use the same quote information and setup multiple sections and theme them differently.  This allows you to display the same information in different ways for different areas/categories of your site.</p>

<p>Multipart quotes are supported!  This means you are able to break a quote entry into multiple pieces.  At present up to fifteen (15) different pieces.  So you are now able to create complex themes that have a common layout with multiple changing sections that are specified in the daily entry itself.  So for example a theme with dynamic store URL, image name, product name, alt tags, css tags, etc.  You can change up to ten pieces of information in different areas of your theme every day!  No longer do you have to treat a daily entry as one continous block of information to be inserted.  Of course the old method is still supported but now you have a new option.  Also you are able to mix both the old and new way using different quote sections.</p>

<p>You also have the ability to limit the number of words from a quote section to create excerpts.  This requires the use of the shortcode method.</p>

<p>Editor management is now supported in version 2.8+.  This allows you to assign editor accounts to manage the daily quote sections.</p>

<p>Isn't this quote goodness on steroids? I mean with unlimited quote sections, total category control, and total layout control with custom quote section override what else could it be?! Sold? Awesome! Well because you use Wordpress and obviously know a good content manager when you see it this plugin is available to you for the unbelievable low price of FREE! However act fast because if you don't someone else will download it before you. ;-)</p>

<p>Optional: Supports Memcached caching system for reduced database load and optimized daily information displaying.</p>

<p>Language Support: Should work for all languages that use the A-Z alphabet. Plugin only displays text entered by you. The only limitation is possible removal of unknown characters outside standard A-Z.</p>

<p>Live Site Preview: Want to see this plugin in action on a real live site? <a href="http://www.blissplan.com/">BlissPlan.com</a> and look in the right sidebar.</p>

<p>Shameless Promotion: See other <a href="http://wordpress.org/plugins/search.php?q=cleverwise">Cleverwise Wordpress Directory Plugins</a></p>

<p>Thanks for looking at the Cleverwise Plugin Series! To help out the community reviews and comments are highly encouraged.  If you can't leave a good review I would greatly appreciate opening a support thread before hand to see if I can address your concern(s).  Enjoy!</p>

== Installation ==

<ol>
<li>Upload the <strong>cleverwise-daily-quotes</strong> directory to your plugins.</li>
<li>In Wordpress management panel activate "<strong>Cleverwise Daily Quotes</strong>" plugin.</li>
<li>In the "<strong>Settings</strong>" menu a new option "<strong>Daily Quotes</strong>" will appear.</li>
<li>Once you have loaded the main panel for the plugin click on the "<strong>Help Guide</strong>" link which explains in detail how to use the plugin.</li>
</ol>

== Frequently Asked Questions ==

= Does this work with WordPress Networks (multisite)? =

Yes! It has been tested using the multisite function.

= I set the shortcode and added the filter code to my <strong>functions.php</stong> file yet nothing is displaying?! =

You probably forgot to setup the default/general layout in <strong>Settings</strong>.

= Do caching plugins help with the database load? =

Yes! If you use a Wordpress caching plugin it should help with reducing database load.  Do keep in mind this plugin also supports the Memcached caching system which will reduce the database load too.

== Screenshots ==

1. screenshot-1.png
2. screenshot-2.png
3. screenshot-3.png
4. screenshot-4.png
5. screenshot-5.png

== Changelog ==

= 3.4 =
Fixed: Cross site script vulnerability patched
Fixed: Minor bug when no content was detected using content check function

= 3.2 =
Added ability to limit number of words when using shortcode

= 3.0 =
Fixed: PHP 8 undefined error in logs<br>
Minor text updates

= 2.8 =
Added ability to allow editors to making changes<br>
Added date next to day count when viewing quote section<br>
Added warning message when no general theme is saved<br>
Multipart quotes now support fifteen (15) parts instead of ten (10)<br>
Fixed: Date over run bug when viewing<br>
Moved daily quote section shortcode and pages/posts using it to view from edit<br>
Altered theme

= 2.5 =
Fixed: PHP 7.2 Patch

= 2.4 =
Fixed: A few minor bugs<br>
Added order (weighted) numbering<br>
Altered theme

= 2.2 =
Fixed: Several notice messages have been resolved<br>
Added multipart quotes

= 2.0 =
Fixed: Multisite Network install (both methods supported)

= 1.9 =
Multisite Standard install tested<br>
Custom separator support<br>
Non leap year adjustment (Safe to use dates)<br>
Minor theme changes

= 1.8 =
Added new theme feature that alters daily quote for URL and form safe calls

= 1.7 =
Fixed: Display bug when multiple daily sections where shown<br>
Fixed: PHP error message when missing section ids

= 1.6 =
Day change now based on Wordpress Timezone setting

= 1.5 =
Plugin can now easily add missing daily content to reach 366 days

= 1.4 =
Added ability to check daily content for day count<br>
Background edits to eliminate some PHP notice messages

= 1.3 =
Ability to hide/turn off daily sections<br>
Added link to WordPress category area for easier management<br>
Shortcode support to directly load a daily section; useful for pages

= 1.2 =
An easy to use display widget has been added

= 1.1 =
Fixed: Shortcode in certain areas would cause incorrect placement

= 1.0 =
Initial release of plugin

== Upgrade Notice ==

= 1.8 =
Added new theme feature that alters daily quote for URL and form safe calls
