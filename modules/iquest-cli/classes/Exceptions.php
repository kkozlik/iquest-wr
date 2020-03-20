<?php

class Iquest_MetadataOpenException extends Exception {}
class Iquest_noMetaDataException extends Iquest_MetadataOpenException {}

class Iquest_InvalidConfigException extends Exception {}
class Iquest_ConfigDirOpenException extends Iquest_InvalidConfigException {}

class Iquest_VerifyFailedException extends Iquest_InvalidConfigException {
    const SOLUTION_IDS = "Solution IDs";
    const CLUE_IDS = "Clue IDs";
    const CLUE_GRP_IDS = "Clue Group IDs";

    private $gathered_info = array();

    public function __construct($message, $gather_info=array()){
        parent::__construct($message);

        foreach($gather_info as $info_type){
            $this->gather_info($info_type);
        }
    }

    private function gather_info($info_type){
        $data = array();

        switch($info_type){
        case self::SOLUTION_IDS:
            $solutions = Iquest_Solution::fetch();
            foreach($solutions as $solution) $data[]=$solution->id;
            break;

        case self::CLUE_IDS:
            $clues = Iquest_Clue::fetch();
            foreach($clues as $clue) $data[]=$clue->id;
            break;

        case self::CLUE_GRP_IDS:
            $cluegprs = Iquest_ClueGrp::fetch();
            foreach($cluegprs as $cluegrp) $data[]=$cluegrp->id;
            break;

        default:
            throw new Exception(__CLASS__.":".__FUNCTION__." - Invalid value for info_type '$info_type'");
        }

        $this->gathered_info[$info_type]=$data;
    }


    public function get_info(){
        $str = "";
        foreach($this->gathered_info as $label => $data){
            sort($data);
            $str .= "$label\n";
            $str .= str_repeat("=", strlen($label))."\n";
            $str .= implode(", ", $data)."\n\n";
        }
        return $str;
    }
}
