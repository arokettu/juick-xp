<?php
/**
 * Plugin Name: Juick Crossposter
 * Plugin URI: https://github.com/sandfox-im/juick-xp
 * Description: A simple Juick.com crossposter plugin
 * Version: 0.3.1
 * Author: Sand Fox
 * Author URI: http://sandfox.im/
 * License: GNU GPL v2
 */

add_action('publish_post', 'juickxp_post');

function juickxp_post($post_id)
{
    $juick = get_option('juickxp_custom_jid');
    $include_text = get_option('juickxp_include_text', false);

    if (!function_exists('xmpp_send')) // no xmpp sender plugin
    {
        return;
    }

    $tags_s = '';
    $tags = array();

    $tags_custom = explode(' ', get_option('juickxp_jtags_custom'));

    foreach ($tags_custom as $tag) {
        if (!empty($tag)) {
            $tags[] = $tag;
        }
    }

    $post = get_post($post_id);
    $post_link = get_permalink($post_id);

    if (get_option('juickxp_jtags_categories')) {
        foreach (wp_get_object_terms($post_id, 'category') as $tag) {
            $tags [] = str_replace(' ', '-', $tag->name);
        }
    }

    if (get_option('juickxp_jtags_tags')) {
        foreach (wp_get_object_terms($post_id, 'post_tag') as $tag) {
            $tags [] = str_replace(' ', '-', $tag->name);
        }
    }

    $k = 5;

    foreach ($tags as $tag) {
        if (!$k) {
            break;
        }

        $tags_s .= "*$tag ";
        $k--;
    }

    if (empty($juick)) {
        $juick = 'juick@juick.com';
    }

    if ($post->post_type != 'post') // no pages or attachments!
    {
        return;
    }

    $message = $tags_s;
    $message .= $post->post_title . "\n";

    if ($include_text) {
        $message .= "\n" . strip_tags($post->post_excerpt ? $post->post_excerpt : $post->post_content) . "\n\n";
    }

    $message .= $post_link;

    xmpp_send($juick, $message);
}

/* ----- settings section -------- */

add_action('admin_menu', 'juickxp_create_menu');

function juickxp_create_menu()
{
    if (!function_exists('xmpp_send')) // in case XMPP Enabled is not present
    {
        add_submenu_page('plugins.php', 'Juick Crossposter Settings', 'Juick Crossposter', 'administrator', __FILE__, 'juickxp_settings_page');
    }

    add_submenu_page('xmpp-enabled', 'Juick Crossposter Settings', 'Juick Crossposter', 'administrator', __FILE__, 'juickxp_settings_page');
    add_action('admin_init', 'register_juickxp_settings');
}


function register_juickxp_settings()
{
    register_setting('juickxp-settings', 'juickxp_include_text');

    register_setting('juickxp-settings', 'juickxp_jtags_custom');
    register_setting('juickxp-settings', 'juickxp_jtags_categories');
    register_setting('juickxp-settings', 'juickxp_jtags_tags');

    register_setting('juickxp-settings', 'juickxp_custom_jid');
}

function juickxp_settings_page()
{

    ?>
    <div class="wrap">
    <h2>Juick XP Settings</h2>
    <?php if (!function_exists('xmpp_send')):
        ?><p style="color: red">Error: <strong>XMPP Enabled</strong> is not installed.
        Please install the <strong>XMPP Enabled</strong> plugin for this plugin to work</p>

        <ul>
            <li><a href="http://wordpress.org/extend/plugins/xmpp-enabled/">
                    http://wordpress.org/extend/plugins/xmpp-enabled/</a>
            </li>
            <li>
                <a href="http://sandfox.org/projects/xmpp-enabled.html">
                    http://sandfox.org/projects/xmpp-enabled.html</a>
            </li>
        </ul>

        <hr/>

    <?php
    endif;

    ?>

    <form method="post" action="options.php">
        <?php settings_fields('juickxp-settings'); ?>
        <table class="form-table">
            <tr valign="top">
                <th scope="row">Custom tags (separated by space)<br/>
                    <small>(prefix * is added automatically)</small>
                </th>
                <td>
                    <input type="text" name="juickxp_jtags_custom"
                           value="<?php echo get_option('juickxp_jtags_custom', 'wp-juick-xp'); ?>"/>
                </td>
            </tr>
            <tr>
                <th scope="row" colspan="2">
                    <input type="checkbox" value="1" name="juickxp_jtags_categories" id="juickxp_jtags_categories"
                        <?php if (get_option('juickxp_jtags_categories', true)) echo 'checked="checked"' ?>
                        /> <label for="juickxp_jtags_categories">Include post categories as Juick tags</label>
                </th>
            </tr>
            <tr>
                <th scope="row" colspan="2">
                    <input type="checkbox" value="1" name="juickxp_jtags_tags" id="juickxp_jtags_tags"
                        <?php if (get_option('juickxp_jtags_tags', true)) echo 'checked="checked"' ?>
                        /> <label for="juickxp_jtags_tags">Include post tags as Juick tags</label>
                </th>
            </tr>
            <tr>
                <th scope="row" colspan="2">
                    The order is {custom, categories, tags} limited by 5
                </th>
            </tr>
            <tr>
                <th scope="row" colspan="2">
                    <input type="checkbox" value="1" name="juickxp_include_text" id="juickxp_include_text"
                        <?php if (get_option('juickxp_include_text', false)) echo 'checked="checked"' ?>
                        /> <label for="juickxp_include_text">Include excerpt<br/>
                        <small>Experimental feature</small>
                    </label>
                </th>
            </tr>
            <tr valign="top">
                <th scope="row">Custom Juick JID<br/>
                    <small>Leave blank for juick@juick.com</small>
                </th>
                <td>
                    <input type="text" name="juickxp_custom_jid"
                           value="<?php echo get_option('juickxp_custom_jid'); ?>"/>
                </td>
            </tr>
        </table>
        <p class="submit">
            <input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>"/>
        </p>

    </form>
    </div><?php
}
