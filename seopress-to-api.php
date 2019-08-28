<?php

final class SEOPress_Frontend_To_REST_API {

    public static function get_instance() {
        if ( ! ( self::$instance instanceof self ) ) {
            self::$instance = new self();
            }
            return self::$instance;
            }
    public function init() {
        if ( ! did_action( 'seopress_activation' ) ) {
            add_action( 'admin_notices', [ $this, 'admin_notice_missing_main_plugin' ] );
            return;
            }

    public function __construct() {
        add_action( 'plugins_loaded', [ $this, 'init' ] );
        }

    public function admin_notice_missing_main_plugin() {
        if ( isset( $_GET['activate'] ) ) unset( $_GET['activate'] );
        $message = sprintf(
            /* translators: 1: Plugin name 2: Elementor Pro*/
            esc_html__( '"%1$s" requires "%2$s" to be installed and activated.', 'moo-elements' ),
            '<strong>' . esc_html__( 'Moo Elements', 'moo-elements' ) . '</strong>',
            '<strong>' . esc_html__( 'Elementor Pro', 'moo-elements' ) . '</strong>'
            );
             printf( '<div class="notice notice-warning is-dismissible"><p>%1$s</p></div>', $message );
             }
}

SEOPress_Frontend_To_REST_API::instance();