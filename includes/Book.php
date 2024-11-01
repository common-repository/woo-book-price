<?php
/**
 * Created by PhpStorm.
 * User: anhvnit
 * Date: 1/15/19
 * Time: 14:03
 */
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
class OP_Book{
    public $db;
    public function __construct()
    {
        global $_has_openpos;
        $this->db = new OP_Book_Price();
        add_action( 'wp_ajax_op_price_book_ajax_list', array($this,'ajax_book_list') );
        add_action( 'wp_ajax_op_price_book_product_ajax_list', array($this,'ajax_book_product_list') );


        add_action( 'wp_ajax_op_upload_book_csv', array($this,'upload_book_csv') );
        add_action( 'wp_ajax_op_save_book', array($this,'save_book') );
        add_action( 'wp_ajax_op_download_book_csv', array($this,'download_book_csv') );
        add_action( 'wp_ajax_op_force_download_book_csv', array($this,'force_download_book_csv') );

        add_action( 'wp_ajax_op_update_book_product', array($this,'update_book_product') );
        add_action( 'wp_ajax_op_delete_book_product', array($this,'delete_book_product') );
        add_action( 'wp_ajax_op_delete_book', array($this,'delete_book') );

        //start hook to product price
        //woocommerce_product_get_price
        //woocommerce_product_get_regular_price
        //woocommerce_product_get_sale_price
        //woocommerce_product_get_date_on_sale_from
        //woocommerce_product_get_date_on_sale_to

        add_filter('woocommerce_product_get_price',array($this,'product_price'),50,2);
        add_filter('woocommerce_product_variation_get_price',array($this,'product_price'),50,2);


        //end
    }
    public function init(){
        add_action( 'admin_menu', array($this,'pos_admin_menu'),10 );
    }

    public function pos_admin_menu(){
        global  $_has_openpos;

        if($_has_openpos)
        {
            $page = add_submenu_page( 'openpos-dasboard', __( 'Price Books', 'woo-book-price' ),  __( 'Price Books', 'woo-book-price' ) , 'manage_woocommerce', 'op-price-book', array( $this, 'book_page' ) );
        }else{
            $page = add_submenu_page( 'edit.php?post_type=product', __( 'Price Books', 'woo-book-price' ),  __( 'Price Books', 'woo-book-price' ) , 'manage_woocommerce', 'op-price-book', array( $this, 'book_page' ) );
        }

        add_action( 'admin_print_styles-'. $page, array( $this, 'admin_enqueue' ) );
    }

    public function book_page(){
        global $op_warehouse;
        global $_has_openpos;
        $warehouses = array();
        if($op_warehouse)
        {
            $warehouses = $op_warehouse->warehouses();

        }else{
            $warehouses[] = array(
                'id' => 0,
                'name' => __('Default Woocommerce Store','woo-book-price')
            );
        }
        $action = isset($_REQUEST['action']) ? esc_attr($_REQUEST['action']) : '';
        $current_book_id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
        $current_book = array(
            'title' => '',
            'date_from' => '',
            'date_to' => '',
            'status' => 2,
            'priority' => 0
        );
        if($current_book_id)
        {
            $tmp = $this->db->getBook($current_book_id);
            if(!empty($tmp))
            {
                $current_book = $tmp;
            }
        }
        switch ($action)
        {
            case 'new':
                require(OPENPOS_BOOK_DIR.'templates/new.php');
                break;
            case 'edit':
                require(OPENPOS_BOOK_DIR.'templates/edit.php');
                break;
            default:
                require(OPENPOS_BOOK_DIR.'templates/books.php');
        }

    }
    public function admin_enqueue(){

        wp_enqueue_style('op-book-bootstrap.jquery', OPENPOS_BOOK_URL.'/assets/css/jquery.dataTables.css');
        wp_enqueue_style('op-book-bootstrap', OPENPOS_BOOK_URL.'/assets/css/bootstrap.css');
        wp_enqueue_style('op-book-bootstrap.datetime', OPENPOS_BOOK_URL.'/assets/css/bootstrap-datetimepicker.min.css');

        wp_enqueue_style('openpos-book.admin.datatable.bootrap', OPENPOS_BOOK_URL.'/assets/css/dataTables.bootstrap.css',array('op-book-bootstrap','op-book-bootstrap.jquery'));
        wp_enqueue_style('openpos-book.admin', OPENPOS_BOOK_URL.'/assets/css/admin.css',array('op-book-bootstrap','op-book-bootstrap.datetime'));


        wp_enqueue_script('openpos-book.admin.moment', OPENPOS_BOOK_URL.'/assets/js/moment.min.js',array('jquery'));
        wp_enqueue_script('openpos-book.admin.bootstrap', OPENPOS_BOOK_URL.'/assets/js/bootstrap.js',array('jquery'));

        wp_enqueue_script('openpos-book.admin.datables', OPENPOS_BOOK_URL.'/assets/js/datatables.min.js',array('jquery', 'wp-mediaelement'));
        wp_enqueue_script('openpos-book.admin.datables.jquery', OPENPOS_BOOK_URL.'/assets/js/jquery.dataTables.js',array('openpos-book.admin.bootstrap'));
        wp_enqueue_script('openpos-book.admin.bootstrap.datepicker', OPENPOS_BOOK_URL.'/assets/js/bootstrap-datetimepicker.js',array('openpos-book.admin.datables.jquery','openpos-book.admin.moment'));
        wp_enqueue_script('openpos-book.admin', OPENPOS_BOOK_URL.'/assets/js/admin.js',array('openpos-book.admin.datables.jquery','openpos-book.admin.bootstrap.datepicker'));


    }

