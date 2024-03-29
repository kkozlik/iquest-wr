<?php

class Traccar{
    const PATH_PREFIX =     "api/";

    protected $scheme = "http";
    protected $server_addr = "localhost";
    protected $server_port = 8082;
    protected $auth_token = null;

    public function __construct($options=[]){

        $opt_names = ['scheme', 'server_addr', 'server_port', 'auth_token'];

        foreach($opt_names as $opt){
            if (!empty($options[$opt])) $this->$opt = $options[$opt];
        }
    }

    private function auth(){
        global $lang_str;

        $curl = curl_init();

        $url = "$this->scheme://".$this->server_addr.":".$this->server_port."/".static::PATH_PREFIX."session";
        $url .= "?token=".rawurlencode($this->auth_token);

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "GET");
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

        $cookie_file = self::get_cookie_file();

        curl_setopt($curl, CURLOPT_COOKIEJAR, $cookie_file);
        curl_setopt($curl, CURLOPT_COOKIEFILE, $cookie_file);

        sw_log(__CLASS__."::".__FUNCTION__.": Executing API query: url:'$url'", PEAR_LOG_DEBUG);

        if (PHPlib::$session) PHPlib::$session->close_session();
        $output = curl_exec($curl);
        if (PHPlib::$session) PHPlib::$session->reopen_session();

        if (false === $output){
            $err = $lang_str['traccar_err_api_auth_error'].curl_error($curl);
            self::curl_close($curl);

            sw_log(__CLASS__.": Failed to query url: ".$url, PEAR_LOG_ERR);
            sw_log(__CLASS__.": ".$err, PEAR_LOG_ERR);
            throw new Traccar_api_query_exception($err);
        }
        else{
            $response_code = (int)curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
            if($response_code < 200 or $response_code > 299){

                $resp_code_err = "Response code is not 2xx but ".curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
                self::curl_close($curl);

                $resp = json_decode($output, true);
                if ($resp and isset($resp['error'])) $reason = $resp['error'];
                else                                 $reason = $resp_code_err;

                $err = $lang_str['traccar_err_api_auth_error'].$reason;

                sw_log(__CLASS__.": Failed to query url: ".$url, PEAR_LOG_ERR);
                sw_log(__CLASS__.": ".$resp_code_err, PEAR_LOG_ERR);
                sw_log(__CLASS__.": Response: ".$output, PEAR_LOG_ERR);

                throw new Traccar_api_query_exception($err);
            }
        }

        self::curl_close($curl);

