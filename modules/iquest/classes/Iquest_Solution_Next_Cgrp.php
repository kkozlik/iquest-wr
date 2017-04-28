<?php

class Iquest_Solution_Next_Cgrp{

    public $cgrp_id;
    public $condition;

    function __construct($cgrp_id, $condition){
        
        $this->cgrp_id = $cgrp_id;
        $this->condition = $condition;
    }
    
}