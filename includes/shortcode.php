<?php
/**
 * For shortcode functionality
 */
class FlowBoard_Shortcode {

    function __construct() {

        //Add buttons to tinymce
        add_action( 'media_buttons_context', array( $this, 'media_buttons_context' ) );

        //add some content to the bottom of the page
        //This will be shown in the inline modal
        add_action( 'admin_footer', array( &$this, 'add_inline_popup_content' ) );

        //Adding the shortcodes' functions
        add_shortcode( 'FlowBoard', array(&$this, 'flowboard' ));
        add_shortcode( 'usernotes', array(&$this, 'usernotes' ));

    }

    /* The general start function that replaces inline content with the plugin */
    function flowboard($atts) {

        extract( shortcode_atts( array(
            'override' => '0',
            'id' => 1
        ), $atts ) );

        $meta = get_post_meta($id, flowboard_metadata());
        $meta = json_decode($meta[0]);

        if ( current_user_can( 'edit_posts' ) || $override ){

            wp_enqueue_script('jquery');
            wp_admin_css('thickbox');
            wp_enqueue_script('jquery-ui-draggable');
            wp_enqueue_script('jquery-ui-droppable');
            add_thickbox();

            $myScriptUrl = WP_PLUGIN_URL . '/flowboard/js/script.js';
            wp_deregister_script( 'flowboardjs' );
            wp_register_script( 'flowboardjs', $myScriptUrl);
            wp_enqueue_script( 'flowboardjs' );
            wp_localize_script( 'flowboardjs', 'FlowBoardAjax', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );

            $myScriptUrl = WP_PLUGIN_URL . '/flowboard/js/jquery.ui.touch-punch.min.js';
            wp_deregister_script( 'touch-punch' );
            wp_register_script( 'touch-punch', $myScriptUrl );
            wp_enqueue_script( 'touch-punch' );
        }

        $board = "<!--FlowBoard-->";

        /*$board .= '<div id="flowboardmain_'.$id.'" class="flowboardmain" style="';
        $options = get_option('flowboard_plugin_options');
        if ($options['board_background']!=NULL) {
            $board = $board.'background-image: url('.$options['board_background'].');';
        }
        else {
            $board = $board.'background-image: url('.WP_PLUGIN_URL.'/flowboard/img/flowboard_bg.jpg);';
        }
        $board = $board.'">';
        */

        $board .= '<div id="flowboardmain_'.$id.'" class="flowboardmain">';

        if (current_user_can('edit_posts') || $override){
            $board = $board.'<a href="'.admin_url('admin-ajax.php').'?action=flowboard_note_form&id=0&height=550&width=600&board='.$id.'" class="thickbox" title="'.__('Add new note', 'flowboard').'">';
            $board .= '<img src="'.WP_PLUGIN_URL.'/flowboard/img/button.png" alt="'.__('add new note', 'flowboard').'" />';
            $board .= '</a><br/>';
        }


        $zones = FlowBoard_Board::get_zones($id);

        foreach($zones as $key => $zone){
            $board .= '<div id="flowboard_zone'.$key.'" name="'.$zone.'" class="flowboard_zone" style="width:'.round(95/sizeof($zones)).'%">'.$zone.'</div>';
        }

        $board .= FlowBoard_Note::board_notes($id, $override) . '</div>';

        $board .= '<div style="clear: both;"></div>';

        $board .= "<!--/FlowBoard-->\r\n";

        return $board;
    }

