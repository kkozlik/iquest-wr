<?php
require_once("Iquest_graph_abstract.php");

/**
 *  Class holding graph of clue/solution dependencies 
 */ 
class Iquest_solution_graph extends Iquest_graph_abstract{
    private $team_id;
    private $cgroups;
    private $solutions;
    private $nodes = array();
    private $edges = array();
    private $reverse_edges = array();


    /**
     *  Create the graph for a team
     */         
    function __construct($team_id){
        $this->team_id = $team_id;

        // fetch clue groups and solutions
        $opt = array("team_id" => $this->team_id);
        $this->cgroups = Iquest_ClueGrp::fetch($opt);
        $this->cgrp_open = Iquest_ClueGrp::fetch_cgrp_open($opt);
        $this->solutions = Iquest_Solution::fetch();

        // create clue => solution edges
        $this->load_clue2solution();

        // walk through all solutions
        foreach($this->solutions as &$solution){

            // create nodes for task solutions
            $this->nodes["S_".$solution->id] = 
                new Iquest_solution_graph_node(Iquest_solution_graph_node::TYPE_SOLUTION, $solution);


            $next_cgrp_ids = $solution->get_next_cgrp_ids();
            foreach($next_cgrp_ids as $next_cgrp_id){
                // if there is a clue group that is gained by solving a task solution
                if (isset($this->cgroups[$next_cgrp_id])){
                    $cgrp = &$this->cgroups[$next_cgrp_id];

                    // fetch all clues and create the solution => clue edges
                    $clues = $cgrp->get_clues();
                    foreach($clues as &$clue){
                        if (!isset($this->edges["S_".$solution->id])) $this->edges["S_".$solution->id] = array();
                        $this->edges["S_".$solution->id][] = "C_".$clue->id;
                        
                        if (!isset($this->reverse_edges["C_".$clue->id])) $this->reverse_edges["C_".$clue->id] = array();
                        $this->reverse_edges["C_".$clue->id][] = "S_".$solution->id;
                    }
                }
            }

// TODO: Need to set $this->nodes["S_".$solution->id]->solved flag if the solution is solved
//       Need to figure out how to determine whether a solution is solved

            // // if there is a clue group that is gained by solving a task solution
            // if (isset($this->cgroups[$solution->cgrp_id])){
            //     $cgrp = &$this->cgroups[$solution->cgrp_id];

            //     // if team has gained the clue group, mark the solution as solved
            //     if ($cgrp->gained_at){
            //         $this->nodes["S_".$solution->id]->solved = true;
            //     }

            //     // fetch all clues and create the solution => clue edges
            //     $clues = $cgrp->get_clues();
            //     foreach($clues as &$clue){
            //         if (!isset($this->edges["S_".$solution->id])) $this->edges["S_".$solution->id] = array();
            //         $this->edges["S_".$solution->id][] = "C_".$clue->id;
                    
            //         if (!isset($this->reverse_edges["C_".$clue->id])) $this->reverse_edges["C_".$clue->id] = array();
            //         $this->reverse_edges["C_".$clue->id][] = "S_".$solution->id;
            //     }
            // }

            // // For dead-end waypoints there is no real clue group defined. 
            // // So check directly the open_cgrp table so we know whether it is solved. 
            // elseif (isset($this->cgrp_open[$solution->cgrp_id])){
            //     $this->nodes["S_".$solution->id]->solved = true;
            // }
        }

        // walk through all clue groups
        foreach($this->cgroups as &$cgroup){
            // get clues of the group
            $clues = $cgroup->get_clues();
            foreach($clues as &$clue){
                // create graph nodes for the clues
                $this->nodes["C_".$clue->id] = 
                    new Iquest_solution_graph_node(Iquest_solution_graph_node::TYPE_CLUE, $clue);
                    
                // if team has gained the clue group, mark the clue as gained
                if ($cgroup->gained_at){
                    $this->nodes["C_".$clue->id]->gained = true;
                }
            }
        }
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

        // walk through the rows
        while ($row=$res->fetchRow(MDB2_FETCHMODE_ASSOC)){
            // and create the clue=>solution edges
            if (!isset($this->edges["C_".$row[$c->clue_id]])) $this->edges["C_".$row[$c->clue_id]] = array();
            $this->edges["C_".$row[$c->clue_id]][] = "S_".$row[$c->solution_id];

            // create the reversed edges as well
            if (!isset($this->reverse_edges["S_".$row[$c->solution_id]])) $this->reverse_edges["S_".$row[$c->solution_id]] = array();
            $this->reverse_edges["S_".$row[$c->solution_id]][] = "C_".$row[$c->clue_id];
        }
        $res->free();
    } 


