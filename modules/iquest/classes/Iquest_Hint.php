<?php
require_once("Iquest_file.php");


class Iquest_Hint extends Iquest_file{
    public $clue_id;
    public $timeout;
    public $show_at;
    public $for_sale;
    public $price;
    public $ordering;

    /**
     *  Fetch hints form DB
     */
    static function fetch($opt=array()){
        global $data, $config;

        /* table's name */
        $th_name  = &$config->data_sql->iquest_hint->table_name;
        $tc_name  = &$config->data_sql->iquest_clue->table_name;
        $tt_name  = &$config->data_sql->iquest_hint_team->table_name;
        /* col names */
        $ch      = &$config->data_sql->iquest_hint->cols;
        $cc      = &$config->data_sql->iquest_clue->cols;
        $ct      = &$config->data_sql->iquest_hint_team->cols;

        $qw = array();
        $join = array();
        $cols = "";
        if (isset($opt['clue_id'])) $qw[] = "h.".$ch->clue_id." = ".$data->sql_format($opt['clue_id'], "s");
        if (isset($opt['ref_id']))  $qw[] = "h.".$ch->ref_id." = ".$data->sql_format($opt['ref_id'], "s");
        if (isset($opt['team_id'])){
            $team = Iquest_Team::fetch_by_id($opt['team_id']);
            $team_time_sql = $team->get_time_sql();

            $qw[] = "t.".$ct->team_id." = ".$data->sql_format($opt['team_id'], "s");
            $join[] = " join ".$tt_name." t on h.".$ch->id." = t.".$ct->hint_id;
            $cols .= ", UNIX_TIMESTAMP(t.".$ct->show_at.") as ".$ct->show_at."
                      , ".$ct->for_sale;

            if (!empty($opt['accessible'])){
                $qw[] = "(t.{$ct->show_at} <= $team_time_sql and t.{$ct->show_at} != 0)";
            }

            if (!empty($opt['not_accessible'])){
                $qw[] = "(t.{$ct->show_at} > $team_time_sql or t.{$ct->show_at} = 0)";
            }

            if (!empty($opt['for_sale'])){
                $qw[] = "t.".$ct->for_sale." = 1";
            }
        }

        if (isset($opt['cgrp_id'])){
            $qw[] = "c.".$cc->cgrp_id." = ".$data->sql_format($opt['cgrp_id'], "s");
            $join[] = " join ".$tc_name." c on c.".$cc->id." = h.".$ch->clue_id;
        }

        if (isset($opt['unscheduled_team_id'])){
            //Get only those hints that are not scheduled to be shown to given team yet
            $q2 = "select ".$ct->hint_id."
                   from ".$tt_name."
                   where ".$ct->team_id." = ".$data->sql_format($opt['unscheduled_team_id'], "s");

            $qw[] = "h.".$ch->id." not in (".$q2.")";
        }


        if ($qw) $qw = " where ".implode(' and ', $qw);
        else $qw = "";

        $o_order_by = (isset($opt['order_by'])) ? $opt['order_by'] : "timeout";
        $o_order_desc = (!empty($opt['order_desc'])) ? "desc" : "";

        $q = "select h.".$ch->id.",
                     h.".$ch->ref_id.",
                     h.".$ch->clue_id.",
                     h.".$ch->filename.",
                     h.".$ch->content_type.",
                     time_to_sec(h.".$ch->timeout.") as ".$ch->timeout.",
                     h.".$ch->price.",
                     h.".$ch->ordering.",
                     h.".$ch->comment.
                     $cols."
              from ".$th_name." h ".implode(" ", $join).
              $qw;

        if ($o_order_by) {
            $q .= " order by ".$ch->$o_order_by." ".$o_order_desc;
        }

        $res=$data->db->query($q);
        $res->setFetchMode(PDO::FETCH_ASSOC);

        $out = array();
        while ($row=$res->fetch()){
            if (!isset($row[$ct->show_at]))  $row[$ct->show_at] = null;
            if (!isset($row[$ct->for_sale])) $row[$ct->for_sale] = null;

            $out[$row[$ch->id]] =  new Iquest_Hint($row[$ch->id],
                                                   $row[$ch->ref_id],
                                                   $row[$ch->filename],
                                                   $row[$ch->content_type],
                                                   $row[$ch->comment],
                                                   $row[$ch->clue_id],
                                                   $row[$ch->timeout],
                                                   $row[$ch->price],
                                                   $row[$ch->ordering],
                                                   $row[$ct->show_at],
                                                   $row[$ct->for_sale]);
        }
        $res->closeCursor();
        return $out;
    }

    /**
     *  Schedule time to show new hint for team $team_id.
     *  This function do not check whether it is already scheduled!
     */
    static function schedule($id, $team_id, $timeout, $for_sale, $open_ts = null){
        global $data, $config;

        /* table's name */
        $t_name  = &$config->data_sql->iquest_hint_team->table_name;
        /* col names */
        $c       = &$config->data_sql->iquest_hint_team->cols;

        if (!$open_ts) {
            $team = Iquest_Team::fetch_by_id($team_id);
            $open_ts = $team->get_time();
        }

        // TODO: store current time shift in the table, update all INSER/UPDATE statements...

        // if timeout is not specified set it to zero
        if ($timeout) $show_at_sql = "FROM_UNIXTIME(".$data->sql_format($open_ts + $timeout, "n").")";
        else $show_at_sql = 0;

        $q="insert into ".$t_name." (
                    ".$c->hint_id.",
                    ".$c->team_id.",
                    ".$c->show_at.",
                    ".$c->for_sale.")
            values (".$data->sql_format($id,        "s").",
                    ".$data->sql_format($team_id,   "n").",
                    $show_at_sql,
                    ".$data->sql_format($for_sale,  "n").")";

