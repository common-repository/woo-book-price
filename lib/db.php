<?php
if(!class_exists('OP_Book_Price'))
{
    class OP_Book_Price{

        public static function install(){
            global $wpdb;

            $sql = "CREATE TABLE IF NOT EXISTS `" . $wpdb->prefix . "openpos_price_book` (
				      `book_id` int(11) NOT NULL AUTO_INCREMENT,
				      `title` VARCHAR(255),
				      `comment` text,
				      `from_date` VARCHAR(255),
                      `to_date` VARCHAR(255),
                      `created_by` int(11) NOT NULL DEFAULT '0',
                      `status` int(11) NOT NULL DEFAULT '0',
                      `priority` int(11) NOT NULL DEFAULT '0',
                      `store_id` int(11) NOT NULL DEFAULT '0',
                      `date_added` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                      PRIMARY KEY (`book_id`)
				) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;";
            $wpdb->query($sql);

            $sql = "CREATE TABLE IF NOT EXISTS `" . $wpdb->prefix . "openpos_price_book_products` (
				      `book_product_id` int(11) NOT NULL AUTO_INCREMENT,
                      `book_id` int(11) NOT NULL DEFAULT '0',
                      `product_id` int(11) NOT NULL DEFAULT '0',
                      `price` decimal(16,2) NOT NULL DEFAULT '0.00',
                      `barcode` VARCHAR(255),
                      `date_added` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                      PRIMARY KEY (`book_product_id`)
				) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;";
            $wpdb->query($sql);
        }

        public function save_draft_book(){
            global $wpdb;
            $wpdb->insert(
                $wpdb->prefix . "openpos_price_book",
                array(
                    'title' => 'temp'
                )
            );
            return $wpdb->insert_id;

        }
        public function save_book($data){
            global $wpdb;
            if(isset($data['book_id']) && $data['book_id'] > 0 )
            {
                $wpdb->replace(
                    $wpdb->prefix . "openpos_price_book",
                    array(
                        'book_id' => (int)$data['book_id'],
                        'title' => esc_textarea($data['title']),
                        'from_date' => $data['from_date'],
                        'to_date' => $data['to_date'],
                        'created_by' => $data['created_by'],
                        'status' => (int)$data['status'],
                        'priority' => (int)$data['priority'],
                        'store_id' => (int)$data['store_id'],
                    )
                );
                return $data['book_id'];
            }else{
                $wpdb->insert(
                    $wpdb->prefix . "openpos_price_book",
                    array(
                        'title' => esc_textarea($data['title']),
                        'from_date' => $data['from_date'],
                        'to_date' => $data['to_date'],
                        'created_by' => (int)$data['created_by'],
                        'status' => (int)$data['status'],
                        'priority' => (int)$data['priority'],
                        'store_id' => (int)$data['store_id'],
                    )
                );
                return $wpdb->insert_id;
            }
        }
        public function save_book_items($data){
            global $wpdb;
            $wpdb->delete( $wpdb->prefix . "openpos_price_book_products", array( 'book_id' => $data['book_id'], 'product_id' => $data['product_id'] ) );

            $wpdb->insert(
                $wpdb->prefix . "openpos_price_book_products",
                array(
                    'book_id' => $data['book_id'],
                    'product_id' => $data['product_id'],
                    'price' => $data['price'],
                    'barcode' => $data['barcode']
                )
            );
            return $wpdb->insert_id;

        }

        public function update_book_item_price($item_id,$price){
            global $wpdb;
            $wpdb->update(
                $wpdb->prefix . "openpos_price_book_products",
                array(
                    'price' => $price,	// string
                ),
                array( 'book_product_id' => (int)$item_id )
            );

        }

        public function delete_book_item($item_id)
        {
            global $wpdb;
            $wpdb->delete( $wpdb->prefix . "openpos_price_book_products", array( 'book_product_id' => (int)$item_id ) );
        }

        public function delete_book($book_id)
        {
            global $wpdb;
            $wpdb->delete( $wpdb->prefix . "openpos_price_book_products", array( 'book_id' => (int)$book_id ) );
            $wpdb->delete( $wpdb->prefix . "openpos_price_book", array( 'book_id' => (int)$book_id ) );
        }

        public function getBookProducts($params){
            global $wpdb;
            $sql_count = "SELECT COUNT(*)  FROM ".$wpdb->prefix."openpos_price_book_products WHERE book_id = ".(int)$params['book_id'];


            $sql = "SELECT * FROM ".$wpdb->prefix."openpos_price_book_products WHERE book_id = ".(int)$params['book_id'];


            $sql .= ' ORDER BY product_id '.$params['order'];

            $sql .= " LIMIT ".$params['start'].','.$params['per_page'];
            $total = $wpdb->get_var($sql_count);
            $rows = $wpdb->get_results( $sql,ARRAY_A );
            return array('total' => $total,'rows' => $rows);
        }

        public function getBook($book_id){
            global $wpdb;
            $sql = "SELECT * FROM ".$wpdb->prefix."openpos_price_book WHERE book_id = ".intval($book_id);
            return $wpdb->get_row($sql,ARRAY_A);
        }
        public function getBooks($params){
            global $wpdb;
            $sql_count = "SELECT COUNT(*)  FROM ".$wpdb->prefix."openpos_price_book WHERE status > 0 ";


            $sql = "SELECT * FROM ".$wpdb->prefix."openpos_price_book WHERE status > 0 ";
            if($params['term'])
            {
                $sql .= ' AND title LIKE "%'.$params['term'].'%"';
            }
            $sql .= ' ORDER BY book_id '.$params['order'];
            $sql .= " LIMIT ".$params['start'].','.$params['per_page'];
            $total = $wpdb->get_var($sql_count);
            $rows = $wpdb->get_results( $sql,ARRAY_A );
            return array('total' => $total,'rows' => $rows);
        }
        public function getBookByProduct($product_id,$store_id = false){
            global $wpdb;
            $sql = "SELECT * FROM ".$wpdb->prefix."openpos_price_book_products AS item LEFT JOIN ".$wpdb->prefix."openpos_price_book AS book ON book.book_id = item.book_id  WHERE book.status = 1 AND item.product_id =".intval($product_id);
            if($store_id !== false)
            {
                $sql .= " AND book.store_id =".$store_id;
            }
            $sql .= " ORDER BY book.date_added DESC";
            $rows = $wpdb->get_results( $sql,ARRAY_A );
            return $rows;
        }
    }

}
?>