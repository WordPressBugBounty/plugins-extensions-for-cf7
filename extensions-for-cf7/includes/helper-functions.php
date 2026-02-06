<?php
/**
 *  @phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
 */

/**
 * [extcf7_clean]
 * @param  [JSON] $var
 * @return [array]
 */
function extcf7_clean( $varr ) {
    if ( is_array( $varr ) ) {
        return array_map( 'extcf7_clean', $varr );
    } else {
        return is_scalar( $varr ) ? sanitize_text_field( $varr ) : $varr;
    }
}

/**
 * Get option value
 * 
 * Look at the new option name first
 * if does not exists that option, then look the old option key
 *
 * @return string
 */
if( !function_exists('htcf7ext_get_option') ){
    function htcf7ext_get_option( $section = '', $option_key = '', $default = '' ){
        $new_options = array();
    
        if( $section === 'htcf7ext_opt' ){
            $new_options = get_option('htcf7ext_opt');
        }
    
        if( $section === 'htcf7ext_opt_extensions' ){
            $new_options = get_option('htcf7ext_opt_extensions');
        }
    
        // 1. look for new settings data
        // 2. look for old settings data
        // 3. look for default param
    
        if( isset($new_options[$option_key]) ){
            return $new_options[$option_key];
        } elseif( get_option($option_key) ) {
            return get_option($option_key);
        } elseif( $default ){
            return $default;
        }
    
        return '';
    }
}

/**
 * Get module option value
 * @input section, option_id, option_key, default
 * @return mixed
 */
if( !function_exists('htcf7ext_get_module_option') ) {
    function htcf7ext_get_module_option( $section = '', $option_id = '', $option_key = '', $default = null ){

        $module_settings = get_option( $section );
        
        if( $option_id && is_array( $module_settings ) && count( $module_settings ) > 0 ) {


            if( isset ( $module_settings[ $option_id ] ) && '' != $module_settings[ $option_id ] ) {

                $option_value = json_decode( $module_settings[ $option_id ], true );

                if( $option_key && is_array( $option_value  ) && count( $option_value  ) > 0 ) {

                    if ( isset($option_value[$option_key] ) && '' != $option_value[$option_key] ) {
                        return $option_value[$option_key];
                    } else {
                        return $default;
                    }
                } else {
                    return $module_settings[ $option_id ];
                }
                
            } else {
                return $default;;
            }

        } else {
            return $module_settings;
        }

    }
}

function htcf7ext_update_menu_badge() {

    global $menu, $submenu;
    // Note: Keep 'contat-form-list' for backward compatibility with existing bookmarks/links
    $slug        = 'contat-form-list';
    $capability  = 'manage_options';

    // Use cached count instead of direct DB query
    $total = extcf7_get_unread_count();

    // Update Menu badge
    foreach ( $menu as $key => $menu_item ) {
        if ( $menu_item[2] === $slug && current_user_can( $capability ) ) {
            $menu[$key][0] = sprintf( '%1$s <span class="awaiting-mod count-%2$d"><span class="pending-count" aria-hidden="true">%2$d</span><span class="comments-in-moderation-text screen-reader-text">%2$d %3$s</span></span>', $menu_item[3], $total, __('Unread Message', 'cf7-extensions') );
            break;
        }
    }

    // Update Submenu badge
    foreach ( $submenu as $key => $items ) {
        if ( $key === $slug && current_user_can( $capability ) ) {
            foreach ($items as $index => $value) {
                if ( $value[2] === 'admin.php?page=contat-form-list#/entries' ) {
                    $submenu[$key][$index][0] = sprintf( '%1$s <span class="awaiting-mod count-%2$d"><span class="pending-count" aria-hidden="true">%2$d</span><span class="comments-in-moderation-text screen-reader-text">%2$d %3$s</span></span>', __('Submissions', 'cf7-extensions'), $total, __('Unread Message', 'cf7-extensions') );
                    break;
                }
            }
            break;
        }
    }

}

if( !function_exists('htcf7ext_is_tg_v2') ) {
    function htcf7ext_is_tg_v2() {
        return version_compare( WPCF7_VERSION, '6.0' ) >= 0 ;
    }
}

/**
 * Encode form data as JSON for storage
 * Replaces serialize() for security
 *
 * @param array $data Data to encode
 * @return string JSON encoded string
 * @since 3.4.0
 */
if( !function_exists('extcf7_encode_form_data') ) {
    function extcf7_encode_form_data( $data ) {
        return wp_json_encode( $data, JSON_UNESCAPED_UNICODE );
    }
}

/**
 * Decode form data - handles both JSON and legacy serialized data
 * Provides backward compatibility for existing sites
 *
 * @param string $data Encoded data (JSON or serialized)
 * @return array|false Decoded array or false on failure
 * @since 3.4.0
 */
if( !function_exists('extcf7_decode_form_data') ) {
    function extcf7_decode_form_data( $data ) {
        if ( empty( $data ) ) {
            return array();
        }

        // First, try to decode as JSON (new format)
        $decoded = json_decode( $data, true );

        if ( json_last_error() === JSON_ERROR_NONE && is_array( $decoded ) ) {
            return $decoded;
        }

        // Fallback: try to unserialize (legacy format)
        // Only unserialize if it looks like serialized data
        if ( is_serialized( $data ) ) {
            $unserialized = @unserialize( $data, array( 'allowed_classes' => false ) );
            if ( is_array( $unserialized ) ) {
                return $unserialized;
            }
        }

        return array();
    }
}

/**
 * Check if data is in legacy serialized format
 *
 * @param string $data Data to check
 * @return bool True if serialized format
 * @since 3.4.0
 */
if( !function_exists('extcf7_is_legacy_format') ) {
    function extcf7_is_legacy_format( $data ) {
        if ( empty( $data ) ) {
            return false;
        }
        return is_serialized( $data );
    }
}

/**
 * Get unread submissions count with caching
 *
 * @return int Unread count
 * @since 3.4.0
 */
if( !function_exists('extcf7_get_unread_count') ) {
    function extcf7_get_unread_count() {
        $count = get_transient( 'extcf7_unread_count' );

        if ( false === $count ) {
            global $wpdb;
            $table_name = $wpdb->prefix . 'extcf7_db';

            // Check if table exists
            $table_exists = $wpdb->get_var( $wpdb->prepare(
                "SHOW TABLES LIKE %s",
                $table_name
            ) );

            if ( $table_exists ) {
                $count = (int) $wpdb->get_var( $wpdb->prepare(
                    "SELECT COUNT(*) FROM $table_name WHERE status = %s",
                    'unread'
                ) );
            } else {
                $count = 0;
            }

            set_transient( 'extcf7_unread_count', $count, 5 * MINUTE_IN_SECONDS );
        }

        return (int) $count;
    }
}

/**
 * Invalidate unread count cache
 * Call this when submissions are added or status changes
 *
 * @since 3.4.0
 */
if( !function_exists('extcf7_invalidate_unread_cache') ) {
    function extcf7_invalidate_unread_cache() {
        delete_transient( 'extcf7_unread_count' );
    }
}