        $res=$data->db->query($q);

        return true;
    }

    /**
     *  De-schedule displaying of hint by $clue_id for team $team_id.
     *  If the hint is not displayed yet, it will not be displayed never.
     */
    static function deschedule($clue_ids, $team_id){
        global $data, $config;

        /* table's name */
        $th_name  = &$config->data_sql->iquest_hint->table_name;
        $tt_name  = &$config->data_sql->iquest_hint_team->table_name;
        /* col names */
        $ch      = &$config->data_sql->iquest_hint->cols;
        $ct      = &$config->data_sql->iquest_hint_team->cols;

        $team = Iquest_Team::fetch_by_id($team_id);
        $team_time_sql = $team->get_time_sql();

        $q2 = "select ".$ch->id."
               from ".$th_name."
               where ".$data->get_sql_in($ch->clue_id, $clue_ids, true);

        $qw = array();
        $qw[] = $ct->team_id." = ".$data->sql_format($team_id, "n");
        $qw[] = $ct->hint_id." in (".$q2.")";
        $qw[] = "({$ct->show_at} > $team_time_sql or {$ct->show_at}=0)";
        $qw = " where ".implode(' and ', $qw);

        $q = "delete from ".$tt_name.$qw;

        $res=$data->db->query($q);

        return true;
    }

    /**
     *  Buy the hint.
     *  Change scheduled time to show the hint for team $team_id to NOW and
     *  mark the hint it is not longer for sale.
     *
     *  The hint must be already scheduled. This function do not check whether
     *  it is already scheduled!
     *
     *  This function also do not check price of the hint
     */
    static function buy($id, $team_id){
        global $data, $config;

        /* table's name */
        $t_name  = &$config->data_sql->iquest_hint_team->table_name;
        /* col names */
        $c       = &$config->data_sql->iquest_hint_team->cols;

        $team = Iquest_Team::fetch_by_id($team_id);
        $team_time_sql = $team->get_time_sql();

        $q = "update ".$t_name." set
                ".$c->show_at." = $team_time_sql,
                ".$c->for_sale." = 0
              where ".$c->hint_id." = ".$data->sql_format($id,      "s")." and
                    ".$c->team_id." = ".$data->sql_format($team_id, "n");

        $res=$data->db->query($q);

        return true;
    }

    /**
     * Return next hint to be shown (by timeout)
     *
     * @param int $team_id
     * @return assoc
     */
    static function get_next_scheduled($team_id){
        global $data, $config;

        /* table's name */
        $t_name  = &$config->data_sql->iquest_hint_team->table_name;
        /* col names */
        $c       = &$config->data_sql->iquest_hint_team->cols;

        $team = Iquest_Team::fetch_by_id($team_id);
        $team_time_sql = $team->get_time_sql();

        $qw = array();
        $qw[] = $c->show_at." > $team_time_sql";
        $qw[] = $c->team_id." = ".$data->sql_format($team_id, "n");

        if ($qw) $qw = " where ".implode(' and ', $qw);
        else $qw = "";

        $q = "select UNIX_TIMESTAMP(".$c->show_at.") as ".$c->show_at.",
                     ".$c->hint_id."
              from ".$t_name.$qw;

        $q .= " order by ".$c->show_at;
        $q .= $data->get_sql_limit_phrase(0, 1);

        $res=$data->db->query($q);
        $res->setFetchMode(PDO::FETCH_ASSOC);

        $out = null;
        if ($row=$res->fetch()){
            $out=array("show_at"     => $row[$c->show_at],
                       "hint_id" => $row[$c->hint_id]);
        }
        $res->closeCursor();

        return $out;
    }

    function __construct($id, $ref_id, $filename, $content_type, $comment, $clue_id, $timeout, $price, $ordering, $show_at=null, $for_sale=null){
        parent::__construct($id, $ref_id, $filename, $content_type, $comment);

        $this->clue_id = $clue_id;
        $this->timeout = $timeout;
        $this->price = $price;
        $this->ordering = $ordering;
        $this->show_at = $show_at;
        $this->for_sale = $for_sale;
    }

    function insert(){
        global $data, $config;

        /* table's name */
        $t_name = &$config->data_sql->iquest_hint->table_name;
        /* col names */
        $c      = &$config->data_sql->iquest_hint->cols;

        $q = "insert into ".$t_name."(
                    ".$c->id.",
                    ".$c->ref_id.",
                    ".$c->filename.",
                    ".$c->content_type.",
                    ".$c->comment.",
                    ".$c->clue_id.",
                    ".$c->timeout.",
                    ".$c->price.",
                    ".$c->ordering."
              )
              values(
                    ".$data->sql_format($this->id,              "s").",
                    ".$data->sql_format($this->ref_id,          "s").",
                    ".$data->sql_format($this->filename,        "s").",
                    ".$data->sql_format($this->content_type,    "s").",
                    ".$data->sql_format($this->comment,         "S").",
                    ".$data->sql_format($this->clue_id,         "s").",
                    sec_to_time(".$data->sql_format($this->timeout, "n")."),
                    ".$data->sql_format($this->price,           "n").",
                    ".$data->sql_format($this->ordering,        "n")."
              )";

        $res=$data->db->query($q);
    }


    function to_smarty(){
        $out = parent::to_smarty();
        $out['timeout'] = gmdate("H:i:s", $this->timeout);
        $out['price'] = $this->price;

        return $out;
    }

}
