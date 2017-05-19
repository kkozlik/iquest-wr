<?php

class Iquest_Solution_Next_Cgrp{

    public $cgrp_id;

    /**
     * Condition specifing whether the clue group could be gained. If set, the condition
     * shall be in form: FUNCTION_NAME([PARAM, ... ])
     * 
     * The only supported function now is 'DEPENDS'. See cond_depends() method.
     *
     * @var string
     */
    public $condition;

    /**
     * Prefix of methods implementing conditions
     */
    const COND_PREFIX = "cond_";

    function __construct($cgrp_id, $condition){
        
        $this->cgrp_id = $cgrp_id;
        $this->condition = $condition;
    }
    
    /**
     * Return true if this next clue group is gained conditionally
     *
     * @return bool
     */
    public function isConditional(){
        return !empty($this->condition);
    }

    /**
     * Evaluate condition whether next clue group could be shown
     *
     * @param Iquest_Solution $solution
     * @param int $team_id
     * @return bool
     */
    public function evalueateCondition(Iquest_Solution $solution, $team_id){

        if (!$this->isConditional()) return true;

        $condition = $this->parseCondition();

        return call_user_func(array($this, self::COND_PREFIX.$condition['function']), $solution, $team_id, $condition['params']);
    }

    /**
     * Parse condition stored as $this->condition in text format and return it as
     * array: {function: xxx, params: yyy}
     * 
     * Where function shall shall be name of a cond_* method of this class
     *
     * @return array
     */
    public function parseCondition(){

        // Get function name and params from $this->condition string
        if (!preg_match('/^(?P<function>[a-z0-9_]+) *\\((?P<params>.*)\\)$/i', $this->condition, $matches)){
            throw new Exception("Invalid condtition expression");
        }

        // convert params to array
        $params = explode(",", $matches['params']);
        $params = array_map("trim", $params);

        // check whether function exists
        $funct = $matches['function'];
        if (!method_exists($this, self::COND_PREFIX.$funct)){
            throw new Exception("Unknown condition function '$funct'");
        }

        $parsedCondition = array("function" => $funct,
                                 "params" => $params);

        sw_log(__CLASS__.":".__FUNCTION__.": parsed condition:".json_encode($parsedCondition), PEAR_LOG_DEBUG);

        return $parsedCondition;
    }


    /**
     * Implementation of DEPENDS(...) condition. It should return true if all solutions
     * specified as params are solved.
     *
     * @param Iquest_Solution $current_solution     The solution just being solved
     * @param int $team_id                          The team solving the solution
     * @param array $params                         Params of the DEPENDS(...) condition. It should be array of solution IDs
     * @return bool
     */
    private function cond_depends(Iquest_Solution $current_solution, $team_id, $params){

        sw_log(__CLASS__.":".__FUNCTION__.": params:".json_encode($params), PEAR_LOG_DEBUG);

        $opt = array("team_id" => $team_id);
        $solutions = Iquest_Solution::fetch($opt);

        foreach($params as $solution_id){
            // if solution that is beeing solved just now is part of the depends list, just skip it
            if ($solution_id == $current_solution->id) continue;

            if (!isset($solutions[$solution_id])){
                sw_log(__CLASS__.":".__FUNCTION__.": unknown solution '$solution_id'. Evaluating condition as false.", PEAR_LOG_ERROR);
                return false;
            }

            if (!$solutions[$solution_id]->is_solved($team_id)){
                sw_log(__CLASS__.":".__FUNCTION__.": solution '$solution_id' is not solved yet. Evaluating condition as false.", PEAR_LOG_INFO);
                return false;
            }
        }

        sw_log(__CLASS__.":".__FUNCTION__.": All solutions '".implode("', '", $params)."' are solved. Evaluating condition as true.", PEAR_LOG_INFO);
        return true;
    }

}