<?php

class Iquest{

    /**
     *  Check whether contest already started (START_TIME passed)
     */
    static function is_started(){
        $start_time = Iquest_Options::get(Iquest_Options::START_TIME);

        if (time() < $start_time) return false;
        return true;
    }

    /**
     *  Check whether contest is over (END_TIME passed or team is deactivated)
     */
    static function is_over(){
        // check that team is active (team is deactivated when it give up the contest)
        $team = Iquest_auth::get_logged_in_team();
        if (!$team->is_active()) return true;

        $end_time = Iquest_Options::get(Iquest_Options::END_TIME);

        if (time() < $end_time) return false;
        return true;
    }

    /**
     *  Start the content for the team:
     *
     *  1. Open initial clue group
     *  2. Schedule showing of hints
     *  3. Schedule showing of solution
     */
    static function start($team_id){
        // Make sure it's time to start contest
        if (!self::is_started()) return;

        $cgrp_ids = Iquest_Options::get(Iquest_Options::INITIAL_CGRP_IDS);

        $log_prefix = __FUNCTION__.": Team (ID=$team_id) ";
        sw_log($log_prefix."*** Starting contest for Team", PEAR_LOG_INFO);

        $start_time = Iquest_Options::get(Iquest_Options::START_TIME);
        if (!$start_time) {
            $team = Iquest_Team::fetch_by_id($team_id);
            $start_time = $team->get_time();
        }

        foreach(array_reverse($cgrp_ids) as $cgrp_id)
        {
            // 1. Open new clue group
            self::_open_cgrp($cgrp_id, $team_id, $log_prefix, $start_time);

            // 2. Schedule show time for new hints
            self::_schedule_new_hints($cgrp_id, $team_id, $log_prefix, $start_time);

            // 3. If team gained all clues that lead to some task_solution
            //    schedule showing of the solution
            self::_schedule_solution($cgrp_id, $team_id, $log_prefix, $start_time);
        }
    }


    static function buy_hint($hint, $team_id){
        global $data;

        $data->transaction_start();

        $log_prefix = __FUNCTION__.": Team (ID=$team_id) ";

        // 1. Spend coin from wallet
        sw_log($log_prefix."*** Spending coins ({$hint->price})", PEAR_LOG_INFO);

        $team = Iquest_Team::fetch_by_id($team_id);
        $team->wallet_spend_money($hint->price);

        // 2. Mark the hint as bought
        sw_log($log_prefix."*** Marking the hint as bought (ID={$hint->id})", PEAR_LOG_INFO);

        Iquest_Hint::buy($hint->id, $team_id);

        $data->transaction_commit();
    }


    public static function blow_up(Iquest_Solution $solution, $team_id){
        global $data;

        $log_prefix = __FUNCTION__.": Team (ID=$team_id) ";
        $bomb_value = 1; // always cost one bomb

        // If solution is already solved, skip it.
        if ($solution->is_solved($team_id)){
            sw_log($log_prefix."Cannot blow up solution (ID={$solution->id}). It's already solved", PEAR_LOG_INFO);
            return;
        }

        $data->transaction_start();

        // 1. Spend bomb
        sw_log($log_prefix."*** Spending bomb ({$bomb_value})", PEAR_LOG_INFO);

        $team = Iquest_Team::fetch_by_id($team_id);
        $team->remove_bomb($bomb_value);

        // 2. Schedule the solution to show now
        sw_log($log_prefix."*** Showing the solution (ID={$solution->id})", PEAR_LOG_INFO);

        Iquest_Solution::schedule_update($solution->id, $team_id, 0);

        $data->transaction_commit();
    }


