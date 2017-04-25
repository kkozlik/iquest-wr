<?php

abstract class Iquest_graph_abstract{

    public static function escape_dot($str){
        return '"'.str_replace('"', '\"', $str).'"';
    }

    /**
     *  Visualize the graph using graphviz
     */         
    public function image_graph(){
        global $config;

        // prepare specification of file descriptors
        $descriptorspec = array(
           0 => array("pipe", "r"),  // stdin is a pipe that the child will read from
           1 => array("pipe", "w"),  // stdout is a pipe that the child will write to
           2 => array("file", "/dev/null", "a")                 // no stderr 
        );

        // execute the graphviz command
        $cmd = $config->iquest->graphviz_cmd." -Tsvg";
        $process = proc_open($cmd, $descriptorspec, $pipes);

        if (!is_resource($process)) {
            throw new RuntimeException("Failed to execute graphviz!");
        }

        // $pipes now looks like this:
        // 0 => writeable handle connected to child stdin
        // 1 => readable handle connected to child stdout
    
        // Write DOT representation of the graph to stdin of the graphviz
        fwrite($pipes[0], $this->get_dot()); 
        fclose($pipes[0]);
    
        // read the image data
        $image_data = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
    
        // It is important that you close any pipes before calling
        // proc_close in order to avoid a deadlock
        $return_value = proc_close($process);
    
        // Return image to the browser
        header('Content-Description: File Transfer');
        header('Content-type: image/svg+xml');
//      header('Content-Disposition: attachment; filename="graph.png"');
        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . strlen($image_data));

        echo $image_data;
    }

    /**
     *  Generate graph representation in DOT language (for graphviz)
     */         
    abstract protected function get_dot();
}
