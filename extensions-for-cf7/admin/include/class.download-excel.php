<?php
/**
 * @phpcs:disable WordPress.Security.NonceVerification.Recommended, WordPress.Security.NonceVerification.Missing, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
 */
if( ! defined( 'ABSPATH' ) ) exit(); // Exit if accessed directly

use Box\Spout\Writer\Common\Creator\WriterEntityFactory;
use Box\Spout\Common\Entity\Row;

/**
 * HT CF7 Email Excel Export
*/
class Extensions_Cf7_Excel
{
    function __construct()
    {
        require_once CF7_EXTENTIONS_PL_PATH . 'vendor/autoload.php';
        $download_excel_status = isset($_REQUEST['download_excel']) && $_REQUEST['download_excel'] == true ? true : false;

        if( $download_excel_status && isset( $_REQUEST['nonce'] ) ){
            $nonce = sanitize_text_field($_REQUEST['nonce']);

            if ( ! wp_verify_nonce( $nonce, 'excel_download_nonce' ) ) {
                wp_die(esc_html__('Not Valid.. Download Request..!!', 'cf7-extensions'));
            }

            $this->Extensions_Cf7_Download_Excel();
        }
    }

    /**
     * Download Excel file
     * @return void
    */
    public function Extensions_Cf7_Download_Excel()
    {
        global $wpdb;
        $table_name = $wpdb->prefix.'extcf7_db';

        $cf7_id = !empty($_REQUEST['cf7_id']) ? absint($_REQUEST['cf7_id']) : 0;
        $heading_row = $wpdb->get_results( 
            $wpdb->prepare( "SELECT * FROM $table_name WHERE form_id = %d ORDER BY id DESC LIMIT 1", $cf7_id ),
            OBJECT 
        );

        // There is no record in the $cf7_id
        if( empty($heading_row) ){
            return;
        }

        $heading_row = reset($heading_row);
        // Use safe decoder that handles both JSON and legacy serialized data
        $form_values = extcf7_decode_form_data($heading_row->form_value);
        $heading_keys = array_keys($form_values);

        // Setup Excel Writer
        $writer = WriterEntityFactory::createXLSXWriter();
        $fileName = "extcf7-" . date("Y-m-d") . ".xlsx";

        try {
            $writer->openToFile($fileName);
        } catch ( \Exception $e ) {
            error_log( 'Extensions for CF7: Failed to create Excel file for export - ' . $e->getMessage() );
            wp_die( esc_html__( 'Failed to create export file. Please check server permissions.', 'cf7-extensions' ) );
        }

        // Prepare Headers
        $headers = [esc_html__('Date', 'cf7-extensions'), esc_html__('Form Id', 'cf7-extensions')];
        foreach ($heading_keys as $key) {
            $tmp_key = str_replace('your-', '', $key);
            $tmp_key = str_replace(array('-','_'), ' ', $tmp_key);
            $headers[] = ucwords($tmp_key);
        }

        // Add Headers to Excel
        $headerRow = WriterEntityFactory::createRowFromArray($headers);
        $writer->addRow($headerRow);

        // Process Data in Chunks
        $total_rows = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE form_id = %d", $cf7_id));
        $per_query = 1000;
        $total_queries = ceil($total_rows / $per_query);

        $upload_dir = wp_upload_dir();
        $extcf7_dir_url = $upload_dir['baseurl'].'/extcf7_uploads';

        for ($k = 0; $k < $total_queries; $k++) {
            $offset = $k * $per_query;
            $results = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT * FROM $table_name WHERE form_id = %d ORDER BY id DESC LIMIT %d, %d",
                    $cf7_id,
                    $offset,
                    $per_query
                ),
                OBJECT
            );

            foreach ($results as $result) {
                $row_data = [
                    $result->form_date,
                    $result->form_id
                ];

                // Use safe decoder that handles both JSON and legacy serialized data
                $form_values = extcf7_decode_form_data($result->form_value);

                foreach ($heading_keys as $key) {
                    $value = isset($form_values[$key]) ? $form_values[$key] : '';

                    if (strpos($key, 'file') !== false) {
                        $value = empty($value) ? '' : $extcf7_dir_url.'/'.$value;
                    }

                    if (is_array($value)) {
                        $value = implode(', ', $value);
                    }

                    $row_data[] = $value;
                }

                $row = WriterEntityFactory::createRowFromArray($row_data);
                $writer->addRow($row);
            }
        }

        $writer->close();

        // Verify file was created before sending
        if ( ! file_exists( $fileName ) ) {
            error_log( 'Extensions for CF7: Excel file was not created successfully' );
            wp_die( esc_html__( 'Failed to create export file.', 'cf7-extensions' ) );
        }

        header('Content-Description: File Transfer');
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="'.basename($fileName).'"');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($fileName));

        readfile($fileName);
        wp_delete_file($fileName);
        die();
    }
}

new Extensions_Cf7_Excel();