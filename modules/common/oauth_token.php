<?php

/**
 * OAuth token stored in storage
 */
class Iquest_auth_oauth_token{
    private $code;
    private $expire;
    private $jwt;

    /**
     * Create the JWT token
     *
     * @return void
     * @throws Iquest_auth_jwt_exception
     */
    public function init(){
        $this->code = bin2hex(random_bytes(15));

        // default expiration in 5 minutes
        $this->expire = time() + 300;
        $this->jwt = Iquest_auth::get_jwt();
    }

    public function get_code() : string {
        return $this->code;
    }

    public function get_jwt() : string {
        return $this->jwt;
    }

    public function is_valid() : bool {
        return ($this->expire >= time());
    }

    public function to_store_obj() : array{
        $out = [];

        $out['expire'] = $this->expire;
        $out['jwt'] = $this->jwt;

        return $out;
    }

    public static function from_store_obj(string $code, array $store_obj) : Iquest_auth_oauth_token{
        $token = new self();

        $token->code = $code;
        $token->expire = $store_obj['expire'];
        $token->jwt = $store_obj['jwt'];

        return $token;
    }


    /**
     *  Instantiate auth token by code
     */
    static function by_code($code){

        $objs = static::fetch(array("code"=>$code));
        if (!$objs) return null;

        $obj = reset($objs);
        return $obj;
    }

    /**
     *  Fetch auth tokens form DB
     */
    private static function fetch($opt=array()){
        global $data, $config;

        /* table's name */
        $t_name  = &$config->data_sql->iquest_auth_tokens->table_name;
        /* col names */
        $c       = &$config->data_sql->iquest_auth_tokens->cols;

        $qw = array();
        if (isset($opt['code'])) $qw[] = "c.".$c->code." = ".$data->sql_format($opt['code'], "s");

        if ($qw) $qw = " where ".implode(' and ', $qw);
        else $qw = "";

        $q = "select c.".$c->code.",
                     UNIX_TIMESTAMP(c.".$c->expire.") as {$c->expire},
                     c.".$c->jwt."
              from ".$t_name." c ".
              $qw;

        $res=$data->db->query($q);
        $res->setFetchMode(PDO::FETCH_ASSOC);

        $out = array();
        while ($row=$res->fetch()){
            $out[$row[$c->code]] = new Iquest_auth_oauth_token();
            $out[$row[$c->code]]->code   = $row[$c->code];
            $out[$row[$c->code]]->expire = $row[$c->expire];
            $out[$row[$c->code]]->jwt    = $row[$c->jwt];
        }
        $res->closeCursor();
        return $out;
    }

    public function insert(){
        global $data, $config;

        /* table's name */
        $t_name  = &$config->data_sql->iquest_auth_tokens->table_name;
        /* col names */
        $c      = &$config->data_sql->iquest_auth_tokens->cols;

        $q = "insert into ".$t_name."(
                    ".$c->code.",
                    ".$c->expire.",
                    ".$c->jwt."
              )
              values(
                    ".$data->sql_format($this->code,                 "s").",
                    FROM_UNIXTIME(".$data->sql_format($this->expire, "n")."),
                    ".$data->sql_format($this->jwt,                  "s")."
              )";

        $data->db->query($q);
    }

    public function delete(){
        global $data, $config;

        /* table's name */
        $t_name  = &$config->data_sql->iquest_auth_tokens->table_name;
        /* col names */
        $c      = &$config->data_sql->iquest_auth_tokens->cols;

        $q = "delete from ".$t_name." where ".$c->code." = ".$data->sql_format($this->code, "s");
        $data->db->query($q);
    }

    public static function gc(){
        global $data, $config;

        /* table's name */
        $t_name  = &$config->data_sql->iquest_auth_tokens->table_name;
        /* col names */
        $c      = &$config->data_sql->iquest_auth_tokens->cols;

        $q = "delete from ".$t_name." where ".$c->expire." < now()";
        $data->db->query($q);
    }
}