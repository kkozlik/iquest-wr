<?php
/**
 *  File automaticaly included by the framework when module is loaded
 *
 *  @author     Karel Kozlik
 *  @package    iquest
 */

/**
 *  module init function
 *
 *  Is called when all files are included
 */
function iquest_init(){
    global $config, $_SERWEB, $lang_str, $data;

    $data->connect_to_db();

    /* load lang file for this module */
    load_another_lang('iquest');
    require_once($_SERWEB["configdir"] . "config.iquest.php");
}

include_module('traccar');

require_once( "classes/Iquest.php" );

require_once( "classes/Iquest_Clue.php" );
require_once( "classes/Iquest_ClueGrp.php" );
require_once( "classes/Iquest_Hint.php" );
require_once( "classes/Iquest_Solution.php" );
require_once( "classes/Iquest_key.php" );

require_once( "classes/Iquest_solution_graph_node.php" );
require_once( "classes/Iquest_solution_graph.php" );
require_once( "classes/Iquest_contest_graph_simplified.php" );

require_once( "classes/Iquest_Condition.php" );

require_once( "classes/Iquest_Team.php" );
require_once( "classes/Iquest_team_rank.php" );

require_once( "classes/Iquest_Tracker.php" );

require_once( "classes/Iquest_info_msg.php" );

require_once( dirname(__FILE__)."/options.php" );
require_once( dirname(__FILE__)."/events.php" );