    function usernotes($atts){

        if(!$atts) {
            echo 'no attributes defined';
        }

        extract( $atts );

        $args = array( 'post_type' => 'flowboard_note', 'posts_per_page' => 30, 'author' => $userid );

        $loop = new WP_Query( $args );

        if (isset($userid)) {
            $tasks_author = get_the_author_meta('display_name', $userid);
            echo '<h2>'.__('Responsible', 'FlowBoard').': '.$tasks_author.'</h2>';
        }

        echo '<table><tbody><tr>'.(!isset($userid) ? '<td>'.__('Responsible', 'flowboard').'</td>' : '' ).'
                    <td>'.__('Headline', 'flowboard').'</td>
                    <td>'.__('Text', 'flowboard').'</td>
                    <td>'.__('Board', 'flowboard').'</td>
                    <td>'.__('Status', 'flowboard').'</td>
                    <td>'.__('Time left', 'flowboard').'</td>
                    <td>'.__('Estimate', 'flowboard').'</td></tr>';

        while ( $loop->have_posts() ) {

            $loop->the_post();
            $board_id = get_post_meta(get_the_ID(), '_flowboard_board');

            $dataString = get_post_meta(get_the_ID(), '_flowboard_meta');
            $note_meta = json_decode($dataString[0]);

            echo '<tr>'
                    .(!isset($userid) ? '<td>'.get_the_author_meta('display_name', $userid).'</td>' : '' ).'
                    <td>'.get_the_title().'</td>
                    <td>'.get_the_content().'</td>
                    <td>'.get_post($board_id[0])->post_title.'</td>
                    <td>'.$note_meta->status.'</td>
                    <td>'.$note_meta->timeleft.'</td>
                    <td>'.$note_meta->estimate.'</td></tr>';    
        }
        echo '</tbody></table>';
    }

    function media_buttons_context( $context ) {

        $post_id   = ! empty( $_GET['post'] ) ? (int) $_GET['post'] : 0;
        $post_type = get_post_type( $post_id );

        if ( $post_type == 'flowboard_note' ) return $context;

        $image_btn = WP_PLUGIN_URL . '/flowboard/img/button.png';
        $out       = '<a href="#TB_inline?width=250&height=400&inlineId=popup_flowboard" class="thickbox" title="' . __( 'Add Flowboard here', 'flowboard' ) . '"><img src="' . $image_btn . '" alt="' . __( 'Add FlowBoard here', 'flowboard' ) . '" /></a>';
        return $context . $out;
    }


    function add_inline_popup_content() {

        ?>
    <!--suppress ALL -->
    <div id="popup_flowboard" style="display:none; height: 400px;">
        <h2>Add a FlowBoard</h2>

        <p>
            <select name="flowboard_board" id="flowboard_board">
                <option value="0"><?php _e('--- choose board to insert ---','flowboard'); ?></option>
                <?php
                    $args = array('post_type'=>'flowboard_board','numberposts'=>'-1');
                    $posts = get_posts($args);
                    foreach($posts as $post){
                        echo '<option value="'.$post->ID.'">' . $post->post_title . '</option>' . "\r\n";
                    }
                ?>
            </select> (<a href="<?php echo admin_url('edit.php?post_type=flowboard_board'); ?>"><?php _e('manage boards here!','flowboard'); ?></a>)
        </p>

        <p>
            <input id="flowboard_override" type="checkbox" value="0" /> <?php _e( 'Make the FlowBoard draggable for unauthorized visitors.', 'flowboard' ); ?>
        </p>

        <p>
            <input type="button"
                   onclick="tinyMCE.activeEditor.execCommand('mceInsertContent', 0, flowboardSetShortCode()); tb_remove();"
                   class="button-primary" value="<?php _e( 'Insert', 'flowboard' ); ?>" />&nbsp;
            <input type="button" onclick="tb_remove();" class="button-secondary"
                   value="<?php _e( 'Cancel', 'flowboard' ); ?>" />
        </p>
    </div>

        <script language="javascript">
            function flowboardSetShortCode() {

                var override    = jQuery('#flowboard_override').is(':checked');
                var board       = parseInt(jQuery('#flowboard_board').val());
                _boardkey = 0;

                if (!board) return '';

                var shortcode = '[FlowBoard id=' + board;

                if (override) shortcode += ' override=1';

                shortcode += ']';

                return shortcode;

            }
        </script>

    <?php
    }


}

?>