<div class="wrap op-conten-wrap">
    <h1><?php echo __( 'Edit Price Books', 'woo-book-price' ); ?></h1>

        <div class="form-container">
            <div class="row">
                <div class="col-md-8 col-md-offset-2">
                    <form class="form-horizontal" id="book-form">
                        <input type="hidden" name="book_id" value="<?php echo $current_book_id; ?>">
                        <div class="form-group">
                            <label for="inputEmail3" class="col-sm-2 control-label"><?php echo __( 'Title', 'woo-book-price' ); ?></label>
                            <div class="col-sm-10">
                                <input type="text" class="form-control" id="book_title" value="<?php echo $current_book['title']; ?>" name="title" placeholder="Book Title">
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="inputEmail3" class="col-sm-2 control-label"><?php echo __( 'Outlet', 'woo-book-price' ); ?></label>
                            <div class="col-sm-10">
                                <select class="form-control" name="store_id">
                                    <?php foreach($warehouses as $warehouse): ?>
                                    <option value="<?php echo $warehouse['id']; ?>" <?php echo ($current_book['store_id'] == $warehouse['id'] ) ? 'selected':''; ?> ><?php echo $warehouse['name']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="from_date" class="col-sm-2 control-label"><?php echo __( 'From Date', 'woo-book-price' ); ?></label>
                            <div class="col-sm-8">
                                <input type="text" class="form-control datepicker" value="<?php echo $current_book['from_date']; ?>" id="from_date" name="from_date">
                            </div>
                            <div class="col-sm-2"></div>
                        </div>
                        <div class="form-group">
                            <label for="to_date" class="col-sm-2 control-label"><?php echo __( 'To Date', 'woo-book-price' ); ?></label>
                            <div class="col-sm-8">
                                <input type="text" class="form-control datepicker" value="<?php echo $current_book['to_date']; ?>" id="to_date" name="to_date">
                            </div>
                            <div class="col-sm-2"></div>
                        </div>
                        <div class="form-group">
                            <label for="inputEmail3" class="col-sm-2 control-label"><?php echo __( 'Status', 'woo-book-price' ); ?></label>
                            <div class="col-sm-10">
                                <select class="form-control" name="status">
                                    <option value="1" <?php echo ($current_book['status'] == 1) ? 'selected':''; ?> ><?php echo __( 'Publish', 'woo-book-price' ); ?></option>
                                    <option value="2"<?php echo ($current_book['status'] == 2) ? 'selected':''; ?> ><?php echo __( 'Pending', 'woo-book-price' ); ?></option>

                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="to_date" class="col-sm-2 control-label"><?php echo __( 'Priority', 'woo-book-price' ); ?></label>
                            <div class="col-sm-4">
                                <input type="number" class="form-control" value="<?php echo $current_book['priority']; ?>" id="priority" name="priority" value="0">
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="inputEmail3" class="col-sm-2 control-label"><?php echo __( 'Import Product List CSV', 'woo-book-price' ); ?></label>
                            <div class="col-sm-5">
                                <input type="file" id="csv-file-import">
                                <p class="help-block"><?php echo __( 'Upload file follow format of outlet csv after updated', 'woo-book-price' ); ?></p>
                            </div>
                            <div class="col-sm-5">
                                <button type="button" id="import-csv-btn" class="btn btn-info pull-right"><?php echo __( 'Import', 'woo-book-price' ); ?></button>
                                <button type="button" id="download-csv-btn" class="btn btn-default pull-left"><?php echo __( 'Download Outlet CSV', 'woo-book-price' ); ?></button>
                            </div>

                        </div>

                        <button type="button" id="book-save-btn" class="btn btn-primary pull-right"><?php echo __( 'Save', 'woo-book-price' ); ?></button>
                    </form>
                </div>
            </div>

        </div>

    <br class="clear">
    <div class="product-list-container">
        <table id="op-transfer-grid" class="table table-condensed table-hover table-striped op-transfer-grid">
            <thead>
            <tr>
                <th data-column-id="product_id" data-identifier="true" ><?php echo __( 'Product ID', 'woo-book-price' ); ?></th>
                <?php if($_has_openpos): ?>
                <th data-column-id="barcode" data-identifier="false" data-sortable="false"><?php echo __( 'Barcode', 'woo-book-price' ); ?></th>
                <?php endif; ?>
                <th data-column-id="sku" data-identifier="false"  data-sortable="false"><?php echo __( 'Sku', 'woo-book-price' ); ?></th>
                <th data-column-id="product_name" data-identifier="false" data-sortable="false"><?php echo __( 'Product Name', 'woo-book-price' ); ?></th>
                <th data-column-id="price" data-sortable="false"><?php echo __( 'Price', 'woo-book-price' ); ?></th>
                <th data-column-id="view_url" class="text-right" data-sortable="false"></th>
            </tr>
            </thead>
        </table>
    </div>


    <br class="clear">
