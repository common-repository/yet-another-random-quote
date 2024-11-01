<?php
/*
Plugin Name: Yet Another Random Quote
Plugin URI: http://www.movingtofreedom.org/2010/07/11/wordpress-plugin-yarq-v3-yet-another-random-quote
Description: A Wordpress plugin which randomly displays quotes. YARQ!
Version: 3.1
Author: Scott Carpenter, Christian Beer, Frank van den Brink
Author URI: http://www.movingtofreedom.org
License: GPL2

	Copyright 2006 Frank van den Brink (frank@fsfe.org)
	Copyright 2010 Christian Beer (open.source.notes@gmail.com)
	Copyright 2010 Scott Carpenter (scottc@movingtofreedom.org)

	This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.

*/

//
// Table name
//
global $wpdb;
define('YARQ_QUOTES_TABLE', $wpdb->prefix . 'yarq_quotes');
define('YARQ_DB_VERSION', '2.5');

//
// Install the plugin
//
function yarq_install()
{
	global $wpdb;

	add_option('yarq_format', '<blockquote id="yarq_quote" cite="%source%">
<p>%quote%</p>
</blockquote>
<p id="yarq_author">by %author%</p>');
	add_option("yarq_db_version", YARQ_DB_VERSION);
	add_option("yarq_separator", ", ");
	add_option("yarq_autoformat", 1);
	// clean installation
	if ($wpdb->get_var("show tables like '" . YARQ_QUOTES_TABLE . "'") != YARQ_QUOTES_TABLE) {
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta("CREATE TABLE " . YARQ_QUOTES_TABLE . " (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			author VARCHAR(255) NOT NULL,
			source VARCHAR(255) NOT NULL,
			quote TEXT NOT NULL,
			UNIQUE KEY id (id)
		);");

		$wpdb->query("INSERT INTO " . YARQ_QUOTES_TABLE . " (author, source, quote) VALUES ('WordPress', 'http://wordpress.org', 'Code is poetry.');");
	}
	// update table (if needed)
	$installed_ver = get_option("yarq_db_version");
    if( $installed_ver != YARQ_DB_VERSION ) {
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta("CREATE TABLE " . YARQ_QUOTES_TABLE . " (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			author VARCHAR(255) NOT NULL,
			source VARCHAR(255) NOT NULL,
			quote TEXT NOT NULL,
			UNIQUE KEY id (id)
		);");

      update_option("yarq_db_version", YARQ_DB_VERSION);
  }
}

//
// Generate links in the admin menu to the YARQ admin pages
//
function yarq_generate_admin_menu()
{
	if (function_exists('add_options_page'))
	{
		add_options_page(__('Yet Another Random Quote', 'yarq'), __('YARQ', 'yarq'), 10, basename(__FILE__), 'yarq_admin_options');
		add_management_page(__('Manage Random Quotes', 'yarq'), __('Quotes', 'yarq'), 10, basename(__FILE__), 'yarq_admin_manage');
	}
}

//
// YARQ Admin options panel
//
function yarq_admin_options()
{
	if (isset($_POST['update_options']))
	{
		update_option('yarq_format', $_POST['yarq_format']);
		update_option('yarq_separator', $_POST['yarq_separator']);
		update_option('yarq_autoformat', $_POST['yarq_autoformat']);
		echo '<div class="updated"><p><strong>' . __('Options updated.', 'yarq') . '</strong></p></div>';
	}

	echo '<div class="wrap">' . "\n";
	echo '<h2>' . __('Yet Another Random Quote Options', 'yarq') . "</h2>\n";

	echo '<form action="" method="post">' . "\n";

	echo '<fieldset class="options">' . "\n";
	echo '<legend>' . __('Format', 'yarq') . "</legend>\n";
	echo '<p>' . __('The format and style used to display the quote. <code>%quote%</code> is replaced with the random quote, <code>%author%</code> is replaced by the name of the author of the quote, and <code>%source%</code> is replaced by the source of the quote.</p><p><code>%opt_separator%</code> is for an optional separator between author and source. See option below.', 'yarq') . "</p>\n";
	echo '<p><textarea id="yarq_format" name="yarq_format" cols="60" rows="4" style="width: 98%;" class="code">' . stripslashes(get_option('yarq_format')) . "</textarea></p>\n";
	
	if (get_option('yarq_autoformat') == 1) {
		$chk = 'checked="checked"';
	}
	echo '<p><input type="checkbox" id="yarq_autoformat" name="yarq_autoformat" class="code" value="1"' . 
	     "$chk /> Autoformat quotes. (When adding or editing quotes, the plugin will try adding paragraphs &lt;p&gt; and line breaks &lt;br /&gt; where they make sense. Autoformatting won't be applied when &lt;li&gt; tags are detected in the quote.)</p>\n";	
	echo "</fieldset>\n";

	echo '<fieldset class="options">' . "\n";
	echo '<legend>' . __('Separator', 'yarq') . "</legend>\n";
	echo '<p>' . __('This separator is used between author and source when <code>%opt_separator%</code> is present in Format above. It will be replaced with this string if author and source are both present, and an empty string if one or both are absent.', 'yarq') . "</p>\n";
	echo '<p><input type="text" id="yarq_separator" name="yarq_separator" size="10" maxlength="10" class="code" value="' . stripslashes(get_option('yarq_separator')) . '"'." /></p>\n";
	echo "</fieldset>\n";

	echo '<div class="submit"><input type="submit" name="update_options" value="' . __('Update Options', 'yarq') . '" /></div>' . "\n";

	echo "</form></div>\n";
}

//
// YARQ Admin manage quotes panel
//
function yarq_admin_manage()
{
	global $wpdb;

	//
	// Delete a quote
	//
	if (isset($_GET['delete']))
	{
		$wpdb->query("DELETE FROM " . YARQ_QUOTES_TABLE . " WHERE id = '" . intval($_GET['delete'])  . "'");
		echo '<div class="updated"><p><strong>' . sprintf(__('Quote %d has been deleted.', 'yarq'), intval($_GET['delete'])) . '</strong></p></div>';
	}

	//
	// Add a quote
	//
	if (isset($_POST['add']))
	{

		$errors = array();

		if (empty($_POST['yarq_quote'])) {
			$errors[] = __('You did not enter a quote.', 'yarq');
		}elseif (!empty($_POST['yarq_qid'])) {
			$qid = intval($_POST['yarq_qid']);
			$the_quote = $wpdb->get_row("SELECT * FROM " . YARQ_QUOTES_TABLE . " WHERE id = $qid");
			if (empty($the_quote)) {
				$errors[] = sprintf(__('Quote ID %d does not exist. (If adding a <i>new</i> quote, leave QID field empty.)', 'yarq'), $qid);
			}
		}


		$quo = trim($_POST['yarq_quote']);

		if (get_option('yarq_autoformat') == 1) {
			$quo = format_quote($quo);
		}

		if (count($errors) > 0)
		{
			echo '<div class="error"><ul>' . "\n";
			foreach ($errors as $error)
			{
				echo '<li><strong>' . __('ERROR', 'yarq') . '</strong>: ' . $error . "</li>\n";
			}
			echo "</ul></div>\n";
		}
		else
		{
			$author = $_POST['yarq_author'];
			$source = $_POST['yarq_source'];
			// don't know if these could ever be "null", but let's make sure
			if (is_null($author)) $author = "";
			if (is_null($source)) $source = "";

			// use "prepare" to prevent SQL injection attacks
			if (empty($qid)) {

				$wpdb->query($wpdb->prepare("INSERT INTO " . YARQ_QUOTES_TABLE . " (author, source, quote)
					VALUES (%s, %s, %s)", $author, $source, $quo));

				echo '<div class="updated"><p><strong>' . __('Quote added.', 'yarq') . '</strong></p></div>';

			} else {

				$wpdb->query($wpdb->prepare("UPDATE " . YARQ_QUOTES_TABLE . " SET author = %s, source = %s, quote = %s WHERE id = %d", $author, $source, $quo, $qid));

				echo '<div class="updated"><p><strong>' . sprintf(__('Quote %d updated.', 'yarq'), $qid) . '</strong></p></div>';
			}
		}
	} // end add

	// 7-3-10 edit quote feature -- populate form with existing quote if there is one
	global $the_quote;
	if (isset($_GET['edit']))	{
		$qid_edit = intval($_GET['edit']);
		$the_quote = $wpdb->get_row("SELECT * FROM " . YARQ_QUOTES_TABLE . " WHERE id = $qid_edit");
		if (empty($the_quote)) {
			echo '<div class="error"><p>' . sprintf(__('<strong>UPDATE ERROR:</strong> Quote ID %d does not exist.', 'yarq'), $qid_edit) . '</p></div>';

		}
	}

	//
	// Add form
	//
	echo '<div class="wrap">' . "\n";
	echo '<h2>' . __('Add Quote', 'yarq') . "</h2>\n";

	echo '<form action="" method="post">' . "\n";

	echo '<table class="editform" width="100%" cellspacing="2" cellpadding="5">' . "\n";
	echo '<tr><th scope="row" width="10%">' . __('Quote', 'yarq') . '</th><td width="80%"><textarea id="yarq_quote" name="yarq_quote" rows="12" cols="60" style="width: 98%;" class="code">' . htmlspecialchars(stripslashes($the_quote->quote), ENT_NOQUOTES) . '</textarea></td></tr>' . "\n";
	echo '<tr><th scope="row">' . __('Author', 'yarq') . '</th><td><input name="yarq_author" type="text" id="yarq_author" value="' . stripslashes($the_quote->author) . '" size="70" /></td></tr>' . "\n";
	echo '<tr><th scope="row">' . __('Source', 'yarq') . '</th><td><input name="yarq_source" type="text" id="yarq_source" value="' . stripslashes($the_quote->source) . '" size="70" /></td></tr>' . "\n";
	echo '<tr><th scope="row">' . __('QID', 'yarq') . '</th><td><input name="yarq_qid" type="text" id="yarq_qid" value="' . $the_quote->id . '" size="4" /> (' . __('for updates only') . ")</td></tr>\n";
	echo "</table>\n";

	echo '<div class="submit"><input type="submit" name="add" value="' . __('Add / Update Quote', 'yarq') . '" /></div>' . "\n";

	echo "</form></div>\n";

	echo "</div>\n";

	//
	// List quotes
	//

	echo '<div class="wrap">' . "\n";
	echo '<h2>' . __('Manage Quotes', 'yarq') . "</h2>\n";

	$quotes = $wpdb->get_results("SELECT * FROM " . YARQ_QUOTES_TABLE . " ORDER BY ID DESC");
	if ($quotes)
	{
		echo '<table id="the-list-x" width="100%" cellpadding="3" cellspacing="3">' . "\n";
		echo "<tr>\n";
		echo '<th scope="col">' . __('QID', 'yarq') . "</th>\n";
		echo '<th scope="col">' . __('Quote', 'yarq') . "</th>\n";
		echo '<th scope="col" style="text-align: left">' . __('Author', 'yarq') . "</th>\n";
		echo '<th scope="col" style="text-align: left">' . __('Source', 'yarq') . "</th>\n";
		echo '<th scope="col" colspan="2">' . __('Operations', 'yarq') . "</th>\n";
		echo "</tr>\n";

		$alternate = true;
		foreach($quotes as $quote)
		{
			echo '<tr id="quote-' . $quote->id . '"';
			if ($alternate)
			{
				echo ' class="alternate"';
				$alternate = false;
			}
			else
			{
				$alternate = true;
			}

			echo ">\n";
			echo '<th scope="row">' . $quote->id . "</th>\n";
			echo '<td>' . stripslashes($quote->quote) . "</td>\n";
			echo '<td>' . stripslashes($quote->author) . "</td>\n";
			echo '<td>' . stripslashes($quote->source) . "</td>\n";

			echo '<td><a href="edit.php?page=yarq.php&amp;delete=' . $quote->id . '" title="' . sprintf(__('Delete quote #%d.', 'yarq'), $quote->id) . '" class="delete">' . __('Delete', 'yarq') . "</a></td>\n";

			echo '<td>&nbsp;&nbsp;&nbsp;<a href="edit.php?page=yarq.php&amp;edit=' . $quote->id . '" title="' . sprintf(__('Edit quote #%d.', 'yarq'), $quote->id) . '" class="edit">' . __('Edit', 'yarq') . "</a></td>\n";
			echo "</tr>\n";
		}

		echo "</table>\n";
	}
	else
	{
		echo '<p><strong>' . __('No quotes found.', 'yarq') . '</strong></p>';
	}

	echo "</div>\n";
}

function format_quote($quote)
{
	// 11-19-10 -- don't format if there are <li> items.  (Other exceptions?)
	if (stripos($quote, '<li') !== false) {
		return $quote;
	}

	// remove any trailing br's (they'll get added back in)
	$quote = preg_replace('|<br\ ?/?>   # matches variety of br tags, e.g. <br />, <br/>, <br>
	                        (			# capture newline to \1 to be preserved in replace
	                       	  (\r)?   	# optional windows carriage return
	                          \n      	# linefeed as used by win and unix
	                        )
	                       |x', '\\1', $quote); // x = extended/verbose mode
	// remove whitespace between any paragraphs (2 newlines will be added back in later)
	$quote = preg_replace('|</p>\s+<p|', '</p><p', $quote);

	# <p> to start the whole thing if not already there
	$quote = preg_replace('|^(?!<p)|', '<p>', $quote);	# negative lookahed (?!)
	# </p> to end the whole thing if not already there
	$quote = preg_replace('|(?<!</p>)$|', '</p>', $quote);	# negative lookbehind (?<!)

	# create paragraph beginnings
	$quote = preg_replace('|(					# capture newlines to \1
							  ((\r)?\n){2,}		# 2 or more newlines means new paragraph
							)
							(?!<p)				# negative lookahead to make sure no <p> already
						   |x', '\\1<p>', $quote);	// preserve newlines so next line can do the ends
	# create paragraph ends (causes dupe closing </p> in some cases)
	$quote = preg_replace('|(?<!</p>)           # negative lookbehind to make sure no </p> already
							(((\r)?\n){2,})		# 2 or more newlines means new paragraph
						   |x', '</p>', $quote);

	# get rid of dupe paragraph endings caused by last step
	$quote = preg_replace('|</p>\s*</p>|', '</p>', $quote);	# clumsy workaround -- couldn't fix dupe </p>s
	// remove whitespace between any paragraphs again, to be added back in...
	// (why? to make sure we have exactly 2 newlines between each paragraph)
	$quote = preg_replace('|</p>\s+<p|', '</p><p', $quote);
	# add line breaks at end of lines
	$quote = preg_replace('|(?<!</p>)			# negative lookbehind check for </p>; no br if </p>
							((\r)?\n)			# win/unix newline
						   |x', '<br />\\1', $quote);
    # add newlines between paragraphs
	$quote = preg_replace('|</p><p>|', "</p>\n\n<p>", $quote);
	// note: using single quotes didn't work as expected: '</p>\n\n<p>' (also tried '</p>\\n\\n<p>')

	return $quote;
}

//
// Display a random quote
// precedence of GET params: (1) qid (2) who (3) src
//
function yarq_display($format = '')
{
	global $wpdb;

	if (empty($format)) {
		$format = stripslashes(get_option('yarq_format'));
	}

	if (isset($_GET['qid'])) {

		$quote_id = intval($_GET['qid']); // intval for safety's sake
		$quote = $wpdb->get_row("SELECT * FROM " . YARQ_QUOTES_TABLE . " WHERE id = " . $quote_id);
		if (empty($quote)) {
			echo sprintf(__('No quote found with id = %d.', 'yarq'), $quote_id);
			return;
		}
	} else {

		// see end of yarq.php for example of specifying the SQL "where" clause
		// in functions.php (and more about this at movingtofreedom.org plugin page)
		$where= '';
		if (function_exists('yarq_get_where_clause')) { $where = yarq_get_where_clause(); }

		$quote = $wpdb->get_row("SELECT * FROM " . YARQ_QUOTES_TABLE . $where . " ORDER BY RAND() LIMIT 1");
	}
	
	$output = yarq_format_quote($quote->quote, $quote->author, $quote->source, $format);

	// see end of yarq.php for example of specifying author and source links
	// in functions.php (and more at movingtofreedom.org plugin page)

	//replace author with link if one of the included authors
	if (function_exists('yarq_get_author')) { yarq_get_author($quote->author, $output); }
	//replace source with link if one of the included sources
	if (function_exists('yarq_get_source')) { yarq_get_source($quote->source, $output); }

	echo " <!-- id = " . $quote->id . " -->\n" . $output;
}

function yarq_format_quote($the_quote, $the_author, $the_source, $format)
{
	$output = str_replace('%quote%', wptexturize(stripslashes($the_quote)), $format);
	if ( !empty($the_author) ) {
		$output = str_replace('%author%', wptexturize(stripslashes($the_author)), $output);
	} elseif (empty($the_source)) {
		$output = str_replace('%author%', __('unknown', 'yarq'), $output);
	} else {
		$output = str_replace('%author%', "", $output);
	}
	if ( !empty($the_author) && !empty($the_source) ) {
		$output = str_replace('%opt_separator%', get_option('yarq_separator'), $output);
	} else {
		$output = str_replace('%opt_separator%', "", $output);
	}
	return str_replace('%source%', wptexturize(stripslashes($the_source)), $output);
}

// order by options: (1) id (2) author (3) source (4) quote
function yarq_display_all($order_by = 'id', $asc = true, $format = '')
{
	global $wpdb;

	if (empty($format)) {
		$format = stripslashes(get_option('yarq_format'));
	}

	$order_by = strtolower($order_by);
	if ($order_by !== 'id' && $order_by !== 'author' &&
	    $order_by !== 'source' && $order_by !== 'quote') {
		$order_by = 'id';
	}

	if (!$asc) {
		$dir = 'desc';
	} else {
		$dir = 'asc';
	}
	
	$quotes = $wpdb->get_results("SELECT * FROM " . YARQ_QUOTES_TABLE . " ORDER BY $order_by $dir");
		
	if ($quotes) {
		// ol or ul is set in template file surrounding this call
		$alt = "";
		foreach($quotes as $quote) {
			if (empty($alt)) {
				$alt = ' class="yarq-alt"';
			} else {
				$alt = "";
			}
			echo '<li' . $alt . '>' . yarq_format_quote($quote->quote, $quote->author, $quote->source, $format) . "</li>\n";
		}
	} else {
		echo '<p><b>No quotes found.</b></p>';
	}
}

// 11-19-10 -- use new 2.8+ widget stuff, from:
// http://codex.wordpress.org/Widgets_API
// (which now gives us an option to set the title...)
/**
 * YarqWidget Class
 */
class YarqWidget extends WP_Widget {
    /** constructor */
    function YarqWidget() {
        parent::WP_Widget(false, $name = 'Random Quote');	
    }

    /** @see WP_Widget::widget */
    function widget($args, $instance) {		
        extract( $args );
        $title = apply_filters('widget_title', $instance['title']);
        ?>
              <?php echo $before_widget; ?>
                  <?php if ( $title )
                        echo $before_title . $title . $after_title; ?>
                  <?php yarq_display(); ?>
              <?php echo $after_widget; ?>
        <?php
    }

    /** @see WP_Widget::update */
    function update($new_instance, $old_instance) {				
	$instance = $old_instance;
	$instance['title'] = strip_tags($new_instance['title']);
        return $instance;
    }

    /** @see WP_Widget::form */
    function form($instance) {				
        $title = esc_attr($instance['title']);
        ?>
            <p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?> <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" /></label></p>
        <?php 
    }

} // class YarqWidget

//
// Add hooks
//
register_activation_hook(__FILE__,'yarq_install');
add_action('admin_menu', 'yarq_generate_admin_menu');
add_action('widgets_init', create_function('', 'return register_widget("YarqWidget");'));
load_plugin_textdomain('yarq');

/*
// To be placed in functions.php if you want to alter probabilities and modify
// the where clause to look for specific authors or sources. (Or you could use
// them right here, but keep in mind that updates to yarq.php will break things.)

function yarq_get_where_clause()
{
	if (isset($_GET['who']) && function_exists('yarq_get_author')) {
		$author = yarq_get_author($_GET['who'], $output);
	}
	if (isset($_GET['src']) && function_exists('yarq_get_source')) {
		$source = yarq_get_source($_GET['src'], $output);
	}
	if (empty($author) && empty($source)) {
		// reduce the odds of getting Rush
		$rnd = rand(1,8);
		if ($rnd == 1) {
			$author = 'Rush';
		} else {
			$author_not = 'Rush';
		}
	}
	if (!empty($author)) {
		$where = " WHERE author = '$author'";
	} elseif (!empty($author_not)) {
		$where = " WHERE author != '$author_not'";
	} elseif (!empty($source)) {
		$where = " WHERE source = '$source' ";
	}
	return $where;
}

// used in two ways: 1) see if an author is marked for the link treatment and
//                      return name for use in SQL, and
//                   2) replace author in output string with the link
//
// assumes no single or double quotes or html already in author name; just plain text
// will have to enhance for other scenarios if/when necessary
function yarq_get_author($a, &$output)
{
	$author_param = str_replace(' ', '-', strtolower($a));
	switch($author_param) {
		case 'author-one':		$author = 'Author One';		break;
		case 'author-two':		$author = 'Author Two';		break;
		// etcetera
	}

	if (!empty($author)) {
		$output = str_replace($author, '<a href="?who=' . $author_param . '">' . $author . '</a>', $output);
	}
	return $author;
}

// used in two ways: 1) see if a source is marked for the link treatment and return text for use in SQL, and
//                   2) replace source in output string with the link
// assumes no single or double quotes or html already in source; just plain text
// will have to enhance for other scenarios if/when necessary
function yarq_get_source($s, &$output)
{
	$source_param = str_replace(' ', '-', strtolower($s));
	switch($source_param) {
		case 'source-one':		$author = 'Source One';		break;
		case 'source-two':		$author = 'Source Two';		break;
	}

	if (!empty($source)) {
		$output = str_replace($source, '<a href="?src=' . $source_param . '">' . $source . '</a>', $output);
	}

	return $source;
}
*/
?>