    static function solution_found($solution, $team_id){
        global $data;

        /**
         *  1. Close current task (only if the show_at time did not pass)
         *     Table: task_solution_team.show_at = never
         *     Table: task_solution_team.solved_at = now
         *
         *  2. Open new clue group
         *     Table: open_cgrp_team.gained_at = now
         *
         *  3. Schedule show time for new hints
         *     Table: hint_team.show_at = now+timeout
         *
         *  4. If team gained all clues that lead to some task_solution
         *     set the show_at time
         *     Table: task_solution_team.show_at = now+timeout
         *
         *  5. Hints that has not been displayed and are not needed any more
         *     should not be never showed:
         *     Table: hint_team.show_at = newer
         *
         *  6. Solutions that has not been displayed and are not needed any more
         *     should not be never showed.
         *     Different with the [1] is that this step walk throught whole the
         *     graph of clues/solutions and search for the solutions that are
         *     not realy needed to reach the final task.
         *     Table: task_solution_team.show_at = never
         *
         *  7. Update team ranks
         */

        $log_prefix = __FUNCTION__.": Team (ID=$team_id) ";

        $data->transaction_start();

        // 1. Close current task
        sw_log($log_prefix."*** Closing solution (ID={$solution->id})", PEAR_LOG_INFO);
        Iquest_Solution::deschedule($solution->id, $team_id);
        Iquest_Solution::mark_solved($solution->id, $team_id);

        // 2. Open new clue groups
        // If there are multiple clue groups they are opened in reverse order then
        // their 'ordering' value. This is because the gained_at value may differ by a second
        // and we still want display them by 'ordering' order on team interface
        $next_cgrp_ids = array_reverse($solution->get_next_cgrp_ids_cond($team_id));
        foreach($next_cgrp_ids as $next_cgrp_id){
            self::_open_cgrp($next_cgrp_id, $team_id, $log_prefix);
        }

        self::gain_coins($team_id, $solution->coin_value);
        self::gain_bombs($team_id, $solution->bomb_value);

        // 3. Schedule show time for new hints
        foreach($next_cgrp_ids as $next_cgrp_id){
            self::_schedule_new_hints($next_cgrp_id, $team_id, $log_prefix);
        }

        // 4. If team gained all clues that lead to some task_solution
        //    schedule showing of the solution
        foreach($next_cgrp_ids as $next_cgrp_id){
            self::_schedule_solution($next_cgrp_id, $team_id, $log_prefix);
        }

        // 5. Hints that has not been displayed and are not needed any more
        //    should not be never showed:

        sw_log($log_prefix."*** Check what hints could be de-scheduled to show.", PEAR_LOG_INFO);

        $graph = new Iquest_solution_graph($team_id);
        $del_clue_ids = $graph->get_unneded_clues();

        sw_log($log_prefix."    Clue not more needed: (IDs=".implode(", ", $del_clue_ids).")", PEAR_LOG_INFO);

        if ($del_clue_ids){
            Iquest_Hint::deschedule($del_clue_ids, $team_id);
        }


        // 6. Solutions that has not been displayed and are not needed any more
        //    should not be never showed:

        $del_solution_ids = $graph->get_unneded_solutions();
        sw_log($log_prefix."    Solutions not more needed: (IDs=".implode(", ", $del_solution_ids).")", PEAR_LOG_INFO);
        if ($del_solution_ids){
            Iquest_Solution::deschedule($del_solution_ids, $team_id);
        }


        // 7. Update ranks
        $team_distance = $graph->get_distance_to_finish();
        Iquest_team_rank::add_finish_distance($team_id, $team_distance);
        sw_log($log_prefix."    New distance to finish: $team_distance", PEAR_LOG_INFO);

        Iquest_team_rank::update_ranks();

        unset($graph);

        $data->transaction_commit();


        // send notification that team solved the task
        self::_send_notifications($solution, $team_id);
    }

    /**
     *  Add coins to the wallet of the team
     */
    public static function gain_coins($team_id, $value){
        global $lang_str;

        $log_prefix = __FUNCTION__.": Team (ID=$team_id) ";

        if ($value != 0){
            sw_log($log_prefix."*** Gained coins ($value)", PEAR_LOG_INFO);

            Iquest_info_msg::add_msg(
                str_replace("<value>",
                            $value,
                            $lang_str['iquest_msg_coin_gained']), "coin");

            $team = Iquest_Team::fetch_by_id($team_id);
            $team->wallet_add_money($value);
        }
    }

    /**
     *  Add bombs to the team
     */
    public static function gain_bombs($team_id, $value){
        global $lang_str;

        $log_prefix = __FUNCTION__.": Team (ID=$team_id) ";

        if ($value != 0){
            sw_log($log_prefix."*** Gained bombs ($value)", PEAR_LOG_INFO);

            Iquest_info_msg::add_msg(
                str_replace("<value>",
                            $value,
                            $lang_str['iquest_msg_bomb_gained']), "bomb");

            $team = Iquest_Team::fetch_by_id($team_id);
            $team->add_bomb($value);
        }
    }

    /**
     *  Open new clue group
     */
    private static function _open_cgrp($cgrp_id, $team_id, $log_prefix, $open_ts = null){
        if (!Iquest_ClueGrp::is_accessible($cgrp_id, $team_id)){
            sw_log($log_prefix."*** Opening clue group (ID=$cgrp_id)", PEAR_LOG_INFO);
            Iquest_ClueGrp::open($cgrp_id, $team_id, $open_ts);
        }
    }


