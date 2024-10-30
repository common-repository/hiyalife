<?PHP
//if uninstall not called from WordPress exit
if ( !defined( 'WP_UNINSTALL_PLUGIN' ) ) 
    exit();

delete_option("hylwp_user_key" );
delete_option("hylwp_secret_key" );
delete_option("hylwp_blog_id" );
delete_option("HIYA_ACCESS_TOKEN" );
delete_option("HIYA_REFRESH_TOKEN");
delete_option("messageStatus");
delete_option("lastResult" );
delete_option("hiyamsgAuth");

delete_post_meta_by_key("hiya_id" );
delete_post_meta_by_key("hiya_images" );

wp_clear_scheduled_hook("hiya_cron_auth");

?>