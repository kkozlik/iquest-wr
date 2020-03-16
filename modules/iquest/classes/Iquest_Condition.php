<?php

class Iquest_Condition_exception extends Exception {};

class Iquest_Condition{

    /**
     * Prefix of methods implementing conditions
     */
    const COND_PREFIX = "cond_";

    /**
     * Evaluate a condition
     *
     *  Allowed options:
     *   - team_id              - ID of tham the condition is evaluated for
     *   - current_solution     - Instance of Iquest_Solution - When a solution is being solved it is stored to this option
     *
     * @param string    $condition
     * @param array     $options
     * @return bool
     */
    public static function evalueateCondition($condition, $options){

        if (empty($condition)) return true;

        $parsed_condition = static::parseCondition($condition);

        try{
            return call_user_func(array(get_called_class(),
                                        static::COND_PREFIX.$parsed_condition['function']),
                                $parsed_condition['params'],
                                $options);
        }
        catch(Iquest_Condition_exception $e){
            sw_log(__CLASS__.":".__FUNCTION__.": Error while evalueating condition '$condition'. Is the condition defined on proper place?", PEAR_LOG_ERROR);
            sw_log_exception($e, PEAR_LOG_ERROR);
            return false;
        }
    }


    /**
     * Parse the $condition in text format and return it as
     * array: {function: xxx, params: yyy}
     *
     * Where function shall be name of a cond_* method of this class
     *
     * @return array
     */
    public static function parseCondition($condition){

        // Get function name and params from $this->condition string
        if (!preg_match('/^(?P<function>[a-z0-9_]+) *\\((?P<params>.*)\\)$/i', $condition, $matches)){
            throw new Exception("Invalid condtition expression");
        }

        // convert params to array
        $params = explode(",", $matches['params']);
        $params = array_map("trim", $params);

        // check whether function exists
        $funct = $matches['function'];
        if (!method_exists(get_called_class(), static::COND_PREFIX.$funct)){
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
     *  Allowed options:
     *   - team_id (required)        - The team solving the solution
     *   - current_solution          - Instance of Iquest_Solution - The solution just being solved
     *
     * @param array $params     Params of the DEPENDS(...) condition. It should be array of solution IDs
     * @param array $options
     * @return bool
     */
    private static function cond_depends($params, $options){

        sw_log(__CLASS__.":".__FUNCTION__.": params:".json_encode($params), PEAR_LOG_DEBUG);

        if (!isset($options['team_id'])){
            throw new Iquest_Condition_exception("'team_id' option is not set for the condition");
        }

        $opt = array("team_id" => $options['team_id']);
        $solutions = Iquest_Solution::fetch($opt);

        foreach($params as $solution_id){
            // if solution that is being solved just now is part of the depends list, just skip it
            if (isset($options['current_solution']) and $solution_id == $options['current_solution']->id) continue;

            if (!isset($solutions[$solution_id])){
                throw new Iquest_Condition_exception("Unknown solution '$solution_id'");
            }

            if (!$solutions[$solution_id]->is_solved($options['team_id'])){
                sw_log(__CLASS__.":".__FUNCTION__.": solution '$solution_id' is not solved yet. Evaluating condition as false.", PEAR_LOG_INFO);
                return false;
            }
        }

        sw_log(__CLASS__.":".__FUNCTION__.": All solutions '".implode("', '", $params)."' are solved. Evaluating condition as true.", PEAR_LOG_INFO);
        return true;
    }

    /**
     * Implementation of TRUE() condition. It always return true.
     * Use of this condition have same behavior as if not condition is used.
     * But it has a side effect: It draw the arrow in dashed style in the contest graph.
     *
     * @param array $params
     * @param array $options
     * @return bool
     */
    private static function cond_true($params, $options){

        sw_log(__CLASS__.":".__FUNCTION__.": params:".json_encode($params), PEAR_LOG_DEBUG);
        return true;
    }

    /**
     * Implementation of FALSE() condition. It always return false
     *
     * @param array $params
     * @param array $options
     * @return bool
     */
    private static function cond_false($params, $options){

        sw_log(__CLASS__.":".__FUNCTION__.": params:".json_encode($params), PEAR_LOG_DEBUG);
        return false;
    }

}