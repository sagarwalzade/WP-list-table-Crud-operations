<?php

 /*
 * WP list table class to list all ip address data in wp list table. 
 */

class swlp_ip_address_list extends WP_List_Table
{ 
    function __construct()
    {
        global $status, $page;

        parent::__construct(array(
            'singular' => 'IP Address',
            'plural' => 'IP Addresses',
        ));
    }


    function column_default($item, $column_name)
    {
        return $item[$column_name];
    }


    function column_ip_address($item)
    {

        $actions = array(
            'edit' => sprintf('<a href="?page=swlp_ip_address_form&id=%s">%s</a>', $item['id'], __('Edit', 'swlp')),
            'delete' => sprintf('<a href="?page=%s&action=delete&id=%s">%s</a>', $_REQUEST['page'], $item['id'], __('Delete', 'swlp')),
        );

        return sprintf('%s %s',
            $item['ip_address'],
            $this->row_actions($actions)
        );
    }


    function column_cb($item)
    {
        return sprintf(
            '<input type="checkbox" name="id[]" value="%s" />',
            $item['id']
        );
    }

    function get_columns()
    {
        $columns = array(
            'cb' => '<input type="checkbox" />', 
            'ip_address' => __('IP Address', 'swlp'),
            'note' => __('Note', 'swlp'),
        );
        return $columns;
    }

    function get_sortable_columns()
    {
        $sortable_columns = array(
            'ip_address' => array('ip_address', true),
        );
        return $sortable_columns;
    }

    function get_bulk_actions()
    {
        $actions = array(
            'delete' => 'Delete'
        );
        return $actions;
    }

    function process_bulk_action()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'swlp_ip_address'; 

        if ('delete' === $this->current_action()) {
            $ids = isset($_REQUEST['id']) ? $_REQUEST['id'] : array();
            if (is_array($ids)) $ids = implode(',', $ids);

            if (!empty($ids)) {
                $wpdb->query("DELETE FROM $table_name WHERE id IN($ids)");
            }
        }
    }

    function prepare_items()
    {
        global $wpdb;

        $table_name = $wpdb->prefix . 'swlp_ip_address'; 

        $per_page = 10;

        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();
        
        $this->_column_headers = array($columns, $hidden, $sortable);

        $this->process_bulk_action();

        $paged = isset($_REQUEST['paged']) ? max(0, intval($_REQUEST['paged']) - 1) : 0;
        $orderby = (isset($_REQUEST['orderby']) && in_array($_REQUEST['orderby'], array_keys($this->get_sortable_columns()))) ? $_REQUEST['orderby'] : 'id';
        $order = (isset($_REQUEST['order']) && in_array($_REQUEST['order'], array('asc', 'desc'))) ? $_REQUEST['order'] : 'desc';

        $OFFSET = $paged * $per_page;

        $search_term = isset($_REQUEST['s']) ? trim($_REQUEST['s']) : "";

        if(!empty($search_term)){
            $this->items = $wpdb->get_results("SELECT * FROM $table_name WHERE ip_address LIKE '%".$search_term."%' ORDER BY ". $orderby ." ". $order ." LIMIT ".$per_page." OFFSET ".$OFFSET, ARRAY_A);
            $total_items = $wpdb->get_results("SELECT * FROM $table_name WHERE ip_address LIKE '%".$search_term."%' ORDER BY ". $orderby ." ". $order);
            $total_items = count($total_items);
        }else{
            $this->items = $wpdb->get_results("SELECT * FROM $table_name WHERE ip_address LIKE '%".$search_term."%' ORDER BY ". $orderby ." ". $order ." LIMIT ".$per_page." OFFSET ".$OFFSET, ARRAY_A);
            $total_items = $wpdb->get_results("SELECT * FROM $table_name ORDER BY $orderby $order");
            $total_items = count($total_items);
        }


        $this->set_pagination_args(array(
            'total_items' => $total_items, 
            'per_page' => $per_page,
            'total_pages' => ceil($total_items / $per_page) 
        ));
    }
}

function swlp_admin_menu(){
    add_menu_page(__('Locations', 'swlp'), __('Locations', 'swlp'), 'activate_plugins', 'locations', 'swlp_ip_address_page_handler', 'dashicons-admin-site-alt3', 55);

    add_submenu_page('locations', __('IP Addresses', 'swlp'), __('IP Addresses', 'swlp'), 'activate_plugins', 'swlp_ip_address', 'swlp_ip_address_page_handler');
    add_submenu_page('locations', __('Add IP Address', 'swlp'), __('Add IP Address', 'swlp'), 'manage_options', 'swlp_ip_address_form', 'swlp_ip_address_form_page_handler');

    remove_submenu_page('locations', 'locations');
}

add_action('admin_menu', 'swlp_admin_menu');


