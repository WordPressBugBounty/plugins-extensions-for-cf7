<?php
/**
 * @phpcs:disable WordPress.Security.NonceVerification.Recommended, WordPress.Security.NonceVerification.Missing, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
 */

if( ! defined( 'ABSPATH' ) ) exit(); // Exit if accessed directly

/**
 * HT CF7 Email List
*/
class Extensions_Cf7_Page implements Extensions_Cf7_Form_Datalist_Render {
    function cf7_layout_render(){
        $eamil_list_table = new Extensions_Cf7_list();
        $eamil_list_table->set_data();  
        $eamil_list_table->prepare_items();
        ob_start();
        ?>
        <div class="wrap">
            <h3><?php esc_html_e( "Email List","cf7-extensions"); ?></h3>
            <form method="post" action="" enctype="multipart/form-data">
                <?php
                    $eamil_list_table->search_box('search','search_id');  
                    $eamil_list_table->display();
                ?>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }
}


if( !class_exists('WP_List_Table') ){
	require_once ABSPATH."wp-admin/includes/class-wp-list-table.php";
}

/**
 * HT CF7 Email List Manage
*/
class Extensions_Cf7_list extends WP_List_Table
{
	private $cf7_post_id;

	public function __construct() {

        parent::__construct(
            array(
                'singular' => 'contact_form',
                'plural'   => 'contact_forms',
                'ajax'     => false
            )
        );

    }
	
	function set_data(){
		$this->cf7_post_id = !empty($_GET['cf7_id']) ? absint($_GET['cf7_id']) : 0;
		$data = array();
        global $wpdb;
        $search          = isset($_REQUEST['s']) && !empty( $_REQUEST['s'] ) ?  esc_sql( $_REQUEST['s'] ) : false;
        $form_date       = isset($_REQUEST['from_data']) && !empty( $_REQUEST['from_data'] )?esc_sql( $_REQUEST['from_data'] ).' 00:00:00' : false;
        $to_date         = isset($_REQUEST['to_data']) && !empty( $_REQUEST['to_data'] )?esc_sql( $_REQUEST['to_data'] ).' 23:59:00' : false;
        $table_name      = $wpdb->prefix.'extcf7_db';
        $page            = $this->get_pagenum();
        $page            = $page - 1;
        $start           = $page * 100;
        $cf7_post_id     = $this->cf7_post_id;
        $get_cf7_orderby = isset($_GET['orderby']) ? sanitize_text_field($_GET['orderby']) : '';
        $cf7_orderby     = 'date' == $get_cf7_orderby ? 'form_date' : 'id';
        $cf7_order       = isset($_GET['order']) && sanitize_text_field($_GET['order']) == 'asc' ? 'ASC' : 'DESC';

        $this->process_bulk_action();
        if(current_user_can('upload_files')){
            if(isset($_FILES["file"]["name"]) && !empty($_FILES["file"]["name"])){
                
                $sanitiaze_file_name = sanitize_file_name($_FILES["file"]["name"]);
                $fileInfo = wp_check_filetype(basename($sanitiaze_file_name));
                
                if (!empty($fileInfo['ext']) && in_array($fileInfo['ext'], ['csv', 'xlsx', 'xls'])) {
                    $import_file["name"] = $sanitiaze_file_name;  
                    $import_file["tmp_name"] = realpath($_FILES["file"]["tmp_name"]);
                    $import_file["ext"] = $fileInfo['ext'];
                } else {
                    $import_file = false; 
                    ?>
                    <div class="notice notice-error is-dismissible">
                        <p><?php echo esc_html__('Invalid file. Please import a CSV or Excel file (.csv, .xlsx, .xls)','cf7-extensions'); ?></p>
                    </div>
                    <?php
                }

                if($import_file) $this->import_file($import_file);
            }
        }
        
        if( $search && $form_date && $to_date ){
            $results = $wpdb->get_results( 
                $wpdb->prepare("SELECT * FROM $table_name 
                    WHERE form_date BETWEEN %s
                    AND %s 
                    AND form_value LIKE %s
                    AND form_id = %d
                    ORDER BY $cf7_orderby $cf7_order
                    LIMIT $start,100", 
                    $form_date, 
                    $to_date, 
                    '%'.$wpdb->esc_like($search).'%', 
                    $cf7_post_id
                ),
                OBJECT 
            );

        }else if( $search ) {
            $results = $wpdb->get_results( 
                $wpdb->prepare("SELECT * FROM $table_name 
                    WHERE  form_value LIKE %s
                    AND form_id = %d
                    ORDER BY $cf7_orderby $cf7_order
                    LIMIT $start,100",
                    '%'.$wpdb->esc_like($search).'%',
                    $cf7_post_id,
                ), 
                OBJECT 
            );
        }else if( $form_date && $to_date ){
            $results = $wpdb->get_results( 
                $wpdb->prepare( "SELECT * FROM $table_name 
                    WHERE  form_date BETWEEN %s
                    AND %s
                    AND form_id = %d
                    ORDER BY $cf7_orderby $cf7_order
                    LIMIT $start,100",
                    $form_date,
                    $to_date,
                    $cf7_post_id
                ), 
                OBJECT 
            );
        }else if($cf7_post_id){
            $results = $wpdb->get_results(
                $wpdb->prepare( "SELECT * FROM $table_name 
                    WHERE form_id = %d
                    ORDER BY $cf7_orderby $cf7_order
                    LIMIT %d, 100",
                    $cf7_post_id,
                    $start
                ),
                OBJECT
            );
        }else{
            $results = $wpdb->get_results( 
                $wpdb->prepare(
                    "SELECT * FROM $table_name
                    ORDER BY $cf7_orderby $cf7_order
                    LIMIT %d,100",
                    $start
                ), 
                OBJECT 
            );
        }

        foreach ( $results as $result ) {

            $cf7_form_value        = unserialize( $result->form_value );
            $cf7_form_values['id'] = $result->id;
            $cf7_form_data = [];

            foreach ($cf7_form_value as $k => $value){
                $key_value       = str_replace( 'your-', '', $k);
                $key_value       = str_replace( array('_', '-'), ' ', $key_value);
                if(is_array($value)){
                    $array_data = implode(', ',$value);
                    $cf7_form_data[]     = ucwords( esc_html($key_value) ).': '.esc_html($array_data);
                }else{
                    $cf7_form_data[]     = ucwords( esc_html($key_value) ).': '.esc_html($value);
                }
                if ( sizeof($cf7_form_data) > 2) break;
            }
            $cf7_form_values['form_data'] = "<span class='status ". esc_attr($result->status) ."'>" . esc_html($result->status). "</span>" . implode(".<br>",$cf7_form_data) ;
            $cf7_form_values['form_title'] = esc_html(get_the_title($result->form_id));
            $cf7_form_values['date'] = date_format(date_create($result->form_date),"F j, Y");
            $data[] = $cf7_form_values;

        }

        $this->items= $data;
	}

/**
	 * Gets the list of views available on this table.
	 *
	 * The format is an associative array:
	 * - `'id' => 'link'`
	 *
	 * @since 3.1.0
	 *
	 * @return array
	 */
	protected function get_views() {
		return array();
	}