    public function ajax_book_product_list(){
        global $_has_openpos;
        $result = array(
            "draw" => 0,
            "recordsTotal" => 0,
            "recordsFiltered" => 0,
            'data' => array()
        );
        $book_id = isset($_REQUEST['book_id']) ? intval($_REQUEST['book_id']) : 0;
        if($book_id)
        {
            $per_page = intval($_REQUEST['length']);
            $start = intval($_REQUEST['start']);
            $term = isset($_REQUEST['search']['search']) ? sanitize_text_field($_REQUEST['search']['search']): '' ;
            $order = isset($_REQUEST['order'][0]['dir']) ? esc_attr($_REQUEST['order'][0]['dir']) : 'asc';
            $params = array(
                'book_id' => $book_id,
                'per_page' => $per_page,
                'start' => $start,
                'order' => $order
            );

            $rows = $this->db->getBookProducts($params);
            $result['draw'] = isset($_REQUEST['draw']) ? intval($_REQUEST['draw']) : 0;
            $result['recordsTotal'] = $rows['total'];
            $result['recordsFiltered'] = $rows['total'];

            foreach($rows['rows'] as $row)
            {
                $product = wc_get_product($row['product_id']);
                if($_has_openpos)
                {
                    $tmp = array(
                        (int)$row['product_id'],
                        $row['barcode'],
                        $product->get_sku(),
                        $product->get_name(),
                        '<input type="text" value="'.$row['price'].'" id="row-'.$row['book_product_id'].'" />',
                        '<p><button type="button" class="save-row" data-id="'.$row['book_product_id'].'"><span class="glyphicon glyphicon-floppy-disk"></span></button>&nbsp;<button class="del-row" type="button" data-id="'.$row['book_product_id'].'" ><span class="glyphicon glyphicon-trash"></span></button></p>',
                    );
                }else{
                    $tmp = array(
                        (int)$row['product_id'],
                        $product->get_sku(),
                        $product->get_name(),
                        '<input type="text" value="'.$row['price'].'" id="row-'.$row['book_product_id'].'" />',
                        '<p><button type="button" class="save-row" data-id="'.$row['book_product_id'].'"><span class="glyphicon glyphicon-floppy-disk"></span></button>&nbsp;<button class="del-row" type="button" data-id="'.$row['book_product_id'].'" ><span class="glyphicon glyphicon-trash"></span></button></p>',
                    );
                }

                $result['data'][] = $tmp;
            }
        }
        echo json_encode($result);
        exit;
    }
    public function ajax_book_list(){
        $result = array(
            "draw" => 0,
            "recordsTotal" => 0,
            "recordsFiltered" => 0,
            'data' => array()
        );

        $per_page = intval($_REQUEST['length']);
        $start = intval($_REQUEST['start']);
        $term = isset($_REQUEST['search']['value']) ? sanitize_text_field($_REQUEST['search']['value']): '' ;
        $order = isset($_REQUEST['order'][0]['dir']) ? esc_attr($_REQUEST['order'][0]['dir']) : 'asc';
        $params = array(
            'term' => $term,
            'per_page' => $per_page,
            'start' => $start,
            'order' => $order
        );
        $rows = $this->db->getBooks($params);
        $result['draw'] = isset($_REQUEST['draw']) ? intval($_REQUEST['draw']) : 0;
        $result['recordsTotal'] = $rows['total'];
        $result['recordsFiltered'] = $rows['total'];

        global $op_warehouse;
        $warehouses = array();
        if($op_warehouse)
        {
            $warehouses = $op_warehouse->warehouses();

        }else{
            $warehouses[] = array(
                'id' => 0,
                'name' => __('Default Woocommerce Store','woo-book-price')
            );
        }

        foreach($rows['rows'] as $row)
        {

            $created_by = $row['created_by'];
            $user = get_user_by('id',$created_by);

            $warehouse_name = __('Default Woocommerce Store','woo-book-price');
            foreach($warehouses as $warehouse)
            {
                if($warehouse['id'] == $row['store_id'])
                {
                    $warehouse_name = $warehouse['name'];
                }
            }

            $tmp = array(
                (int)$row['book_id'],
                $row['title'],
                $warehouse_name,
                $row['from_date'],
                $row['to_date'],
                $row['priority'],
                $user->display_name,
                $row['status'] == 1 ? '<span style="color: green;">Published</span>' :'<span style="color: red;">Pending</span>',
                '<button class="edit-row" type="button" data-id="'.$row['book_id'].'" ><span class="glyphicon glyphicon-pencil"></span></button>&nbsp;<button class="delete-row" type="button" data-id="'.$row['book_id'].'" ><span class="glyphicon glyphicon-trash"></span></button>',
            );
            $result['data'][] = $tmp;
        }

        echo json_encode($result);
        exit;
    }

