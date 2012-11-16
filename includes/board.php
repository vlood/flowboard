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


}

?>