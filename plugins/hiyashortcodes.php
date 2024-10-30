<?php
add_action( 'init', 'setup_tinymce_button' );

function setup_tinymce_button()
{
    if (get_user_option('rich_editing') == 'true' && current_user_can('edit_posts')) {
        add_action('admin_print_scripts', 'output_tinymce_dialog_vars');
        add_filter('mce_external_plugins', 'add_tinymce_button_script');
        add_filter('mce_buttons', 'register_tinymce_button');
    }
}


    // Register our TinyMCE button
function register_tinymce_button($buttons) {
    array_push($buttons, '|', 'HiyalifeShortcodePopup');
    return $buttons;
}


    // Register our TinyMCE Script
function add_tinymce_button_script($plugin_array) {
    $plugin_array['HiyalifeShortcodeMargenn'] = plugins_url('../assets/tinymcebutton.js', __FILE__);
    return $plugin_array;
}


function output_tinymce_dialog_vars()
{
    $data = array(
        'includesUrl' => includes_url(),
        'pluginsUrl' => plugins_url()
        );

        ?>
        <script type="text/javascript">
        // <![CDATA[
        window.hiyalifeShortcodeDialogData = <?php echo json_encode($data); ?>;
        // ]]>
        </script>
        <?php
    }

    add_action('init', 'hiyalife_register_shortcodes');

    function hiyalife_register_shortcodes() {
        add_shortcode( 'hiyalifeline', 'getHiyaLifeLine' );
    }

    function getHiyaLifeLine($args, $content) {
        $tile = isset($args['tiles'])? "tiles": "";
        $button = isset($args['nobutton'])? "nobutton": "";     
        $width = isset($args['width'])  ? $args['width'] : "100%";
        $height = isset($args['height']) ? $args['height'] : "480px";
        if(isset($args['user'])){
            return '<object width="'.$width.'" height="'.$height.'" data="http://hiyalife.com/widget/user/'.$args['user'].'?'.$tile.'&'.$button.'" style="margin-top:20px;"></object>';    
        }else if(isset($args['brand'])){
            return '<object width="'.$width.'" height="'.$height.'" data="http://hiyalife.com/widget/brand/'.$args['brand'].'?'.$tile.'&'.$button.'" style="margin-top:20px;"></object>';    
        }
        return "";
    }

    ?>