    /**
     *  Mark all nodes from which the final task could be reached without
     *  meeting already solved tasks in the way.
     *            
     *  It is done by walking the graph in reversed order from the final task.
     *  
     *  If the $include_coin_waypoints is set to TRUE, include also nodes
     *  from which any waypoint valuated by coins could be reached.               
     */         
    protected function mark_accessible_nodes($include_coin_waypoints){

        // reset all nodes visited flag
        foreach($this->nodes as &$node) $node->visited = false;

        $queue = array();

        $final_cgrp_id = Iquest_Options::get(Iquest_Options::FINAL_CGRP_ID);

        if (!isset($this->cgroups[$final_cgrp_id])){
            throw new UnexpectedValueException("Invalid ID of final clue group: '$final_cgrp_id'");
        }

        $clues = $this->cgroups[$final_cgrp_id]->get_clues();

        if (!$clues){
            throw new UnexpectedValueException("Final clue group: '$final_cgrp_id' does not contain any clues");
        }

        //add nodes of final clue group to the queue
        foreach($clues as &$clue) $queue[] = "C_".$clue->id; 


        sw_log("mark_accessible_nodes: queue: ".json_encode($queue), PEAR_LOG_DEBUG);

        // if we should include also nodes accessible from waypoints with coins
        if ($include_coin_waypoints){

            // get the solutions that gain some coins
            $opt = array();
            $opt['filter']['coin_value'] = new Filter("coin_value", 0, ">");
            $solutions = Iquest_Solution::fetch($opt);
            
            // add the related nodes to the queue too
            foreach($solutions as $solution){
                if (!isset($this->nodes["S_".$solution->id])){
                    throw new UnexpectedValueException("Node for solution ID='{$solution->id}' does not exists in the graph.");
                }
                
                // but only if they are not solved yet
                if ($this->nodes["S_".$solution->id]->solved) continue;
                
                //add node to the queue
                $queue[] = "S_".$solution->id;
            }
        }

        sw_log("mark_accessible_nodes: queue2: ".json_encode($queue), PEAR_LOG_DEBUG);
        
        // as long as there are nodes in in the queue, fetch node from the queue...
        while(!is_null($node_id = array_shift($queue))){
            // and set it's visited flag to true
            $this->nodes[$node_id]->visited = true;

            sw_log("mark_accessible_nodes: visited node: $node_id", PEAR_LOG_DEBUG);

            // We are walking the graph in reversed order. If there are any
            // edges leading TO this node walk through them. Get all nodes
            // FROM what leads edge TO current node 
            if (isset($this->reverse_edges[$node_id])){
                foreach($this->reverse_edges[$node_id] as $from_node_id){

                    // if the node has been already visited, skip it
                    if ($this->nodes[$from_node_id]->visited) continue;

                    // if the node is task solution that is solved, skip it
                    if ($this->nodes[$from_node_id]->is_solution() and
                        $this->nodes[$from_node_id]->solved)  continue;

                    // add node to queue
                    $queue[] = $from_node_id;
                    sw_log("mark_accessible_nodes: adding node: $from_node_id", PEAR_LOG_DEBUG);
                }
            }
            sw_log("mark_accessible_nodes: queue3: ".json_encode($queue), PEAR_LOG_DEBUG);
        }
    
    }

