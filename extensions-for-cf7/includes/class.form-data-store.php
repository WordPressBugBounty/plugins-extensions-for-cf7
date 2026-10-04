<?php

if( ! defined( 'ABSPATH' ) ) exit(); // Exit if accessed directly

/**
 * HT CF7 Store Form Data
 */
class Extensions_Cf7_Store_Data
{
	
	function __construct(){
		add_action( 'wpcf7_submit', array($this,'extcf7_submit'), 10, 2 );
	}

    /**
     * Save the email data on email submit
     * @return void
    */
	function extcf7_submit($extcf7_info, $result){
        global $wpdb;
        if ( empty($result['status']) || 'mail_sent' !== $result['status'] ) {
            return;
        }
        $submission = WPCF7_Submission::get_instance();
        if($submission){
            $cf7_data               = $submission->get_posted_data(); 
            $cf7_file_upload_dir    = wp_upload_dir();
            $cf7_file_dirname       = $cf7_file_upload_dir['basedir'].'/extcf7_uploads';
            $current_time           = time();
            $cf7_files              = $submission->uploaded_files();
            $cf7_uploaded_files     = array();
            $posted_fields_value    = array();
            $stored_file_names      = array();

            extcf7_secure_uploads_dir( $cf7_file_dirname );

            foreach ($_FILES as $file_key => $file) { //phpcs:ignore WordPress.Security.NonceVerification.Missing
                array_push($cf7_uploaded_files, $file_key);
            }

            foreach ($cf7_files as $file_key => $file) {
                $file = is_array( $file ) ? reset( $file ) : $file;
                if( empty($file) ) continue;

                // Never trust the client-supplied filename/extension: sniff the real
                // type and always write out under our own field-name-based filename.
                $safe_ext = extcf7_get_validated_upload_ext( $file );
                if ( false === $safe_ext ) {
                    error_log( 'Extensions for CF7: Rejected uploaded file with disallowed type for field ' . $file_key );
                    continue;
                }

                $safe_name   = $current_time . '-' . sanitize_file_name( $file_key ) . '.' . $safe_ext;
                $destination = $cf7_file_dirname . '/' . $safe_name;
                if ( ! @copy($file, $destination) ) {
                    error_log( 'Extensions for CF7: Failed to copy uploaded file to ' . $destination );
                    continue;
                }
                $stored_file_names[ $file_key ] = $safe_name;
            }

            foreach ($cf7_data  as $key => $value){
                if(!in_array($key, $cf7_uploaded_files )){
                    $dataKey = esc_html($key);
                    $posted_fields_value[$dataKey] = $value;
                }
                if ( in_array($key, $cf7_uploaded_files ) ){
                    $dataKey = esc_html($key);
                    $posted_fields_value[$dataKey] = isset( $stored_file_names[ $key ] ) ? $stored_file_names[ $key ] : '';
                }
            }

            $mail_template = $extcf7_info->prop( 'mail' );
            $mail = wpcf7_mail_replace_tags(
                $mail_template,
                array(
                    'html' => $mail_template['use_html'],
                    'exclude_blank' => $mail_template['exclude_blank'],
                )
            );

            $posted_fields_value['mail_recipient'] = $mail['recipient'];

            $posted_fields_value['server_http_referer'] = sanitize_text_field( $_SERVER['HTTP_REFERER'] );
            $posted_fields_value['server_remote_addr']  = sanitize_text_field( $_SERVER['REMOTE_ADDR'] );

            $cf7_post_id = $extcf7_info->id();
            // Use JSON encoding instead of serialize for security (since v3.4.0)
            $cf7_value   = extcf7_encode_form_data( $posted_fields_value );
            $cf7_date    = current_time('Y-m-d H:i:s');

            $data  = [
                'form_id'      => $cf7_post_id,
                'form_value'   => $cf7_value,
                'form_date'    => $cf7_date,
            ];

            $table_name = $wpdb->prefix . 'extcf7_db';

            $wpdb->insert( $table_name, $data ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery

            // Invalidate unread count cache
            extcf7_invalidate_unread_cache();
        }
    }
}

new Extensions_Cf7_Store_Data();