	/**
	 * Displays the list of views available on this table.
	 *
	 * @since 3.1.0
	 */
	public function views() {
		$views = $this->get_views();
		/**
		 * Filters the list of available list table views.
		 *
		 * The dynamic portion of the hook name, `$this->screen->id`, refers
		 * to the ID of the current screen.
		 *
		 * @since 3.1.0
		 *
		 * @param string[] $views An array of available list table views.
		 */
		$views = apply_filters( "views_{$this->screen->id}", $views );

		if ( empty( $views ) ) {
			return;
		}

		$this->screen->render_screen_reader_content( 'heading_views' );

		echo "<ul class='subsubsub'>\n";
		foreach ( $views as $class => $view ) {
			$views[ $class ] = "\t<li class='".esc_attr($class)."'>$view";
		}
		echo implode( " |</li>\n", $views ) . "</li>\n"; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '</ul>';
	}

    /**
     * Import CSV or Excel file into database using Box/Spout
     * @param array $file File information including name, tmp_name and extension
     * @return void
     */
    public function import_file($file) {
        if (!class_exists('Box\Spout\Reader\Common\Creator\ReaderEntityFactory')) {
            require_once CF7_EXTENTIONS_PL_PATH . 'vendor/autoload.php';
        }

        global $wpdb;
        $db_format = array();
        $current_form_id = absint($_REQUEST['cf7_id']);

        try {
            $readerFactory = \Box\Spout\Reader\Common\Creator\ReaderEntityFactory::class;

            // Create the appropriate reader based on file extension
            if ($file['ext'] === 'csv') {
                $reader = $readerFactory::createCSVReader();
            } else {
                $reader = $readerFactory::createXLSXReader();
            }

            // Open the file
            $reader->open($file['tmp_name']);

            $isFirstRow = true;
            $header_row = [];

            // Iterate through all sheets
            foreach ($reader->getSheetIterator() as $sheet) {
                // Iterate through all rows of the current sheet
                foreach ($sheet->getRowIterator() as $row) {
                    $values = $row->toArray();

                    if ($isFirstRow) {
                        // Store header row (convert to lowercase)
                        $header_row = array_map('strtolower', $values);
                        $isFirstRow = false;
                        continue;
                    }

                    // Process data row
                    $csv_import_data = array();
                    $csv_import_data['form_date'] = $values[0];
                    $csv_import_data['form_id'] = $values[1];
                    $csv_import_data['form_value'] = array();

                    // Map remaining columns using header row
                    for ($k = 2; $k < count($values); $k++) {
                        if (isset($header_row[$k])) {
                            $mkey = str_replace(' ', '_', $header_row[$k]);
                            $csv_import_data['form_value'][$mkey] = $values[$k];
                        }
                    }

                    $db_format[] = $csv_import_data;
                }
                // We only need the first sheet
                break;
            }

            // Close the reader
            $reader->close();

            // Insert into database
            $table_name = $wpdb->prefix . 'extcf7_db';
            foreach ($db_format as $value) {
                $data = [
                    'form_id' => $current_form_id,
                    'form_value' => serialize($value['form_value']),
                    'form_date' => $value['form_date'],
                ];
                $wpdb->insert($table_name, $data);
            }

            ?>
            <div class="notice notice-success is-dismissible">
                <p><?php echo esc_html__('Successfully Imported File', 'cf7-extensions'); ?></p>
            </div>
            <?php

        } catch (\Exception $e) {
            ?>
            <div class="notice notice-error is-dismissible">
                <p><?php echo esc_html__('Error importing file: ', 'cf7-extensions') . esc_html($e->getMessage()); ?></p>
            </div>
            <?php
        }
    }