    /**
     *  Check whether graph is continuous. I.e. whether all nodes could be 
     *  accessed from initial clue group.
     */         
    public function check_graph_continuous(){

        // reset all nodes visited flag
        foreach($this->nodes as &$node) $node->visited = false;

        $queue = array();

        $initial_cgrp_id = Iquest_Options::get(Iquest_Options::INITIAL_CGRP_ID);

        if (!isset($this->cgroups[$initial_cgrp_id])){
            throw new UnexpectedValueException("Invalid ID of initial clue group: '$initial_cgrp_id'");
        }

        $clues = $this->cgroups[$initial_cgrp_id]->get_clues();

        if (!$clues){
            throw new UnexpectedValueException("Initial clue group: '$initial_cgrp_id' does not contain any clues");
        }

        //add nodes of initial clue group to the queue
        foreach($clues as &$clue) $queue[] = "C_".$clue->id; 

        sw_log("mark_accessible_nodes: queue: ".json_encode($queue), PEAR_LOG_DEBUG);

        // as long as there are nodes in in the queue, fetch node from the queue...
        while(!is_null($node_id = array_shift($queue))){
            // and set it's visited flag to true
            $this->nodes[$node_id]->visited = true;

            sw_log("mark_accessible_nodes: visited node: $node_id", PEAR_LOG_DEBUG);

            // We are walking the graph in reversed order. If there are any
            // edges leading TO this node walk through them. Get all nodes
            // FROM what leads edge TO current node 
            if (isset($this->edges[$node_id])){
                foreach($this->edges[$node_id] as $to_node_id){

                    // if the node has been already visited, skip it
                    if ($this->nodes[$to_node_id]->visited) continue;

                    // add node to queue
                    $queue[] = $to_node_id;
                    sw_log("mark_accessible_nodes: adding node: $to_node_id", PEAR_LOG_DEBUG);
                }
            }
            sw_log("mark_accessible_nodes: queue3: ".json_encode($queue), PEAR_LOG_DEBUG);
        }


        $reveal_goal_cgrp_id = Iquest_Options::get(Iquest_Options::REVEAL_GOAL_CGRP_ID);
        if (!isset($this->cgroups[$reveal_goal_cgrp_id])){
            throw new UnexpectedValueException("Invalid ID of reveal goal clue group: '$reveal_goal_cgrp_id'");
        }


        $unaccessible_cgrps = array();
        $unaccessible_solutions = array();

        foreach($this->nodes as &$node){
            if (!$node->visited) {
                if ($node->is_clue() and 
                    $node->get_obj()->cgrp_id==$reveal_goal_cgrp_id){
                    
                    // It is OK that clue group revaling goal is not accessible.
                    // Skip it.
                    continue;
                } 

                if ($node->is_clue())       $unaccessible_cgrps[]     = $node->get_obj()->cgrp_id;
                if ($node->is_solution())   $unaccessible_solutions[] = $node->get_obj()->id;
            }
        }
    
        $err = "";
        if ($unaccessible_cgrps){
            $err .= "Clue groups: ".
                    implode(", ", array_unique($unaccessible_cgrps)).
                    " are not accessible from the start clue group.\n";
        }

        if ($unaccessible_solutions){
            $err .= "Solutions: ".
                    implode(", ", array_unique($unaccessible_solutions)).
                    " are not accessible from the start clue group.\n";
        }

        return $err;
    }

