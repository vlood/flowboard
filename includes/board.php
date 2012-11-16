<?php

class FlowBoard_Board{



    static function default_zones(){
        return array('ToDo','Working','Test','Done');
    }

    static function get_zones($id){
        $meta = get_post_meta($id,flowboard_metadata());
        $meta = json_decode($meta[0]);
        $zones = $meta->zones;
        if (!is_array($zones)){
            $zones = FlowBoard_Board::default_zones();
        }
        return $zones;
    }

    static function get_zindex_top($id){
        $result = 0;
        $posts = FlowBoard_Board::all_notes($id);
        foreach($posts as $post){
            list($left, $top, $zindex) = explode('x', $post->xyz);
            if ($result<$zindex) $result = $zindex;
        }
        return $result;
    }

    static function all_notes($id){
        $args = array('post_type'=>'flowboard_note','numberposts'=>-1,'meta_key'=>flowboard_metakey(),'meta_value'=>$id);
        $posts = get_posts($args);
        return $posts;
    }


}

?>