    /**
     * Define check box for bulk action (each row)
     * @param  $item
     * @return checkbox
    */
    public function column_cb($item){
        return "<input type='checkbox' name='cf7_emails_id[]' value='".esc_attr($item['id'])."' />";
    }

    public function column_form_data($item){
        $nonce = wp_create_nonce("cf7_email_delete");
        $actions = [
            'view' => sprintf("<b><a href=admin.php?page=contat-form-list&cf7_id=%s&cf7em_id=%s>%s</a></b>",esc_attr( $this->cf7_post_id ), esc_attr($item['id']), esc_html__('View','cf7-extensions')),
            'delete' => sprintf('<b><a href=admin.php?page=contat-form-list&cf7_id=%s&n=%s&cf7em_id=%s&action=%s onclick=\'return confirm("%s");\'>%s</a></b>',esc_attr($this->cf7_post_id),esc_attr($nonce),esc_attr( $item['id'] ),'delete#/entries',esc_html__('Are you sure to delete this file','cf7-extensions'), esc_html__('Delete','cf7-extensions' )),
        ];
        return sprintf("%s %s",$item['form_data'], $this->row_actions($actions) );
    }

    public function column_date($item){
        $nonce = wp_create_nonce("cf7_email_delete");
        $actions = [
            'view' => sprintf('<b><a href=admin.php?page=contat-form-list&cf7_id=%s&cf7em_id=%s>%s</a></b>',esc_attr( $this->cf7_post_id ), esc_attr($item['id']), esc_html__('View','cf7-extensions')),
            'delete' => sprintf('<b><a href=admin.php?page=contat-form-list&cf7_id=%s&n=%s&cf7em_id=%s&action=%s onclick=\'return confirm("%s");\'>%s</a></b>',esc_attr($this->cf7_post_id),esc_attr($nonce),esc_attr( $item['id'] ),'delete#/entries',esc_html__('Are you sure to delete this file','cf7-extensions'),esc_html__('Delete','cf7-extensions' )),
        ];
        return sprintf("%s %s",$item['date'],$this->row_actions($actions));
    }


    function get_sortable_columns(){
        return [
            'date'=>['date',true],
        ];
    }

	function get_columns(){

		return array(
            'cb' => '<input type="checkbox" />',
            'form_data' => esc_html__( 'Form Data', 'cf7-extensions' ),
            'form_title' => esc_html__( 'Form Title', 'cf7-extensions' ),
            'date' => esc_html__( 'Date', 'cf7-extensions' ),
        );

	}