    public function save_book(){
        $current_user_id = get_current_user_id();
        $book_id = isset($_REQUEST['book_id'])? intval($_REQUEST['book_id']) : 0;
        $title = isset($_REQUEST['title'])? sanitize_text_field($_REQUEST['title']) : '';
        $store_id = isset($_REQUEST['store_id'])? intval($_REQUEST['store_id']) : 0;
        $date_from = isset($_REQUEST['from_date'])? sanitize_text_field($_REQUEST['from_date']) : '';
        $date_to = isset($_REQUEST['to_date'])? sanitize_text_field($_REQUEST['to_date']) : '';
        $status = isset($_REQUEST['status'])? intval($_REQUEST['status']) : 0;
        $priority = isset($_REQUEST['priority'])? intval($_REQUEST['priority']) : 0;

        $result = array(
            'status' => 0,
            'data' => array(),
            'message' => 'Unknown message'
        );
        $check = true;
        if($date_to && $date_from)
        {
            $time_from = strtotime($date_from);
            $time_to = strtotime($date_to);
            if($time_from > $time_to)
            {
                $check = false;
                $result['message'] = __('Please choose from date and to date again','woo-book-price');
            }
        }
        if(strlen($title) == 0)
        {
            $check = false;
            $result['message'] = __('Title can not empty','woo-book-price');
        }
        if($check)
        {
            $request_data = array(
                'book_id' => $book_id,
                'title' => $title,
                'store_id' => $store_id,
                'from_date' => $date_from,
                'to_date' => $date_to,
                'status' => $status,
                'priority' => $priority,
                'created_by' => $current_user_id
            );
            $book_id = $this->db->save_book($request_data);
            if($book_id)
            {
                $result['status'] = 1;
                $request_data['book_id'] = $book_id;
                $result['data'] = $request_data;
            }
        }

        echo json_encode($result);
        exit;
    }
    public function upload_book_csv(){
        global $OPENPOS_CORE;
        global $_has_openpos;
        $book_id = isset($_REQUEST['book_id'])? intval($_REQUEST['book_id']) : 0;
        $result = array(
            'status' => 0,
            'data' => array(),
            'message' => ''
        );

        if(!$book_id)
        {
            $book_id = $this->db->save_draft_book();
        }
        if($book_id)
        {
            //
            //load();
            if(isset($_FILES['file']))
            {
                $file = $_FILES['file'];
                $csv = array();
                if($file['type'])
                {
                    $inputFileType = 'Csv';
                    try{
                        $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader($inputFileType);
                        $reader->setReadDataOnly(true);
                        $reader->load($file['tmp_name']);

                        $worksheetData = $reader->listWorksheetInfo($file['tmp_name']);
                        foreach ($worksheetData as $worksheet) {
                            $sheetName = $worksheet['worksheetName'];

                            $reader->setLoadSheetsOnly($sheetName);
                            $spreadsheet = $reader->load($file['tmp_name']);

                            $worksheet = $spreadsheet->getActiveSheet();
                            $csv = $worksheet->toArray();
                        }

                    }catch (Exception $e)
                    {
                        print_r($e->getMessage());
                    }

                   if(!empty($csv))
                   {
                       $labels = $csv[0];
                       array_shift($csv);
                       $id_index = 0;
                       $price_index = 0;
                       foreach($labels as $key => $label)
                       {
                           if(strtoupper($label) == strtoupper('id'))
                           {
                               $id_index = $key;
                           }
                           if(strtoupper($label) == strtoupper('price'))
                           {
                               $price_index = $key;
                           }
                       }

                       foreach($csv as $row)
                       {
                           $product_id = $row[$id_index];
                           if(!$product_id)
                           {
                               continue;
                           }
                           $price = $row[$price_index];

                           $product = wc_get_product($product_id);
                           if($product)
                           {
                               if(!$price)
                               {
                                   continue;
                               }
                               $id = $product->get_id();
                               if($_has_openpos)
                               {
                                   $row_data = array(
                                       'book_id' => $book_id,
                                       'product_id' => $id,
                                       'price' => $price,
                                       'barcode' => $OPENPOS_CORE->getBarcode($id)
                                   );
                               }else{
                                   $row_data = array(
                                       'book_id' => $book_id,
                                       'product_id' => $id,
                                       'price' => $price,
                                       'barcode' => ''
                                   );
                               }

                               $this->db->save_book_items($row_data);
                           }


                       }
                   }

                }
            }
        }



        $result['status'] = 1;
        $result['data']['book_id'] = $book_id;

        echo json_encode($result);
        exit;
    }
    public function force_download_book_csv(){
        $file_name = isset($_REQUEST['file']) ? sanitize_text_field($_REQUEST['file']) : '';
        if($file_name)
        {
            ob_start();
            $upload_dir = wp_upload_dir();
            $url = $upload_dir['basedir'];

            $url = rtrim($url,'/').'/'.$file_name;
            header("Content-Type: application/octet-stream");
            header("Content-Transfer-Encoding: Binary");
            header("Content-disposition: attachment; filename=\"".$file_name."\"");
            echo readfile($url);
        }else{
            echo 'Wrong file name';
        }

        exit;
    }
    public function download_book_csv(){
        global $_in_openpos_book;
        $_in_openpos_book = true;

        $outlet_id = isset($_REQUEST['store_id']) ? intval($_REQUEST['store_id']) : 0;

        global $_has_openpos;
        $params = array('numberposts' => -1);

        $products = $this->getProducts($params);

        $result = array(
            'status' => 0,
            'data' => array(),
            'message' => ''
        );

        $orders_export_data = array();
        $orders_export_data[] = array(
            "ID",
            "PRODUCT",
            "SKU",
            "PRICE",
        );

        foreach($products['posts'] as $post)
        {
            $_product = wc_get_product($post->ID);

            $tmp = array(
                $_product->get_id(),
                $_product->get_name(),
                $_product->get_sku(),
                $_product->get_price(),

            );
            $orders_export_data[] = $tmp;
        }




        $spreadsheet = new Spreadsheet();
        $spreadsheet->setActiveSheetIndex(0);
        $spreadsheet->getActiveSheet()->fromArray($orders_export_data, null, 'A1');

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Csv($spreadsheet);


        $file_name = 'openpos-book-products-'.time().'.csv';
        $writer->save(ABSPATH.'wp-content/uploads/'.$file_name);
        $url = admin_url('admin-ajax.php?action=op_force_download_book_csv&file='.$file_name);
        $result['data']['export_file'] = $url;
        $result['status'] = 1;
        echo json_encode($result);
        exit;
    }
    public function update_book_product(){
        $result = array(
            'status' => 0,
            'data' => array(),
            'message' => ''
        );
        $row_id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
        $price = isset($_REQUEST['price']) ? sanitize_text_field($_REQUEST['price']) : '';

        if($row_id && $price != '')
        {
            $this->db->update_book_item_price($row_id,$price);
            $result['status'] = 1;
        }
        echo json_encode($result);
        exit;
    }
    public function delete_book_product(){
        $result = array(
            'status' => 0,
            'data' => array(),
            'message' => ''
        );

        $row_id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
        if($row_id)
        {
            $this->db->delete_book_item($row_id);
            $result['status'] = 1;
        }

        echo json_encode($result);
        exit;
    }
    public function delete_book(){
        $result = array(
            'status' => 0,
            'data' => array(),
            'message' => ''
        );

        $row_id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
        if($row_id)
        {
            $this->db->delete_book($row_id);
            $result['status'] = 1;
        }

        echo json_encode($result);
        exit;
    }
    public function getProducts($args)
    {

        $ignores = $this->getAllVariableProducts();

        $args['post_type'] = array('product','product_variation');
        $args['exclude'] = $ignores;
        $args['post_status'] = 'publish';
        $args['suppress_filters'] = false;

        $defaults = array(
            'numberposts' => 5,
            'category' => 0, 'orderby' => 'date',
            'order' => 'DESC', 'include' => array(),
            'exclude' => array(), 'meta_key' => '',
            'meta_value' =>'', 'post_type' => 'product',
            'suppress_filters' => true
        );

        $r = wp_parse_args( $args, $defaults );
        if ( empty( $r['post_status'] ) )
            $r['post_status'] = ( 'attachment' == $r['post_type'] ) ? 'inherit' : 'publish';
        if ( ! empty($r['numberposts']) && empty($r['posts_per_page']) )
            $r['posts_per_page'] = $r['numberposts'];
        if ( ! empty($r['category']) )
            $r['cat'] = $r['category'];
        if ( ! empty($r['include']) ) {
            $incposts = wp_parse_id_list( $r['include'] );
            $r['posts_per_page'] = count($incposts);  // only the number of posts included
            $r['post__in'] = $incposts;
        } elseif ( ! empty($r['exclude']) )
            $r['post__not_in'] = wp_parse_id_list( $r['exclude'] );

        $r['ignore_sticky_posts'] = false;
        $get_posts = new WP_Query($r);
        return array('total'=>$get_posts->found_posts,'posts' => $get_posts->get_posts());
    }
    public function getAllVariableProducts()
    {
        $args = array(
            'posts_per_page'   => -1,
            'post_type'        => array('product_variation'),
            'post_status'      => 'publish'
        );
        $posts_array = get_posts($args);
        $result = array();
        foreach($posts_array as $post)
        {
            $parent_id =  $post->post_parent;
            if($parent_id)
            {
                $result[] = $parent_id;
            }
        }
        $arr = array_unique($result);
        $result = array_values($arr);
        return $result;
    }



