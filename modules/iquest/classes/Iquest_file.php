<?php

/**
 *  Common class for clues, hints and solutions
 *  Contain functions for manipulate the files. 
 */ 
class Iquest_file{
    public $id;
    public $ref_id;
    public $filename;
    public $content_type;
    public $comment;
    public $content = null;

    /**
     *  Instantiate obj by ref_id
     */         
    static function &by_ref_id($ref_id){
        $objs = static::fetch(array("ref_id"=>$ref_id));

        if (!$objs) {
            $null = null;
            return $null;   // reference has to be returned
        } 
        
        $obj = reset($objs);
        return $obj;
    }
    
    static function get_mime_type($filename){
        $ext = substr($filename, strrpos($filename, ".")+1);
        
        switch (strtolower($ext)){
        case "txt":     return "text/plain";
        case "html":    return "text/html";
        case "jpeg":
        case "jpg":     return "image/jpeg";
        case "png":     return "image/png";
        case "gif":     return "image/gif";
        case "mp3":     return "audio/mpeg";
        case "wav":     return "audio/wav";
        case "avi":     return "video/x-msvideo";
        case "mp4":     return "video/mp4";
        default:        return "application/octet-string";
        }
        
    }
    
    function __construct($id, $ref_id, $filename, $content_type, $comment){
        $this->id =             $id;
        $this->ref_id =         $ref_id;
        $this->filename =       $filename;
        $this->content_type =   strtolower($content_type);
        $this->comment =        $comment;
    }

    /**
     *  Get content of the file
     */         
    function get_content(){
        global $config;
        
        if (!is_null($this->content)) return $this->content;
    
        $filename = $config->iquest_data_dir.$this->filename;
        $content = file_get_contents($filename);
        
        if (false === $content){
            throw new RuntimeException("Can not read file: ".$filename);
        }
        
        $this->content = $content;
        
        return $this->content;
    }

    /**
     *  Flush content of the file for download
     */         
    function flush_content(){
        global $config;
        
        $filename = $config->iquest_data_dir.$this->filename;

        $offset = 60 * 60 * 24;

        header('Content-Description: File Transfer');
        header('Content-Type: '.$this->content_type);
        header('Content-Disposition: attachment; filename='.basename($filename));
        header('Content-Transfer-Encoding: binary');
        header("Expires: " . gmdate("D, d M Y H:i:s", time() + $offset) . " GMT");
        header("Cache-Control: max-age=$offset, must-revalidate");
        header('Pragma: public');
        header('Content-Length: ' . filesize($filename));
        ob_clean();
        flush();

        $success = readfile($filename);
       
        if (false === $success){
            throw new RuntimeException("Can not read file: ".$filename);
        }
    }

    /**
     *  Determine whether the content could be directly shown in the HTML code.
     *  
     *  So far only text files could be included.          
     */         
    function is_directly_shown(){
        $type_parts = explode("/", $this->content_type, 2);
    
        if ($type_parts[0]=="text") return true;
        return false;
    }


    function to_smarty($opt = array()){
        $out = array();
        $out['id'] = $this->id;
        $out['ref_id'] = $this->ref_id;
        $out['filename'] = basename($this->filename);
        $out['content_type'] = $this->content_type;
        $out['comment'] = $this->comment;
        $out['content'] = null;

        if ($this->is_directly_shown()){
            $out['content'] = $this->get_content();
        }

        return $out;
    }
}