    function extra_tablenav( $which ){
        if('top' == $which):
        $form_date = isset($_REQUEST['from_data']) && !empty($_REQUEST['from_data']) ? sanitize_text_field($_REQUEST['from_data']) : '';
        $to_data = isset($_REQUEST['to_data']) && !empty($_REQUEST['to_data']) ? sanitize_text_field($_REQUEST['to_data']) : '';
        ?>
        <div class="actions alignleft">
            <?php esc_html_e('From','cf7-extensions'); ?>
            <input type="text" id="form-data" name="from_data" style="width: 130px;" placeholder="YYYY-MM-DD" value="<?php echo esc_attr($form_date); ?>" autocomplete="off">
            <?php esc_html_e('To','cf7-extensions'); ?>
            <input type="text" id="to-date" name="to_data" style="width: 130px;" placeholder="YYYY-MM-DD" value="<?php echo esc_attr($to_data); ?>" autocomplete="off">
            <script type="text/javascript">
            (function($){
                $(document).ready(function(){
                    $("#form-data").datepicker({
                        dateFormat : 'yy-mm-dd'
                    });
                    $("#to-date").datepicker({
                        dateFormat : 'yy-mm-dd'
                    });
                });
            })(jQuery);
            </script>
            <?php 
                submit_button( esc_html__('Filter','cf7-extensions'),'button','sumbit',false );
            ?>
        </div>

        <?php  
            if( empty($_GET['cf7_id']) ){
                return;
            }
        ?>
        <div class="actions alignleft">
            File
           <input type="file" name="file" id="file" style="width: 150px;">
           <?php 
                submit_button(esc_html__('Import','cf7-extensions'),'button','csv-import',false);
            ?>
        </div>
        <?php
        endif;

        echo '<div class="actions extcf7-export-actions alignleft">';

        $csv_nonce = wp_create_nonce( 'csv_download_nonce' );
        echo "<a href='".esc_attr($_SERVER['REQUEST_URI'])."&download_csv=true&nonce=".esc_attr($csv_nonce)."'class='button'>";
        echo esc_html__( 'Export CSV', 'cf7-extensions' );
        echo '</a>';

        $excel_nonce = wp_create_nonce( 'excel_download_nonce' );
        echo "<a href='".esc_attr($_SERVER['REQUEST_URI'])."&download_excel=true&nonce=".esc_attr($excel_nonce)."'class='button'>";
        echo esc_html__( 'Export Excel', 'cf7-extensions' );
        echo '</a>';
        
        echo '</div>';
    }

    /**
     * Define bulk action
     * @return array
    */
    public function get_bulk_actions() {

        return array(
            'delete' => esc_html__( 'Delete', 'cf7-extensions' ),
            'mark_as_read' => esc_html__( 'Mark as Read', 'cf7-extensions' ),
            'mark_as_unread' => esc_html__( 'Mark as Unread', 'cf7-extensions' ),
        );

    }

	function prepare_items(){
		$this->_column_headers = array($this->get_columns(),array('id'),$this->get_sortable_columns());

        $search = empty( $_REQUEST['s'] ) ? false :  esc_sql( $_REQUEST['s'] );
        $from_date    = isset($_REQUEST['from_data']) && !empty( $_REQUEST['from_data'] )?esc_sql( $_REQUEST['from_data'] ).' 00:00:00' : false;
        $to_date      = isset($_REQUEST['to_data']) && !empty( $_REQUEST['to_data'] )?esc_sql( $_REQUEST['to_data'] ).' 23:59:00' : false;
        $cf7_post_id  = $this->cf7_post_id;

        global $wpdb;

        $table_name  = $wpdb->prefix.'extcf7_db';
   
        $perPage     = 100;
        $currentPage = $this->get_pagenum();
        if ( ! empty($search) && !empty($from_date) && !empty($to_date)) {

            $totalIemails = $wpdb->get_var( 
                $wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE form_date BETWEEN %s AND %s AND form_value LIKE %s AND form_id = %d ", $from_date, $to_date, '%' . $wpdb->esc_like($search) . '%', $cf7_post_id, )
            );

        }else if(! empty($search)){
            $totalIemails  = $wpdb->get_var(
                $wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE form_value LIKE %s AND form_id = %d ",'%' . $wpdb->esc_like($search) . '%', $cf7_post_id )
            );
        }else if(! empty($from_date) && ! empty($to_date)){
            $totalIemails  = $wpdb->get_var(
                $wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE form_date BETWEEN %s AND %s AND form_id = %d ", $from_date,$to_date, $cf7_post_id )
            );
        }else{

            if( $cf7_post_id !== null ){
                $totalIemails  = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE form_id = %d", $cf7_post_id));
            } else {
                $totalIemails  = $wpdb->get_var( $wpdb->prepare("SELECT COUNT(*) FROM %s", $table_name) );
            }
            
        }

        $this->set_pagination_args( array(
            'total_items' => $totalIemails,
            'per_page'    => $perPage
        ) );
	}

