<?php
require_once("Iquest_graph_abstract.php");

/**
 *  Class generate image of simplified contest graph 
 */ 
class Iquest_contest_graph_simplified extends Iquest_graph_abstract{
    private $team_id;
    private $cgroups;
    private $solutions;
    private $clue2solution;

    /* Hide names of not visited clue groups */
    protected $hide_names = true;

    /* URL of the screen that display clue groups */
    protected $cgrp_url = null;
    
    /* Flag indicating that unknown clue groups shall not be displayed as hyperlinks */
    protected $link_unknown_cgrps = false;


    /**
     *  Create the graph for a team
     */         
    function __construct($team_id){
        $this->team_id = $team_id;

        // fetch clue groups and solutions
        $opt = array("team_id" => $this->team_id);
        $this->cgroups = Iquest_ClueGrp::fetch($opt);
        $this->solutions = Iquest_Solution::fetch();

        // create clue => solution edges
        $this->load_clue2solution();
    }

    /**
     *  Set URL of the screen that display clue groups 
     */
    public function set_cgrp_url($url){
        $this->cgrp_url = $url;
    }

    /** 
     *  Set flag indicating whether unknown clue groups shall not be displayed as hyperlinks 
     */
    public function link_unknown_cgrps($val){
        $this->link_unknown_cgrps = $val;
    }

    /** 
     *  Hide names of not visited clue groups 
     */
    public function hide_names($val){
        $this->hide_names = $val;
    }

    protected function get_dot(){
        $out = "digraph G {\n";
//        $out .= "bgcolor=\"#cccccc\"\n";
        $out .= "bgcolor=\"transparent\"\n";
        $out .= "pad=0.3\n";
        $out .= "rankdir=LR\n";
        $out .= "nodesep=0.7\n";
        $out .= "ranksep=0.9\n";

        // generate graph edges

        $connected_cgroups = array();
        $edges = array();
        // walk through all clue groups
        foreach($this->cgroups as &$cgroup){
            // get clues of the group
            $clues = $cgroup->get_clues();
            foreach($clues as &$clue){
                if (empty($this->clue2solution[$clue->id])) continue;
                $solution_ids = $this->clue2solution[$clue->id];
                foreach($solution_ids as $solution_id){
                    if (empty($this->solutions[$solution_id])) continue;
                    $solution = $this->solutions[$solution_id];
                    if (empty($this->cgroups[$solution->cgrp_id])) continue;
                    $next_cgrp = $this->cgroups[$solution->cgrp_id];


                    $edges[] = self::escape_dot($cgroup->ref_id)." -> ".self::escape_dot($next_cgrp->ref_id).";\n";
                    $connected_cgroups[$cgroup->id] = true;
                    $connected_cgroups[$next_cgrp->id] = true;
                }
            }
        }

        // output graph nodes from clue groups
        $reveal_goal_cgrp_id = Iquest_Options::get(Iquest_Options::REVEAL_GOAL_CGRP_ID);

        foreach($this->cgroups as &$cgroup){
            // skip the "reveal the goal" cgrp if it is not connected by any edge
            if ($reveal_goal_cgrp_id == $cgroup->id and !isset($connected_cgroups[$cgroup->id])) continue;

            $out .= self::escape_dot($cgroup->ref_id)." ".$this->cgroup2dot($cgroup).";\n";
        }
        
        // output graph edges

        $edges = array_unique($edges);
        $out .= implode("", $edges);

        $out .= "}\n";

        return $out;
    }

    protected function cgroup2dot($cgroup){

        $cgrp_url = "";
        if ($this->cgrp_url){
            $cgrp_url = str_replace("<ID>", RawURLEncode($cgroup->ref_id), $this->cgrp_url);
        }

        $dot = "[";
        
        $dot .= "shape=circle,";
        $dot .= "width=0.3,";
        $dot .= "penwidth=3.0,";
        $dot .= "fixedsize=true,";
        $dot .= "style=filled,";
        $dot .= "color=black,";

        $xlabel = $cgroup->name;
        if ($cgroup->gained_at){
            $dot .= "fillcolor=white,";
        }
        else{
            $dot .= "fillcolor=grey,";
            if ($this->hide_names) $xlabel = "?";
            if (!$this->link_unknown_cgrps) $cgrp_url="";
        }

        if ($cgrp_url){
            $dot .= "URL=".self::escape_dot(htmlspecialchars($cgrp_url)).",";
            $dot .= "target=_parent,";
        }

        $dot .= "xlabel=".self::escape_dot($xlabel).",";
        $dot .= "label=\" \"";
        
        $dot .= "]";
        
        return $dot;
    }

    /**
     *  Load clue2solution linkings and create the clue=>solution graph edges
     */         
    private function load_clue2solution(){
        global $data, $config;

        /* table's name */
        $t_name = &$config->data_sql->iquest_clue2solution->table_name;
        /* col names */
        $c      = &$config->data_sql->iquest_clue2solution->cols;

        // fetch the whole clue2solution DB table
        $q = "select ".$c->clue_id.",
                     ".$c->solution_id."
              from ".$t_name;
    
        $res=$data->db->query($q);
        if ($data->dbIsError($res)) throw new DBException($res);

        $this->clue2solution = array();

        // walk through the rows
        while ($row=$res->fetchRow(MDB2_FETCHMODE_ASSOC)){
            $this->clue2solution[$row[$c->clue_id]][] = $row[$c->solution_id];
        }
        $res->free();
    } 
}
