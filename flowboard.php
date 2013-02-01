<?php
/*
Plugin Name: FlowBoard
Plugin URI: http://plugins.flowcom.se/flowboard
Description: Web development tool, agile whiteboard with sticky notes. Your notes are posts in WP!
Version: 1.5.3
Author: Flowcom AB, Andreas Ek
Author URI: http://www.flowcom.se
License: GPLv2
*/

include 'includes/ajax.php';
include 'includes/note.php';
//include 'includes/optionpage.php';
include 'includes/main.php';
include 'includes/shortcode.php';
include 'includes/board.php';

$flowboard_ajax = new FlowBoard_Ajax();
//$flowboard_option = new FlowBoard_Option();
$flowboard_main = new FlowBoard_Main();
$flowboard_shortcode = new FlowBoard_Shortcode();

function flowboard_metakey() {
    return '_flowboard_board';
}

function flowboard_metadata() {
    return '_flowboard_meta';
}

load_plugin_textdomain('flowboard', false, basename( dirname( __FILE__ ) ) . '/languages' );

?>