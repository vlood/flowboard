<?php

class FlowBoard_Note{

    public $id;
    public $board;
    public $author;
    public $body;
    public $color;
    public $timeleft;
    public $estimate;
    public $data;
    public $left;
    public $top;
    public $zindex;
    public $status;
    public $postcontent;

    /**
     * @param $id
     */
    function __construct($id)
    {
        $id = (int)esc_attr($id);

        if ($id){
            $post = get_post($id);
            $dataString = get_post_meta($post->ID,flowboard_metadata());
            $dataArr = json_decode($dataString[0]);
            $this->id = $id;
            $this->author = $dataArr->author;
            $this->body = $post->post_title;
            $this->color = $dataArr->color;
            $this->timeleft = $dataArr->timeleft;
            $this->estimate = $dataArr->estimate;
            $this->status = $dataArr->status;
            $this->postcontent = $post->post_content;
            list($this->left,$this->top,$this->zindex) = explode('x', $dataArr->xyz);
            $this->board = $dataArr->board;
        }
        else{
            $current_user = wp_get_current_user();
            $this->id = 0;
            $this->author = $current_user->display_name;
            $this->body = "";
            $this->color = "yellow";
            $this->timeleft = "0";
            $this->estimate = "0";
            $this->status = "";
            $this->postcontent = '';
            $this->board = 0;
        }
    }


    public function html($preview=false, $draggable=true){

        $str = "<div ";

        //if (!$preview){
        //    $str .= 'onclick="tb_show(\'Edit note\',\''.admin_url('admin-ajax.php').'?action=flowboard_note&id='.$this->id.'\');" ';
        //}

        $str .= 'id="flowboard_';

        if ($preview) $str .= 'preview_';

        $str .= 'note_'.$this->id.'" class="';

        if ($draggable){
            $str .= 'note ';
        }
        else{
            $str .= 'x_note ';
        }

        $str .= $this->color;

        if ($preview) $str .= ' previewNote';

        $str .= '"';

        if (!$preview){
            $str .= ' style="left:'.$this->left.'px;top:'.$this->top.'px;z-index:'.$this->zindex.'"';
        }

        $str .= '>
                    <div class="body">'.$this->body.'</div>
                    <div class="author">' . $this->author . '</div>
                    <span class="data hidden_field">'.$this->id.'</span>
                    <span class="status hidden_field">'.$this->status.'</span>
                    <span class="color hidden_field">'.$this->color.'</span>
                    <div class="time">
                        <span class="timeleft">'.$this->timeleft.'</span>/<span class="estimate">'.$this->estimate.'</span>
                    </div>
                </div>';

        return $str;
    }

    public function form(){

        $board = get_post_meta($this->id, flowboard_metakey(), true);
        $zones = FlowBoard_Board::get_zones($board);

        $str = '<div id="noteData"> <!-- Holds the form -->
            <form action="" method="post" class="note-form">
                <br/>
                <label for="note-body">'.__('Headline', 'flowboard').'</label>
                <textarea name="note-body" id="note-body" class="pr-body" cols="30" rows="6">'.$this->body.'</textarea>
                <table cellspacing="0" cellpadding="0">
                    <tr>
                        <td>
                            <label for="note-name">'.__('Time left', 'flowboard').'</label>
                            <input type="text" name="note-timeleft" id="note-timeleft" class="pr-timeleft numbersOnly" value="'.$this->timeleft.'" />
                        </td>
                        <td style="width:40px;">&nbsp;</td>
                        <td>
                            <label for="note-estimate">'.__('Estimate', 'flowboard').'</label>
                            <input type="text" name="note-estimate" id="note-estimate" class="pr-estimate numbersOnly" value="'.$this->estimate.'" />
                        </td>
                    </tr>
                </table>
                <label for="note-status">'.__('Status', 'flowboard').'</label>
                <select name="note-status" class="pr-status">
                ';

        foreach($zones as $zone){
            $str .= '<option';
            if ($this->status==$zone) $str.=' selected';
            $str .= '>'.$zone.'</option>';
        }

        //Users dropdown instead of text field
        $users  = get_users(array('orderby' => 'display_name', 'order' => 'ASC'));

        $list_items = '<option value="none">NONE</option>';

        foreach ($users as $cur_user) {
            if (!(strcasecmp($this->author, $cur_user->display_name) || strcasecmp($this->author, $cur_user->user_login))) {
                $list_items = $list_items.'<option selected value="' . $cur_user->user_login . '">' . $cur_user->display_name . '</option>';
            } else {
                $list_items = $list_items.'<option value="' . $cur_user->user_login . '">' . $cur_user->display_name . '</option>';    
            }
            
        }

        $str .= '</select><br/><br/>
                <label for="note-name">'.__('Responsible', 'flowboard').'</label>
                
                <select name="note-name" id="note-name" class="pr-author">
                    ' . $list_items . '
                </select>

                <!--input type="text" name="note-name" id="note-name" class="pr-author" value="'.$this->author.'" /-->
                
                <label>'.__('Color', 'flowboard').'</label> <!-- Clicking one of the divs changes the color of the preview -->
                <div class="color yellow"></div>
                <div class="color blue"></div>
                <div class="color green"></div>
                <div class="color purple"></div>
                <div class="color orange"></div>
                <div class="color pink"></div>
                <input type="hidden" class="pr-board" name="board" id="note-board" value="'.$this->board.'" />
                <br/><br/>
                <label>'.__('Text', 'flowboard').'</label> <!-- Clicking one of the divs changes the color of the preview -->
                <textarea class="pr-postcontent" name="note-postcontent" id="note-postcontent">' . $this->postcontent . '</textarea>
                <div class="clear"></div>
                <button id="note-submit" class="dialog_button button-primary">'.__('Save', 'flowboard').'</button>';

            if ($this->id && is_user_logged_in()){
                $str .= '<button onclick="document.location=\''.admin_url('post.php?post='.$this->id.'&action=edit').'\';" id="note-post" class="dialog_button">'.__('To Post', 'flowboard').'</button>';
            }

            $str .= '
                <!--button id="note-import" class="dialog_button">Import</button-->
                <button id="note-close" onclick="tb_remove();">'.__('Cancel', 'flowboard').'</button>
                <!--span class="note-import-block">Import post with id: <input name="note-import-id" id="note-import-id" class="numbersOnly" /><button id="note-import-enter" class="button-secondary dialog_button">OK</button></span-->
            </form>
            </div>';

        return $str;

    }

    /* getNotes generates a string with inline html notes from wpdb table */
    static function board_notes($board_id, $override) {

        $notes = "";
        $args = array('post_type'=>'flowboard_note', 'meta_key' => flowboard_metakey(), 'meta_value' => $board_id, 'numberposts' => -1 );

        $draggable = $override || current_user_can('edit_posts');

        $custom_posts = get_posts($args);
        foreach($custom_posts as $post){
            if ($post->post_status == 'publish'){
                $note = new FlowBoard_Note($post->ID);
                $notes .= $note->html(false, $draggable);
            }

        }

        return $notes;
    }


}

?>