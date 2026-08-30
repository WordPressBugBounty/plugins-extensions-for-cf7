<?php

namespace Cf7_Extensions\Admin;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly.

class Extensions_Cf7_Recomendation{

	// Get Instance
    private static $_instance = null;

    public static function instance(){
        if( is_null( self::$_instance ) ){
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    function __construct(){
        add_action('init', [$this, 'plugin_recommendations']);
    }

    /**
     * [plugin_recommendations]
     * @return [void]
     */
    public function plugin_recommendations(){
        $get_instance = Recommended_Plugins::instance( 
            array( 
                'text_domain'       => 'cf7-extensions', 
                'parent_menu_slug'  => 'contat-form-list', 
                'menu_capability'   => 'manage_options', 
                'menu_page_slug'    => 'cf7-extensions-recommendations',
                'priority'          => 222,
                'assets_url'        => CF7_EXTENTIONS_PL_URL.'admin/assets',
                'hook_suffix'       => 'cf7-extensions_page_cf7-extensions-recommendations'
            )
        );

        // ShopLentor is a WooCommerce plugin — only worth featuring in the
        // primary "Recommended Plugins" tab when WooCommerce is actually
        // active; otherwise it stays discoverable under the "WooCommerce" tab.
        $woocommerce_active = class_exists( 'WooCommerce' );
        $shoplentor_entry   = array(
            'slug'      => 'woolentor-addons',
            'location'  => 'woolentor_addons_elementor.php',
            'name'      => esc_html__( 'ShopLentor – All-in-One WooCommerce Growth & Store Enhancement Plugin', 'cf7-extensions' )
        );

        $get_instance->add_new_tab( array(

            'title' => esc_html__( 'Recommended Plugins', 'cf7-extensions' ),
            'active' => true,
            'plugins' => array_merge(
                $woocommerce_active ? array( $shoplentor_entry ) : array(),
                array(
                    array(
                        'slug'      => 'support-genix-lite',
                        'location'  => 'support-genix-lite.php',
                        'name'      => esc_html__( 'Support Genix – Helpdesk, AI Chatbot, Knowledge Base & Customer Support Ticketing System', 'cf7-extensions' )
                    ),
                    array(
                        'slug'      => 'hashbar-wp-notification-bar',
                        'location'  => 'init.php',
                        'name'      => esc_html__( 'HashBar – Announcement, Notification Bar & Popup Campaign', 'cf7-extensions' )
                    ),
                    array(
                        'slug'      => 'wp-plugin-manager',
                        'location'  => 'plugin-main.php',
                        'name'      => esc_html__( 'WP Plugin Manager – Deactivate plugins per page', 'cf7-extensions' )
                    ),
                    array(
                        'slug'      => 'ht-contactform',
                        'location'  => 'contact-form-widget-elementor.php',
                        'name'      => esc_html__( 'HT Contact Form – Drag & Drop Form Builder for WordPress', 'cf7-extensions' )
                    ),
                    array(
                        'slug'      => 'cookieray',
                        'location'  => 'cookieray.php',
                        'name'      => esc_html__( 'CookieRay – Cookie Banner for Cookie Consent (GDPR/CCPA Compliant)', 'cf7-extensions' )
                    ),
                    array(
                        'slug'      => 'kelune-crm',
                        'location'  => 'kelune-crm.php',
                        'name'      => esc_html__( 'Kelune CRM – Contact Management, Email Marketing, Newsletter & Marketing Automation', 'cf7-extensions' )
                    ),
                )
            )

        ) );

        $get_instance->add_new_tab( array(
            'title' => esc_html__( 'WooCommerce', 'cf7-extensions' ),
            'plugins' => array_merge(
                $woocommerce_active ? array() : array( $shoplentor_entry ),
                array(
                    array(
                        'slug'      => 'whols',
                        'location'  => 'whols.php',
                        'name'      => esc_html__( 'Whols – Wholesale Prices and B2B Store Solution for WooCommerce', 'cf7-extensions' )
                    ),
                    array(
                        'slug'      => 'recurio',
                        'location'  => 'recurio.php',
                        'name'      => esc_html__( 'Recurio – Ultimate Subscription for WooCommerce', 'cf7-extensions' )
                    ),
                )
            )
        ) );

        $get_instance->add_new_tab(array(
            'title' => esc_html__( 'Popular', 'cf7-extensions' ),
            'plugins' => array(
                array(
                    'slug'      => 'ht-mega-for-elementor',
                    'location'  => 'htmega_addons_elementor.php',
                    'name'      => esc_html__( 'HT Mega Addons for Elementor – Elementor Widgets & Template Builder', 'cf7-extensions' )
                ),
                array(
                    'slug'      => 'wp-plugin-manager',
                    'location'  => 'plugin-main.php',
                    'name'      => esc_html__( 'WP Plugin Manager – Deactivate plugins per page', 'cf7-extensions' )
                ),
                array(
                    'slug'      => 'ht-easy-google-analytics',
                    'location'  => 'ht-easy-google-analytics.php',
                    'name'      => esc_html__( 'HT Easy GA4 – Google Analytics WordPress Plugin', 'cf7-extensions' )
                ),
                array(
                    'slug'      => 'cookieray',
                    'location'  => 'cookieray.php',
                    'name'      => esc_html__( 'CookieRay – Cookie Banner for Cookie Consent (GDPR/CCPA Compliant)', 'cf7-extensions' )
                ),
                array(
                    'slug'      => 'insert-headers-and-footers-script',
                    'location'  => 'init.php',
                    'name'      => esc_html__( 'Insert Headers and Footers Code – HT Script', 'cf7-extensions' )
                ),
                array(
                    'slug'      => 'pixelavo',
                    'location'  => 'pixelavo.php',
                    'name'      => esc_html__( 'Pixelavo – Server Side Tracking & Pixel + AI Ads Tools', 'cf7-extensions' )
                ),
                array(
                    'slug'      => 'courseglade-lms',
                    'location'  => 'courseglade-lms.php',
                    'name'      => esc_html__( 'CourseGlade LMS – Online Course & eLearning Platform', 'cf7-extensions' )
                ),
            )
        ));



    }

}

Extensions_Cf7_Recomendation::instance();

?>