function swlp_ip_address_page_handler()
{
    global $wpdb;

    $table = new swlp_ip_address_list();
    $table->prepare_items();

    $message = '';
    if ('delete' === $table->current_action()) {
        $message = '<div class="updated below-h2" id="message"><p>' . sprintf(__('Items deleted: %d', 'swlp'), (is_array($_REQUEST['id'])?count($_REQUEST['id']):1)) . '</p></div>';
    }
    ?>
    <div class="wrap">
        <div class="icon32 icon32-posts-post" id="icon-edit"><br></div>
        <h2><?php _e('IP Address', 'swlp')?> <a class="add-new-h2"
         href="<?php echo get_admin_url(get_current_blog_id(), 'admin.php?page=swlp_ip_address_form');?>"><?php _e('Add new ip address', 'swlp')?></a>
     </h2>
     <?php echo $message; ?>

     <form id="contacts-table" method="GET">
        <input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>"/>
        <?php 
        $table->search_box("Search Post", "search_post_id");
        $table->display();
        ?>
    </form>

</div>
<?php
}


function swlp_ip_address_form_page_handler()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'swlp_ip_address'; 

    $message = '';
    $notice = '';


    $default = array(
        'id' => 0,
        'ip_address' => '',
        'note' => '',
    );


    if ( isset($_REQUEST['nonce']) && wp_verify_nonce($_REQUEST['nonce'], basename(__FILE__))) {

        $item = shortcode_atts($default, $_REQUEST);     

        $item_valid = swlp_validate_ip_address($item);
        if ($item_valid === true) {
            if ($item['id'] == 0) {
                $ip_exist = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $table_name WHERE ip_address = '".$item['ip_address']."'" ));
                if(empty($ip_exist)){
                    $result = $wpdb->insert($table_name, array('ip_address'=>$item['ip_address'], 'note'=>$item['note']));
                    $item['id'] = $wpdb->insert_id;
                    $message = __('Item successfully saved', 'swlp');
                }else{
                    $notice = __('IP Address already exist..!!!', 'swlp');
                }
            } else {
                $result = $wpdb->update($table_name, array('ip_address'=>$item['ip_address'], 'note'=>$item['note']), array('id' => $item['id']));
                $message = __('Item successfully updated', 'swlp');
            }
        } else {

            $notice = $item_valid;
        }
    }

    $item = $default;
    if (isset($_GET['id'])) {
        $item = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $_REQUEST['id']), ARRAY_A);
        if (!$item) {
            $item = $default;
            $notice = __('Item not found', 'swlp');
        }
    }

    
    add_meta_box('ip_address_form_meta_box', __('Ip Address data', 'swlp'), 'swlp_ip_address_form_meta_box_handler', 'ip_address', 'normal', 'default');

    ?>
    <div class="wrap">
        <div class="icon32 icon32-posts-post" id="icon-edit"><br></div>
        <h2><?php _e('IP Address', 'swlp')?> <a class="add-new-h2"
            href="<?php echo get_admin_url(get_current_blog_id(), 'admin.php?page=swlp_ip_address');?>"><?php _e('back to list', 'swlp')?></a>
        </h2>

        <?php if (!empty($notice)): ?>
            <div id="notice" class="error"><p><?php echo $notice ?></p></div>
        <?php endif;?>
        <?php if (!empty($message)): ?>
            <div id="message" class="updated"><p><?php echo $message ?></p></div>
        <?php endif;?>

        <form id="form" method="POST">
            <input type="hidden" name="nonce" value="<?php echo wp_create_nonce(basename(__FILE__))?>"/>

            <input type="hidden" name="id" value="<?php echo $item['id'] ?>"/>

            <div class="metabox-holder" id="poststuff">
                <div id="post-body">
                    <div id="post-body-content">
                        <?php do_meta_boxes('ip_address', 'normal', $item); ?>
                    </div>
                </div>
            </div>
        </form>
    </div>
    <?php
}

function swlp_ip_address_form_meta_box_handler($item)
{
    ?>
    <tbody>
        <div class="formdata">
            <form>
                <p>			
                  <label for="ip_address"><?php _e('IP Address:', 'swlp')?></label>
                  <br>	
                  <input id="ip_address" name="ip_address" type="text" style="width: 100%" value="<?php echo esc_attr($item['ip_address'])?>"
                  required>                  
              </p>
              <p>
                <label for="note"><?php _e('Note:', 'swlp')?></label>
                <br>  
                <textarea id="note" name="note" rows="6" style="width: 100%"><?php echo esc_attr($item['note'])?></textarea>     
            </p>
            <p>
              <input type="submit" value="<?php _e('Save', 'swlp')?>" id="submit" class="button-primary" name="submit" style='margin-top: 15px;'>
          </p>
      </form>
  </div>
</tbody>
<?php
}

function swlp_validate_ip_address($item){
    $messages = array();

    if (empty($item['ip_address'])) $messages[] = __('IP is required', 'swlp');    

    if (empty($messages)) return true;
    return implode('<br />', $messages);
}