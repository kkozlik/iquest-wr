<?php

/**
 *  Node of the Iquest_solution_graph graph
 */
class Iquest_solution_graph_node{
    const TYPE_CLUE = "clue";
    const TYPE_SOLUTION = "solution";

    private $obj;
    private $type;
    // flag indicating the node has been visited
    public $visited = false;

    // attributes for type=solution
    public $solved = false;

    // attributes  for type=clue
    public $gained = false;


    function __construct($type, &$obj){
        $this->type = $type;
        $this->obj = &$obj;
    }

    function is_solution(){
        return ($this->type==self::TYPE_SOLUTION);
    }

    function is_clue(){
        return ($this->type==self::TYPE_CLUE);
    }

    function get_obj(){
        return $this->obj;
    }

    function get_node_id(){

        if ($this->is_solution()) return "S_".$this->obj->id;
        if ($this->is_clue())     return "C_".$this->obj->id;

        throw new UnexpectedValueException("Unknown type of graph node");
    }

    /**
     *  Return representation of the node in dot language
     */
    public function to_dot(){
        $dot = "[";

        if ($this->type == self::TYPE_SOLUTION){
            $dot .= "shape=box";
            $dot .=  $this->solved ? ",color=darkolivegreen1,style=filled" : "";
        }
        else{
            $start_cgrp_ids = Iquest_Options::get(Iquest_Options::INITIAL_CGRP_IDS);
            $final_cgrp_id = Iquest_Options::get(Iquest_Options::FINAL_CGRP_ID);
            $giveitup_cgrp_id = Iquest_Options::get(Iquest_Options::REVEAL_GOAL_CGRP_ID);


            if ($this->obj->cgrp_id == $giveitup_cgrp_id){
                $dot .= "shape=octagon";
                $dot .= ",style=filled";
                $dot .= ",color=cyan";
            }
            else{
                if (in_array($this->obj->cgrp_id, $start_cgrp_ids)){
                    $dot .= "shape=doublecircle";
                }
                elseif ($this->obj->cgrp_id == $final_cgrp_id){
                    $dot .= "shape=doubleoctagon";
                }
                else{
                    $dot .= "shape=ellipse";
                }

                $dot .= ",style=filled";
                $dot .= $this->gained ? ",color=chartreuse3" : ",color=red";
            }

        }

        if ($this->visited){
            $dot .= ",label=<<FONT color=\"#990000\">Visited: </FONT>\"".$this->obj->id."\">";
        }
        else{
            $dot .= ",label=\"".$this->obj->id."\"";
        }

        $dot .= "]";
        return $dot;
    }

}
