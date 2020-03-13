<?php

class Iquest_Solution_Next_Cgrp{

    public $cgrp_id;

    /**
     * Condition specifing whether the clue group could be gained. If set, the condition
     * shall be in form: FUNCTION_NAME([PARAM, ... ])
     *
     * The only supported functions now are:
     *  - 'DEPENDS'. See Iquest_Condition::cond_depends() method.
     *  - 'TRUE'. See Iquest_Condition::cond_true() method.
     *  - 'FALSE'. See Iquest_Condition::cond_false() method.
     *
     * @var string
     */
    public $condition;

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

        return Iquest_Condition::evalueateCondition($this->condition, [
                                                        'team_id' => $team_id,
                                                        'current_solution' => $solution
                                                    ]);
    }

}