    /**
     * Define bulk action
     *
     */
    public function process_bulk_action(){

        global $wpdb;
        $table_name  = $wpdb->prefix.'extcf7_db';
        $action      = $this->current_action();

        if ( isset( $_POST['_wpnonce'] ) && ! empty( $_POST['_wpnonce'] ) ) {

            $nonce        = sanitize_text_field($_POST['_wpnonce']);
            $nonce_action = 'bulk-' . $this->_args['plural'];

            if ( !wp_verify_nonce( $nonce, $nonce_action ) ){
                wp_die( esc_html_e( 'Nope! Security check failed!', 'cf7-extensions' ) );
            }
        }

        if(isset( $_POST['cf7_emails_id'] ) && is_array($_POST['cf7_emails_id'])){
            $form_ids = array_map('absint', extcf7_clean($_POST['cf7_emails_id']));
        }else{
            $form_ids = array();
        }

        $cf7_em_id = !empty($_GET['cf7em_id']) ? sanitize_text_field($_GET['cf7em_id']) : '';

        if( 'delete' === $action ) {
            foreach ($form_ids as $form_id):
                $form_id         = $form_id;
                $delete_row      = $wpdb->get_results( $wpdb->prepare("SELECT * FROM $table_name WHERE id = %d LIMIT 1", $form_id ), OBJECT );
                $del_row_value   = $delete_row[0]->form_value;
                $del_row_values  = unserialize($del_row_value);
                $cf7_upload_dir  = wp_upload_dir();
                $cfdb7_dirname   = $cf7_upload_dir['basedir'].'/extcf7_uploads';

                foreach ($del_row_values as $key => $result) {

                    if ( ( strpos($key, 'file') !== false ) &&
                        file_exists($cfdb7_dirname.'/'.$result) ) {
                        wp_delete_file($cfdb7_dirname.'/'.$result);
                    }

                }

                $wpdb->delete(
                    $table_name ,
                    array( 'id' => $form_id ),
                    array( '%d' )
                );
            endforeach;

            if( $cf7_em_id && !$form_ids ){
                $where = array('ID' => $cf7_em_id);

                $wpdb->delete($table_name, $where);
            }
        }

        if( 'mark_as_read' === $action ) {
            foreach ($form_ids as $form_id) {
                $result = $wpdb->query( $wpdb->prepare(
                    "UPDATE $table_name SET status = %s WHERE id = %d",
                    'read',
                    $form_id
                ));
            }
            htcf7ext_update_menu_badge();
        }

        if( 'mark_as_unread' === $action ) {
            foreach ($form_ids as $form_id) {
                $result = $wpdb->query( $wpdb->prepare(
                    "UPDATE $table_name SET status = %s WHERE id = %d",
                    'unread',
                    $form_id
                ));
            }
            htcf7ext_update_menu_badge();
        }
    }

	function column_default($items,$column_name){
		return $items[$column_name];
	}

    protected function bulk_actions( $which = '' ) {
        if ( is_null( $this->_actions ) ) {
            $this->_actions = $this->get_bulk_actions();
            $bulk_action_position = '';
        }else {
            $bulk_action_position = '2';
        }

        if ( empty( $this->_actions ) )
            return;

        echo '<select name="action' . esc_attr($bulk_action_position) . '">';
        echo '<option value="-1">' . esc_html__( 'Bulk Actions', 'cf7-extensions' ) . "</option>";
        foreach ( $this->_actions as $name => $title ) {
            echo '<option value="' . esc_attr( $name ) . '">' . esc_html( $title ) . "</option>";
        }
        echo "</select>";

        submit_button( esc_html__( "Apply", "cf7-extensions" ), 'action', '', false, array( 'id' => "doaction$bulk_action_position" ) );
    }

}