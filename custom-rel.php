<?php
/*
Plugin Name: Custom Rel
Plugin URI: http://www.hostscope.com/wordpress-plugins/the-custom-rel-wordpress-plugin/
Description: Set your own relation links in your headers (or default back to WP normal behavior).
Version: 1.0.0
Author: John Leavitt
Author URI: http://www.jrrl.com
*/



/* --------------------------------------------------
 * custom_rel_get_values ()
 *
 * Gets the title and link for the post and relation (if any).
 *
 */

function custom_rel_get_values ($id, $rel) {
  global $wpdb;
  $query = "SELECT meta_value FROM $wpdb->postmeta WHERE meta_key = '_custom_rel_$rel' AND post_id='$id'";
  $values = $wpdb->get_var($query);
  if ($values) {
    return (explode ("\n", $values));
  } else {
    return (array(null, null));
  }
}



/* --------------------------------------------------
 * custom_rel_link ()
 *
 * Creates the link tag, if custom values exist.
 *
 */

function custom_rel_link ($link, $rel) {
  global $post;
  $postid = $post->ID;
  error_log ("custom_rel_link [$link] [$rel]");
  list($title,$href) = custom_rel_get_values ($postid, $rel);
  if (empty ($href)) {
    return $link;
  } else {
    return "<link rel='$rel' title='$title' href='$href' />\n";
  }
}
  


/* --------------------------------------------------
 * custom_rel_*_post_rel_link ()
 *
 * The actual filter functions, which just call custom_rel_link.
 *
 */

function custom_rel_next_post_rel_link ($link) {
  return (custom_rel_link ($link, 'next'));
}

function custom_rel_prev_post_rel_link ($link) {
  return (custom_rel_link ($link, 'prev'));
}



/* --------------------------------------------------
 * custom_rel_custom_meta_box ()
 *
 * Generates the sidebar meta box.
 *
 */

function custom_rel_custom_meta_box () {
  global $wpdb, $post, $wp_locale;
  $id = $post->ID;

  error_log("adding custom meta box\n\n");

  echo '<div style="text-align:right; padding-right: 20px; width: 95%;" />';
  
  foreach (array('next', 'prev') as $rel) {
    list($title, $href) = custom_rel_get_values ($id, $rel);
    if (empty($title)) {
      $title = '';
      $href = '';
    }

    echo "'$rel' title: <input type=\"text\" id=\"title$rel\" name=\"title$rel\" value=\"$title\" /><br/>\n";
    echo "'$rel' link: <input type=\"text\" id=\"href$rel\" name=\"href$rel\" value=\"$href\" /><br/>\n";

  }
  echo "</div>";
  echo "<p>Leave blank for normal WordPress behavior.</p>";
}



/* --------------------------------------------------
 * custom_rel_add_box ()
 *
 * The action function that inserts the box created by custom_rel_custom_meta_box.
 *
 */

function custom_rel_add_box () {
  add_meta_box('custom_rel', __('Custom Link Relations'), 'custom_rel_custom_meta_box', 'post', 'side', 'core');
}



/* --------------------------------------------------
 * custom_rel_save_values ()
 *
 * This saves the custom rel values.
 *
 */

function custom_rel_save_values ($id) {
  foreach (array('next', 'prev') as $rel) {
    $title = $_POST['title'.$rel];
    $link  = $_POST['href'.$rel];
    custom_rel_set_values ($id, $rel, $title, $link);
  }
}




/* --------------------------------------------------
 * custom_rel_set_values ()
 *
 * This set the custom values.
 *
 */

function custom_rel_set_values ($id, $rel, $title, $link) {
  global $wpdb;
  $newvalue = $title . "\n" . $link;
  list($oldtitle, $oldlink) = custom_rel_get_values ($id, $rel);
  if (empty($oldlink)) {
    $query = "INSERT INTO {$wpdb->postmeta} (post_id,meta_key,meta_value) VALUES ('$id', '_custom_rel_$rel', '$newvalue');";
  } else {
    if (empty($link)) {
      $query = "DELETE FROM {$wpdb->postmeta} WHERE post_id='$id' AND meta_key='_custom_rel_$rel'";
    } else {
      $query = "UPDATE {$wpdb->postmeta} SET meta_value='$newvalue' WHERE post_id='$id' AND meta_key='_custom_rel_$rel'";
    }
  }
  $wpdb->query ($query);
}


add_action ('dbx_post_advanced',         'custom_rel_add_box');
add_action ('save_post',                  'custom_rel_save_values');
add_filter ('next_post_rel_link',         'custom_rel_next_post_rel_link');
add_filter ('prev_post_rel_link',         'custom_rel_prev_post_rel_link');



?>