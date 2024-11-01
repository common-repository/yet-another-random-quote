=== Yet Another Random Quote ===
Contributors: scarpenter, ChristianB, franky999
Donate link: https://my.fsf.org/donate
Tags: random quotes, random, quote
Requires at least: 2.5
Tested up to: 3.0.1
Stable tag: 3.1

Yet Another Random Quote, a Wordpress plugin which randomly displays quotes.

== Description ==

This plugin will let you display a random quote in your wordpress blog.

You have to add your own assortment of quotes to choose from the admin section of your blog.

You can choose where to display the quotes using the widget or the php function yarq_display() in your template.

New in version 3.1, there is the `yarq_display_all` function:

`yarq_display_all($order_by = 'id', $asc = true, $format = '')`

Which displays all quotes in a list. The first parameter takes id, author, source, or quote.  The second is for ascending or descending order, and the third lets you override the format specified in admin options.

You might call from your theme/template file like so, supplying the `<ul>` or `<ol>` wrapping tags yourself:

`<?php if ( function_exists('yarq_display_all') ) { 
  echo '<h2>All Quotes</h2>';
  echo "<ul>\n";
  yarq_display_all('id', false);
  echo "</ul>\n";
}`

Which can be styled as desired.  Alternating `<li>` items, starting with the first, will use the class "yarq-alt". (`<li class="yarq-alt">`)

Much more at: http://www.movingtofreedom.org/2010/07/11/wordpress-plugin-yarq-v3-yet-another-random-quote/

== Installation ==

1. Upload `yarq.php` to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Add the yarq-widget to the prefered position on your sidebar **or** place `<?php yarq_display(); ?>` in your template

== Frequently Asked Questions ==

Please ask questions and report bugs in wordpress.org forum for this page, or at:

http://www.movingtofreedom.org/2010/07/11/wordpress-plugin-yarq-v3-yet-another-random-quote/

== Changelog ==

= 3.1 (11/20/10) =
* New function, `yarq_display_all()` can be used to display all quotes in a list. (See description tab for options and examples.)
* Widget now has option to change the title.  (After installing/upgrading, you may need to set the initial value to "Random Quote" if you want to retain the old title and not have it be blank.)
* Auto-formatting won't be applied if an `<li>` tag is found in the quote.


= 3.0.3 (10/10/10) =
* Option to turn off auto-formatting of quotes. (Where the plugin tries to add paragraphs and line breaks to the quote itself.)

== Upgrade Notice ==

= 3.1 =
New function, "yarq_display_all()", and the widget's title can now be set (or blank).

= 3.0.3 =
No need to upgrade unless you want to have the option of turning off the autoformat feature.