    /**
     *  Return list of IDs of clues that has been already gained by the team,
     *  but that are not needed anymore (has been used to solve a task solution).
     *  
     *  It is done by walking the graph in reversed order from the final task.
     *  All clues that are reachable from the final task or from any waypoint
     *  valuated by coins (not meeting a solved task) are still needed.                         
     */         
    public function get_unneded_clues(){

        $this->mark_accessible_nodes(true);

        // create the list of unneded clues, walk through all nodes
        $unneded_clues = array();
        foreach($this->nodes as &$node){
            // if the node is not clue skip it
            if (!$node->is_clue()) continue;
            // if the node has been visited, the clue is still needed. Skip it
            if ($node->visited) continue;
            // If the clue has not been gained yet, it do not belong to our scope. Skip it.
            if (!$node->gained) continue;

            // All the rest of nodes should be added to the array
            $unneded_clues[] = $node->get_obj()->id;
        }

        return  $unneded_clues;
    }


    /**
     *  Return list of IDs of task solutions that are not needed anymore 
     *  (Either has been already solved or solving them do not help with
     *  reaching final task).
     *  
     *  It is done by walking the graph in reversed order from the final task.
     *  All solutions that are rachable from the final task (not meeting a solved task)
     *  are still needed.                         
     */         
    public function get_unneded_solutions(){

        $this->mark_accessible_nodes(false);

        // create the list of unneded solutions, walk through all nodes
        $unneded_solutions = array();
        foreach($this->nodes as &$node){
            // if the node is not solutions skip it
            if (!$node->is_solution()) continue;
            // if the node has been visited, the solution is still needed. Skip it
            if ($node->visited) continue;

            // All the rest of nodes should be added to the array
            $unneded_solutions[] = $node->get_obj()->id;
        }

        return  $unneded_solutions;
    }


    /**
     *  Get distance to finish of the contest
     *  
     *  It is done by walking the graph in reversed order from the final task.
     *  All nodes that are rachable from the final task (not meeting a solved task)
     *  are counted to the distance.
     */         
    public function get_distance_to_finish(){

        $this->mark_accessible_nodes(false);

        $dist = 0;
        $cgrps_visited = array();
        foreach($this->nodes as &$node){
            // do not count nodes that are not visited
            if (!$node->visited) continue;

            if ($node->is_clue()){
                // Whole clue-group should be counted only once. 
                // Therefore the $dist should not be incremented on every clue.
                // Visited clue groups are counted in separate array
                $clue = $node->get_obj();
                $cgrps_visited[$clue->cgrp_id] = true;
                continue;
            }
            
            // node is a solution
            $dist++;
        }
        
        // add the number of visited clue groups
        $dist += count($cgrps_visited);
        
        return $dist;
    }


    /**
     *  Get solutions that are not solved yet, but the team has at least one clue
     *  to the solution.     
     */         
    public function get_active_solutions(){
        $out = array();

        // walk throught all graph nodes
        foreach($this->nodes as &$node){
            // skip those nodes that are not solutions ot that are already solved
            if (!$node->is_solution()) continue;
            if ($node->solved) continue;

            // walk through all nodes that points to current node
            $node_id = $node->get_node_id();
            if (isset($this->reverse_edges[$node_id])){
                foreach($this->reverse_edges[$node_id] as $node2_id){
                    // If the node is not clue or if it is not gained yet, skip it
                    $node2 = $this->nodes[$node2_id];
                    if (!$node2->is_clue()) continue;
                    if (!$node2->gained) continue;
                    
                    // add the solution to the output
                    $out[] = $node->get_obj();
                }
            }
        }
        
        return $out;
    }


    /**
     *  Generate graph representation in DOT language (for graphviz)
     */         
    protected function get_dot(){
        $out = "digraph G {\n";

        foreach($this->nodes as $k => $node){
            $out .= self::escape_dot($k)." ".$node->to_dot().";\n";
        }

        foreach($this->edges as $k1 => $v1){
            foreach($this->edges[$k1] as $v2){
                $out .= self::escape_dot($k1)." -> ".self::escape_dot($v2).";\n";
            }
        }

        $out .= "}\n";

        return $out;
    }
}