        // If curl return just true, because the output has been redirected to a file,
        // return the true directly without json_decode
        if ($output === true) return true;
        return json_decode($output, true);
    }


    /**
     * Query traccar server and return the response
     *
     * Available options:
     *  - method      - method of the http query - default: GET
     *  - body        - data to be send in the http body (JSON encoded)
     *  - timeout     - specify maximum number of seconds to execute the query
     *
     * @param [type] $path
     * @param array $params
     * @param array $opts
     * @return mixed
     */
    public function query($path, $params=[], $opts=[]){
        // Try do the query. If unuathenticated response is received, try do the auth and re-run the query

        try{
            $out = $this->query_server($path, $params, $opts);
        }
        catch (Traccar_api_unauthenticated_exception $e){
            $this->auth();
            $out = $this->query_server($path, $params, $opts);
        }

        return $out;
    }

    private function query_server($path, $params=[], $opts=[]){
        global $lang_str;

        $curl = curl_init();

        $param_arr = array();
        foreach($params as $param_name => $param_value){
            $param_arr[] = $param_name."=".urlencode($param_value);
        }
        $query_string = implode("&", $param_arr);

        $url = "$this->scheme://".$this->server_addr.":".$this->server_port."/".static::PATH_PREFIX.$path;
        if ($query_string){
            $url .= "?".$query_string;
        }


        $method = "GET";
        if (!empty($opts['method'])) $method = $opts['method'];

        $headers = [];
        $body = "";

        if (!empty($opts['body'])) {
            $body = json_encode($opts['body']);
            $headers[] = 'Content-Type: application/json';
            $headers[] = 'Content-Length: '.strlen($body);
        }

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

        $cookie_file = self::get_cookie_file();

        curl_setopt($curl, CURLOPT_COOKIEJAR, $cookie_file);
        curl_setopt($curl, CURLOPT_COOKIEFILE, $cookie_file);

        if (!empty($opts['timeout'])){
            curl_setopt($curl, CURLOPT_TIMEOUT, $opts['timeout']);
        }

        if ($headers)   curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        if ($body)      curl_setopt($curl, CURLOPT_POSTFIELDS, $body);

        sw_log(__CLASS__."::".__FUNCTION__.": Executing API query: method:'$method', url:'$url', body:$body", PEAR_LOG_DEBUG);

        if (PHPlib::$session) PHPlib::$session->close_session();
        $output = curl_exec($curl);
        if (PHPlib::$session) PHPlib::$session->reopen_session();

        // CURLINFO_RESPONSE_CODE is defined since PHP 5.5
        if (!defined('CURLINFO_RESPONSE_CODE')) define('CURLINFO_RESPONSE_CODE', CURLINFO_HTTP_CODE);

        if (false === $output){
            $err = $lang_str['traccar_err_api_call_error'].curl_error($curl);
            self::curl_close($curl);

            sw_log(__CLASS__.": Failed to query url: ".$url, PEAR_LOG_ERR);
            sw_log(__CLASS__.": ".$err, PEAR_LOG_ERR);
            throw new Traccar_api_query_exception($err);
        }
        else{
            $response_code = (int)curl_getinfo($curl, CURLINFO_RESPONSE_CODE);

            if($response_code < 200 or $response_code > 299){

                self::curl_close($curl);

                if($response_code == 401) {
                    sw_log(__CLASS__."::".__FUNCTION__.": Query failed - unauthorized: $output", PEAR_LOG_DEBUG);
                    throw new Traccar_api_unauthenticated_exception("Traccar response: unauthorized");
                }

                if($response_code == 400) {
                    sw_log(__CLASS__."::".__FUNCTION__.": Query failed: $output", PEAR_LOG_DEBUG);
                    throw new Traccar_api_not_found_exception($output, $response_code);
                }

                $resp_code_err = "Response code is not 2xx but ".$response_code;
                $err = $lang_str['traccar_err_api_call_error'].$output;

                sw_log(__CLASS__.": Failed to query url: ".$url, PEAR_LOG_ERR);
                sw_log(__CLASS__.": ".$resp_code_err, PEAR_LOG_ERR);
                sw_log(__CLASS__.": Response: ".$output, PEAR_LOG_ERR);

                throw new Traccar_api_query_exception($err, $response_code);
            }
        }

        self::curl_close($curl);

        sw_log(__CLASS__."::".__FUNCTION__.": response: '$output'", PEAR_LOG_DEBUG);

        // If curl return just true, because the output has been redirected to a file,
        // return the true directly without json_decode
        if ($output === true) return true;
        return json_decode($output, true);
    }

    private static function get_cookie_file(){
        global $config;

        return $config->traccar_cookie_file."-".posix_getpwuid(posix_geteuid())['name'];
    }

    private static function curl_close($curl){

        curl_close($curl);

        if (file_exists(self::get_cookie_file())) chmod(self::get_cookie_file(), 0600);
    }

    public static function parse_time($val){
        // $val example: 2019-12-17T15:51:41.405+0000
        return DateTime::createFromFormat('Y-m-d\TH:i:s.uP', $val);
    }

    public function get_device($devId){
        static $devices = array();

        if (!empty($devices[$devId])) return $devices[$devId];

        $dev = Traccar_device::fetch($this, $devId);
        $devices[$devId] = $dev;

        return $devices[$devId];
    }

    public function insert_device(Traccar_device $device){
        return $device->insert($this);
    }

    public function update_device(Traccar_device $device){
        return $device->update($this);
    }

    /**
     * Get zones by device ID
     *
     * @param string $devId
     * @return array of Traccar_zone objects
     */
    public function get_zone_by_dev($devId){
        $dev = $this->get_device($devId);

        if (!$dev) throw new Traccar_api_query_exception("Cannot find device $devId");

        $zones = [];
        foreach($dev->geofenceIds as $zoneId){
            $zone = Traccar_zone::fetch($this, $zoneId);
            if ($zone) $zones[] = $zone;
        }

        return $zones;
    }

    public function get_zone_by_name($name){
        return Traccar_zone::fetch_by_name($this, $name);
    }

    public function get_group_by_name($name){
        return Traccar_group::fetch_by_name($this, $name);
    }

    public function add_zone_to_group($zone_id, $group_id){
        return Traccar_permission::add_zone_to_group($this, $zone_id, $group_id);
    }

    public function get_pos_by_dev($devId){
        $dev = $this->get_device($devId);

        if (!$dev){
            sw_log("Cannot get device ID='$devId'", PEAR_LOG_ERR);
            return null;
        }

        if (!$dev->positionId){
            sw_log("Position is not set for device '$devId'", PEAR_LOG_INFO);
            return null;
        }
        $pos = Traccar_position::fetch($this, $dev->positionId);

        return $pos;
    }

    public function update_zone(Traccar_zone $zone){
        return $zone->update($this);
    }

}