    public function product_price($price,$product){
        global $_in_openpos_book;
        global $op_session_data;

        if($_in_openpos_book)
        {
            return $price;
        }
        $product_id = $product->get_id();

        if($op_session_data && isset($op_session_data['login_warehouse_id']))
        {

            $book_price = $this->getBookPrice($product_id,$op_session_data['login_warehouse_id']);

        }else{
            $book_price = $this->getBookPrice($product_id,0);
        }


        if($book_price !== false)
        {
            return $book_price;
        }
        return $price;
    }

    public function getBookPrice($product_id,$store_id=false){


        $books = $this->db->getBookByProduct($product_id,$store_id);

        if(!empty($books))
        {
            $list = array();
            foreach($books as $book)
            {
                $from_date = $book['from_date'];
                $to_date = $book['to_date'];
                if(!isset($book['price']) || $book['price'] == '')
                {
                    continue;
                }
                if($from_date)
                {
                    $from_date .= ' 00:00:00';
                    $from_time = strtotime($from_date);
                    if($from_time > time())
                    {
                        continue;
                    }
                }
                if($to_date)
                {
                    $to_date .= ' 23:59:00';
                    $to_time = strtotime($to_date);
                    if($to_time < time())
                    {
                        continue;
                    }
                }

                $priority = $book['priority'];
                $item_id = $book['book_product_id'];
                $list[$priority][$item_id] = $book['price'];
            }

            if(!empty($list))
            {

                $priority_keys = array_keys($list);
                $max_priority = max($priority_keys);
                $priority_list = $list[$max_priority];

                $id_keys = array_keys($priority_list);
                $id_max = max($id_keys);
                $filterd_price = $priority_list[$id_max];
                return $filterd_price;
            }
        }
        return false;
    }

}