</div>
<script type="text/javascript">

    (function($) {
        "use strict";

        var files = new Array();
        var book_id = 0;

        var table = $('#op-transfer-grid').DataTable({
            "searching": false,
            "processing": true,
            "serverSide": true,
            ajax: {
                url: "<?php echo admin_url( 'admin-ajax.php?book_id='.$current_book_id ); ?>",
                type: 'post',
                data: {action: 'op_price_book_product_ajax_list',id: $('input[name="book_id"]').val() }
            },
            pageLength : 10
        } );


        $('.datepicker').datetimepicker({
            format: 'MM/DD/YYYY'
        });

        $('input#csv-file-import').change(function(event) {
            files = event.target.files;
        });

        $('#download-csv-btn').click(function () {
            $.ajax({
                url: "<?php echo admin_url( 'admin-ajax.php' ); ?>",
                type: 'post',
                data: $('#book-form').serialize()+'&action=op_download_book_csv',
                dataType: 'json',
                beforeSend:function(){

                },
                success: function(response){
                    if(response['status'] == 1)
                    {
                        window.location = response['data']['export_file'];
                    }
                }
            });
        });
        $('#import-csv-btn').click(function () {
            if(files.length > 0)
            {
                var formData = new FormData();

                formData.append("action", "op_upload_book_csv");
                formData.append("book_id", $('input[name="book_id"]').val());

                formData.append("file", files[0]);

                $.ajax({
                    url: "<?php echo admin_url( 'admin-ajax.php' ); ?>",
                    type: 'post',
                    dataType: 'json',
                    data: formData,
                    contentType: false,
                    processData: false,
                    beforeSend:function(){
                        $('body').addClass('op_loading');
                    },
                    success:function(data){
                        if(data.status == 1)
                        {
                            var book_id = data['data']['book_id'];
                            if(book_id)
                            {
                                $('input[name="book_id"]').val(book_id);
                            }
                            table.ajax.url("<?php echo admin_url( 'admin-ajax.php?book_id=' ); ?>"+book_id).load();


                        }
                        $('body').removeClass('op_loading');

                    },
                    error:function(){
                        $('body').removeClass('op_loading');
                    }
                });
            }else {
                alert("<?php echo __( 'Please choose file', 'woo-book-price' ); ?>");

            }

        });
        $('#book-save-btn').click(function () {
            if($('input[name="title"]').val().length < 1)
            {
                alert("<?php echo __( 'Please enter title', 'woo-book-price' ); ?>");
            }else {
                $.ajax({
                    url: "<?php echo admin_url( 'admin-ajax.php' ); ?>",
                    type: 'post',
                    data: $('#book-form').serialize()+'&action=op_save_book',
                    dataType: 'json',
                    beforeSend:function(){

                    },
                    success: function(response){
                        if(response['status'] == 1)
                        {
                            var book_id = response['data']['book_id'];
                            window.location = '<?php echo admin_url('admin.php?page=op-price-book&action=edit')?>'+'&id='+book_id;
                        }else {
                            alert(response['message']);
                        }
                    }
                });
            }
        });
        $(document).on('click','.save-row',function(){
            var id = $(this).data('id');
            var input_selected = $(document).find('#row-'+id);
            var price = input_selected.val();
            if(price.length < 1)
            {
                alert("<?php echo __( 'Please enter price', 'woo-book-price' ); ?>");
            }else {
                $.ajax({
                    url: "<?php echo admin_url( 'admin-ajax.php' ); ?>",
                    type: 'post',
                    data: {action: 'op_update_book_product',id: id, price: price},
                    dataType: 'json',
                    beforeSend:function(){
                        input_selected.prop('disabled',true);
                    },
                    success: function(response){
                        input_selected.prop('disabled',false);
                    }
                });
            }

        });

        $(document).on('click','.del-row',function(){
            var id = $(this).data('id');
            var input_selected = $(document).find('#row-'+id);

            $.ajax({
                url: "<?php echo admin_url( 'admin-ajax.php' ); ?>",
                type: 'post',
                data: {action: 'op_delete_book_product',id: id},
                dataType: 'json',
                beforeSend:function(){
                    input_selected.prop('disabled',true);
                },
                success: function(response){
                    table.ajax.reload();
                }
            });
        });

    })( jQuery );
</script>