    /**
     *  Schedule show time for new hints
     */
    private static function _schedule_new_hints($cgrp_id, $team_id, $log_prefix, $open_ts = null){
        sw_log($log_prefix."*** Schedule show times for new hints.", PEAR_LOG_INFO);

        $clue_grp = &Iquest_ClueGrp::by_id($cgrp_id);
        if (!$clue_grp){
            throw new RuntimeException("Clue group '".$cgrp_id."' does not exists. ");
        }

        $clues = $clue_grp->get_clues();
        foreach($clues as $k=>$v){
            $opt = array("clue_id" => $v->id,
                         "unscheduled_team_id"=>$team_id); // Only hints not scheduled yet
            $hints = Iquest_Hint::fetch($opt);

            foreach($hints as $hk=>$hv){
                sw_log($log_prefix."    scheduling to show hint (ID={$hv->id}) after ".gmdate('H:i:s', $hv->timeout), PEAR_LOG_INFO);
                Iquest_Hint::schedule($hv->id, $team_id, $hv->timeout, ($hv->price > 0), $open_ts);
            }

            unset($hints);
        }

        unset($clues);
        unset($clue_grp);
    }


    /**
     *  If team gained all clues that lead to some task_solution schedule
     *  showing of the solution
     */
    private static function _schedule_solution($cgrp_id, $team_id, $log_prefix, $open_ts = null){

        sw_log($log_prefix."*** Check what solutions could be scheduled to show.", PEAR_LOG_INFO);

        // fetch list of solutions that are opened by gaining the clue group
        $opening_solutions = Iquest_Solution::fetch_by_opening_cgrp($cgrp_id, $team_id);

        foreach($opening_solutions as $opening_solution){
            sw_log($log_prefix."    * Checking solution (ID={$opening_solution->id})", PEAR_LOG_INFO);

            // if solution is already scheduled, skip it
            if (!is_null($opening_solution->show_at)){
                sw_log($log_prefix."      It's already scheduled to ".date("Y-m-d H:i:s", $opening_solution->show_at), PEAR_LOG_INFO);
                continue;
            }

            // If solution is already solved, skip it.
            if ($opening_solution->is_solved($team_id)){
                sw_log($log_prefix."      It's already solved", PEAR_LOG_INFO);
                continue;
            }


            $schedule_solution = true;
            if ($opening_solution->countdown_start==Iquest_Solution::CD_START_ALL){
                // fetch list of all clue groups that opens the solution
                $clue_grps = Iquest_ClueGrp::fetch_by_pointing_to_solution($opening_solution->id, $team_id);
                foreach($clue_grps as $clue_grp){
                    // if any of the clue groups is not gained yet, do not schedule
                    // the solution
                    if (is_null($clue_grp->gained_at)){
                        sw_log($log_prefix."      Clue group (ID={$clue_grp->id}) not gained yet. Not schedule the solution.", PEAR_LOG_INFO);
                        $schedule_solution = false;
                        break;
                    }
                }
            }

            unset($clue_grps);

            if ($schedule_solution){
                if ($opening_solution->timeout > 0){
                    sw_log($log_prefix."      Scheduling show solution (ID={$opening_solution->id}) after ".gmdate('H:i:s', $opening_solution->timeout), PEAR_LOG_INFO);
                    Iquest_Solution::schedule($opening_solution->id, $team_id, $opening_solution->timeout, $open_ts);
                }
                else{
                    sw_log($log_prefix."      Solution (ID={$opening_solution->id}) should not be scheduled to show due to it's timeout is not set.", PEAR_LOG_INFO);
                }
            }
        }

        unset($opening_solutions);
    }


    /**
     *  Send notifications to email once a team found a solution
     */
    private static function _send_notifications($solution, $team_id){
        global $config;

        // if notification for the solution is not configured, exit
        if (!isset($config->iquest->notifications[$solution->id])) return;

        // get team name
        $team = Iquest_Team::fetch_by_id($team_id);


        // prepare email
        $to = implode(",", $config->iquest->notifications[$solution->id]);
        $subject = "Team {$team->name} solved task {$solution->name}";
        $body = $subject."\n At: ".date("H:i:s");

        // set UTF-8 headers
        $subject = "=?UTF-8?B?".base64_encode($subject)."?=";
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .='Content-type: text/plain; charset=utf-8'."\r\n";

        // send the email
        mail($to, $subject, $body, $headers);
    }
}
