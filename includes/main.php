<?php

class FlowBoard_Main{

    function __construct()
    {
        add_action('wp_print_styles', array(&$this, 'wp_print_styles'));
        add_action('init', array(&$this, 'init'));
        add_action('admin_init', array(&$this, 'admin_init'));
        add_action('admin_menu', array(&$this, 'remove_submenus'));
        add_action( 'save_post', array( &$this, 'save_post' ) );
        add_action( 'admin_enqueue_scripts', array( &$this, 'admin_enqueue_scripts' ) );
        add_action( 'wp_footer', array( &$this, 'wp_footer' ) );

    }

    function wp_footer(){

        echo '<!--';
        wp_editor( '', 'editorforinitialization' );
        echo '-->';

    }

    function save_post( $post_id ){

        global $post;

        if ($post->post_type == 'flowboard_note'){
            //Get intro text from the postback
            $board = esc_attr( $_POST['flowboard_board'] );
            update_post_meta( $post->ID, flowboard_metakey(), $board );
        }

        if ($post->post_type == 'flowboard_board'){

            $z = $_POST['zone_ui_dynamic'];
            if (is_array($z)){
                foreach ( $z as $zone ) {
                    if ( ! empty( $zone ) )	$store_zones[] = $zone;
                }
            }
            else
            {
                $store_zones = FlowBoard_Board::default_zones();
            }

            $meta['zones'] = $store_zones;
            if (!update_post_meta($post->ID, flowboard_metadata(), json_encode($meta)))
            {
                add_post_meta($post->ID, flowboard_metadata(), json_encode($meta),true);
            }
        }

        return $post_id;

    }

    function remove_submenus(){
        global $submenu;
        unset($submenu['edit.php?post_type=flowboard_note'][10]); // Removes 'Add New'.
    }

    function admin_init(){

        add_meta_box( 'flowboard_board_zones', __( 'FlowBoard Zones', 'flowboard' ), array( &$this, 'show_zones' ), 'flowboard_board', 'normal', 'default' );
        add_meta_box( 'flowboard_note_board', __( 'Flowboard properties', 'flowboard' ), array( &$this, 'show_note_properties' ), 'flowboard_note', 'side', 'default' );

    }

    function admin_enqueue_scripts() {

        wp_enqueue_script(array('jquery', 'editor', 'thickbox', 'tinymce_editor' ));

        // Main jQuery
        $src = WP_PLUGIN_URL . '/flowboard/js/metabox.js';
        wp_deregister_script( 'flowboard_metabox' );
        wp_register_script( 'flowboard_metabox', $src );
        wp_enqueue_script( 'flowboard_metabox' );
    }

    function show_note_properties(){
        global $post;

        $board = get_post_meta($post->ID, flowboard_metakey(), true);

        ?>
            <strong><?php _e('Board','flowboard'); ?>:</strong>
            (<a href="<?php echo admin_url('edit.php?post_type=flowboard_board'); ?>"><?php _e('manage boards here!','flowboard'); ?></a>)
            <select name="flowboard_board" id="flowboard_board">
                <option value="0"><?php _e('--- choose assigned board ---','flowboard'); ?></option>
                <?php
                $args = array('post_type'=>'flowboard_board','numberposts'=>'-1');
                $posts = get_posts($args);
                foreach($posts as $p){
                    echo '<option value="'.$p->ID.'"';
                    if ($board == $p->ID) echo ' selected';
                    echo '>' . $p->post_title . '</option>' . "\r\n";
                }
                ?>
            </select>
        </p>
    <?php
    }

    function show_zones(){
        global $post;

        echo '<p>';

        $zones = FlowBoard_Board::get_zones($post->ID);

        ?>
        <div class="flowboard_zones_control">

          <div style="margin-bottom: 4px;">
              <input type="button" class="button-secondary" value="<?php _e( 'Add', 'flowboard' ); ?>"
                     id="flowboard_zones_add" />
              <input type="button" class="button-secondary" value="<?php _e( 'Remove', 'flowboard' ); ?>"
                     id="flowboard_zones_remove" />
              <input type="button" class="button-secondary" value="<?php _e( 'Reset', 'flowboard' ); ?>"
                     id="flowboard_zones_reset" />
          </div>

          <div id="flowboard_zones_ui">

              <?php
              foreach ( $zones as $key => $z ) {
                  ?>
                  <div class="zone_ui_div">
                      <input type="text" class="text zone_ui_dynamic" name="zone_ui_dynamic[]"
                             value="<?php echo $z; ?>" />
                  </div>
                  <?php
              }
              ?>

          </div>
        <?php

        echo '</p>';

        return true;
    }

    /*Required stylesheets */
    function wp_print_styles() {
        $myStyleUrl = WP_PLUGIN_URL . '/flowboard/styles.css';
        $myStyleFile = WP_PLUGIN_DIR . '/flowboard/styles.css';

        if ( file_exists($myStyleFile) ) {
            wp_register_style('FlowBoardStyleSheets', $myStyleUrl);
            wp_enqueue_style( 'FlowBoardStyleSheets');
        }
    }

    function init() {

        register_post_type('flowboard_note', array(	'label' => 'Notes','description' => 'FlowBoard custom post types','public' => true,'show_ui' => true,'show_in_menu' => true,'capability_type' => 'post','hierarchical' => false,'rewrite' => array('slug' => ''),'query_var' => true,'exclude_from_search' => false,'supports' => array('title','editor','comments',),'labels' => array (
            'name' => 'Notes',
            'singular_name' => 'Note',
            'menu_name' => 'Notes',
            'add_new' => 'Add Note',
            'add_new_item' => 'Add New Note',
            'edit' => 'Edit',
            'edit_item' => 'Edit Note',
            'new_item' => 'New Note',
            'view' => 'View Note',
            'view_item' => 'View Note',
            'search_items' => 'Search Notes',
            'not_found' => 'No Notes Found',
            'not_found_in_trash' => 'No Notes Found in Trash',
            'parent' => 'Parent Note',
        ),) );

        register_post_type('flowboard_board', array(	'label' => 'Boards','description' => '','public' => false,'show_ui' => true,'show_in_menu' => 'edit.php?post_type=flowboard_note','capability_type' => 'post','hierarchical' => false,'rewrite' => array('slug' => ''),'query_var' => true,'exclude_from_search' => true,'supports' => array('title',),'labels' => array (
            'name' => 'Boards',
            'singular_name' => 'Board',
            'menu_name' => 'Boards',
            'add_new' => 'Add Board',
            'add_new_item' => 'Add New Board',
            'edit' => 'Edit',
            'edit_item' => 'Edit Board',
            'new_item' => 'New Board',
            'view' => 'View Board',
            'view_item' => 'View Board',
            'search_items' => 'Search Boards',
            'not_found' => 'No Boards Found',
            'not_found_in_trash' => 'No Boards Found in Trash',
            'parent' => 'Parent Board',
        ),) );

        if (!is_admin()) {

            //Kolla av nu om användaren har access alternativt om Public Access är aktiverat.
            $options = get_option('flowboard_plugin_options');

            if (current_user_can('edit_posts') || $options['public_access'])
            {

            }
        }
    }


}

?>