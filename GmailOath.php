<?php

class GmailOath {

    public $oauth_consumer_key;
    public $oauth_consumer_secret;
    public $debug;
    public $callback;

    function __construct($consumer_key, $consumer_secret, $debug, $callback) {
        $this->oauth_consumer_key = $consumer_key;
        $this->oauth_consumer_secret = $consumer_secret;
        $this->debug = $debug;
        $this->callback = $callback;
    }

    
    function logit($msg, $preamble=true) {
        $now = date(DateTime::ISO8601, time());
        error_log(($preamble ? "+++${now}:" : '') . $msg);
    }

   
    function do_get($url, $port=80, $headers=NULL) {
        $retarr = array();
        $curl_opts = array(CURLOPT_URL => $url,
            CURLOPT_PORT => $port,
            CURLOPT_POST => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_RETURNTRANSFER => true);


        if ($headers) {
            $curl_opts[CURLOPT_HTTPHEADER] = $headers;
        }

        $response = $this->do_curl($curl_opts);

        if (!empty($response)) {
            $retarr = $response;
        }

        return $retarr;
    }

 
    function do_post($url, $postbody, $port=80, $headers=NULL) {
        $retarr = array();

        $curl_opts = array(CURLOPT_URL => $url,
            CURLOPT_PORT => $port,
            CURLOPT_POST => true,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_POSTFIELDS => $postbody,
            CURLOPT_RETURNTRANSFER => true);

        if ($headers) {
            $curl_opts[CURLOPT_HTTPHEADER] = $headers;
        }

        $response = do_curl($curl_opts);

        if (!empty($response)) {
            $retarr = $response;
        }

        return $retarr;
    }

  
    function do_curl($curl_opts) {

        $retarr = array();

        if (!$curl_opts) {
            if ($this->debug) {
                $this->logit("do_curl:ERR:curl_opts is empty");
            }
            return $retarr;
        }


        $ch = curl_init();

        if (!$ch) {
            if ($this->debug) {
                $this->logit("do_curl:ERR:curl_init failed");
            }
            return $retarr;
        }

        curl_setopt_array($ch, $curl_opts);

        curl_setopt($ch, CURLOPT_HEADER, true);

        if ($this->debug) {
            curl_setopt($ch, CURLINFO_HEADER_OUT, true);
            curl_setopt($ch, CURLOPT_VERBOSE, true);
        }

        ob_start();
        $response = curl_exec($ch);
        $curl_spew = ob_get_contents();
        ob_end_clean();
        if ($this->debug && $curl_spew) {
            $this->logit("do_curl:INFO:curl_spew begin");
            $this->logit($curl_spew, false);
            $this->logit("do_curl:INFO:curl_spew end");
        }

        if (curl_errno($ch)) {
            $errno = curl_errno($ch);
            $errmsg = curl_error($ch);
            if ($this->debug) {
                $this->logit("do_curl:ERR:$errno:$errmsg");
            }
            curl_close($ch);
            unset($ch);
            return $retarr;
        }

        if ($this->debug) {
            $this->logit("do_curl:DBG:header sent begin");
            $header_sent = curl_getinfo($ch, CURLINFO_HEADER_OUT);
            $this->logit($header_sent, false);
            $this->logit("do_curl:DBG:header sent end");
        }

        $info = curl_getinfo($ch);

        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $header = substr($response, 0, $header_size);
        $body = substr($response, $header_size);

        curl_close($ch);
        unset($ch);

        if ($this->debug) {
            $this->logit("do_curl:DBG:response received begin");
            if (!empty($response)) {
                $this->logit($response, false);
            }
            $this->logit("do_curl:DBG:response received end");
        }

        array_push($retarr, $info, $header, $body);

        return $retarr;
    }

 
    function json_pretty_print($json, $html_output=false) {
        $spacer = '  ';
        $level = 1;
        $indent = 0;
        $pretty_json = '';
        $in_string = false;

        $len = strlen($json);

        for ($c = 0; $c < $len; $c++) {
            $char = $json[$c];
            switch ($char) {
                case '{':
                case '[':
                    if (!$in_string) {
                        $indent += $level;
                        $pretty_json .= $char . "\n" . str_repeat($spacer, $indent);
                    } else {
                        $pretty_json .= $char;
                    }
                    break;
                case '}':
                case ']':
                    if (!$in_string) {
                        $indent -= $level;
                        $pretty_json .= "\n" . str_repeat($spacer, $indent) . $char;
                    } else {
                        $pretty_json .= $char;
                    }
                    break;
                case ',':
                    if (!$in_string) {
                        $pretty_json .= ",\n" . str_repeat($spacer, $indent);
                    } else {
                        $pretty_json .= $char;
                    }
                    break;
                case ':':
                    if (!$in_string) {
                        $pretty_json .= ": ";
                    } else {
                        $pretty_json .= $char;
                    }
                    break;
                case '"':
                    if ($c > 0 && $json[$c - 1] != '\\') {
                        $in_string = !$in_string;
                    }
                default:
                    $pretty_json .= $char;
                    break;
            }
        }

        return ($html_output) ?
                '<pre>' . htmlentities($pretty_json) . '</pre>' :
                $pretty_json . "\n";
    }

    function oauth_http_build_query($params, $excludeOauthParams=false) {

        $query_string = '';
        if (!empty($params)) {
            $keys = $this->rfc3986_encode(array_keys($params));
            $values = $this->rfc3986_encode(array_values($params));
            $params = array_combine($keys, $values);


            uksort($params, 'strcmp');

   
            $kvpairs = array();
            foreach ($params as $k => $v) {
                if ($excludeOauthParams && substr($k, 0, 5) == 'oauth') {
                    continue;
                }
                if (is_array($v)) {
                    
                    natsort($v);
                    foreach ($v as $value_for_same_key) {
                        array_push($kvpairs, ($k . '=' . $value_for_same_key));
                    }
                } else {
                    
                    array_push($kvpairs, ($k . '=' . $v));
                }
            }

            
            $query_string = implode('&', $kvpairs);
        }
        return $query_string;
    }


    function oauth_parse_str($query_string) {
        $query_array = array();

        if (isset($query_string)) {

            $kvpairs = explode('&', $query_string);

            
            foreach ($kvpairs as $pair) {
                list($k, $v) = explode('=', $pair, 2);

               
                if (isset($query_array[$k])) {
                    
                    if (is_scalar($query_array[$k])) {
                        $query_array[$k] = array($query_array[$k]);
                    }
                    array_push($query_array[$k], $v);
                } else {
                    $query_array[$k] = $v;
                }
            }
        }

        return $query_array;
    }


    function build_oauth_header($params, $realm='') {
        $header = 'Authorization: OAuth';
        foreach ($params as $k => $v) {
            if (substr($k, 0, 5) == 'oauth') {
                $header .= ',' . $this->rfc3986_encode($k) . '="' . $this->rfc3986_encode($v) . '"';
            }
        }
        return $header;
    }

  
    function oauth_compute_plaintext_sig($consumer_secret, $token_secret) {
        return ($consumer_secret . '&' . $token_secret);
    }


    function oauth_compute_hmac_sig($http_method, $url, $params, $consumer_secret, $token_secret) {

        $base_string = $this->signature_base_string($http_method, $url, $params);
        $signature_key = $this->rfc3986_encode($consumer_secret) . '&' . $this->rfc3986_encode($token_secret);
        $sig = base64_encode(hash_hmac('sha1', $base_string, $signature_key, true));
        if ($this->debug) {
            logit("oauth_compute_hmac_sig:DBG:sig:$sig");
        }
        return $sig;
    }

    
    function normalize_url($url) {
        $parts = parse_url($url);
		
        $scheme = $parts['scheme'];
        $host = $parts['host'];
		$port = ($scheme == 'https') ? '443' : '80';
        $path = $parts['path'];

        if (!$port) {
            $port = ($scheme == 'https') ? '443' : '80';
        }
        if (($scheme == 'https' && $port != '443')
                || ($scheme == 'http' && $port != '80')) {
            $host = "$host:$port";
        }

        return "$scheme://$host$path";
    }

    
    function signature_base_string($http_method, $url, $params) {
        $query_str = parse_url($url, PHP_URL_QUERY);
        if ($query_str) {
            $parsed_query = $this->oauth_parse_str($query_str);
            $params = array_merge($params, $parsed_query);
        }

        if (isset($params['oauth_signature'])) {
            unset($params['oauth_signature']);
        }

       
        $base_string = $this->rfc3986_encode(strtoupper($http_method)) . '&' .
                $this->rfc3986_encode($this->normalize_url($url)) . '&' .
                $this->rfc3986_encode($this->oauth_http_build_query($params));

        $this->logit("signature_base_string:INFO:normalized_base_string:$base_string");

        return $base_string;
    }

    
    function rfc3986_encode($raw_input){

        if (is_array($raw_input)) 
		{            
            return array_map(array($this, 'rfc3986_encode'), $raw_input);
        } else if (is_scalar($raw_input)) {
            return str_replace('%7E', '~', rawurlencode($raw_input));
        } else {
            return '';
        }
    }

    function rfc3986_decode($raw_input) {
        return rawurldecode($raw_input);
    }

}

class GmailGetContacts {

    function get_request_token($oauth, $usePost=false, $useHmacSha1Sig=true, $passOAuthInHeader=false) {
        $retarr = array();  // return value
        $response = array();

        $url = 'https://www.google.com/accounts/OAuthGetRequestToken';
        $params['oauth_version'] = '1.0';
        $params['oauth_nonce'] = mt_rand();
        $params['oauth_timestamp'] = time();
        $params['oauth_consumer_key'] = $oauth->oauth_consumer_key;
        $params['oauth_callback'] = $oauth->callback;
        $params['scope'] = 'https://www.google.com/m8/feeds';

        // compute signature and add it to the params list
        if ($useHmacSha1Sig) {

            $params['oauth_signature_method'] = 'HMAC-SHA1';
            $params['oauth_signature'] =
                    $oauth->oauth_compute_hmac_sig($usePost ? 'POST' : 'GET', $url, $params,
                            $oauth->oauth_consumer_secret, null);
        } else {
            echo "signature mathod not support";
        }

        if ($passOAuthInHeader) {

            $query_parameter_string = $oauth->oauth_http_build_query($params, FALSE);

            $header = $oauth->build_oauth_header($params);

            $headers[] = $header;
        } else {
            $query_parameter_string = $oauth->oauth_http_build_query($params);
        }

        if ($usePost) {
            $request_url = $url;
            $oauth->logit("getreqtok:INFO:request_url:$request_url");
            $oauth->logit("getreqtok:INFO:post_body:$query_parameter_string");
            $headers[] = 'Content-Type: application/x-www-form-urlencoded';
            $response = do_post($request_url, $query_parameter_string, 443, $headers);
        } else {
            $request_url = $url . ($query_parameter_string ?
                            ('?' . $query_parameter_string) : '' );

            $oauth->logit("getreqtok:INFO:request_url:$request_url");

            $response = $oauth->do_get($request_url, 443, $headers);
        }

        if (!empty($response)) {
            list($info, $header, $body) = $response;
            $body_parsed = $oauth->oauth_parse_str($body);
            if (!empty($body_parsed)) {
                $oauth->logit("getreqtok:INFO:response_body_parsed:");
            }
            $retarr = $response;
            $retarr[] = $body_parsed;
        }

        return $body_parsed;
    }

    function get_access_token($oauth, $request_token, $request_token_secret, $oauth_verifier, $usePost=false, $useHmacSha1Sig=true, $passOAuthInHeader=true) {
        $retarr = array();
        $response = array();

        $url = 'https://www.google.com/accounts/OAuthGetAccessToken';
        $params['oauth_version'] = '1.0';
        $params['oauth_nonce'] = mt_rand();
        $params['oauth_timestamp'] = time();
        $params['oauth_consumer_key'] = $oauth->oauth_consumer_key;
        $params['oauth_token'] = $request_token;
        $params['oauth_verifier'] = $oauth_verifier;
        
        if ($useHmacSha1Sig){
            $params['oauth_signature_method'] = 'HMAC-SHA1';
            $params['oauth_signature'] =
                    $oauth->oauth_compute_hmac_sig($usePost ? 'POST' : 'GET', $url, $params,
                            $oauth->oauth_consumer_secret, $request_token_secret);
        } else {
            echo "signature mathod not support";
        }
    
        if ($passOAuthInHeader) {
            $query_parameter_string = $oauth->oauth_http_build_query($params, false);
            $header = $oauth->build_oauth_header($params);
            $headers[] = $header;
        } else {
            $query_parameter_string = $oauth->oauth_http_build_query($params);
        }

       
        if ($usePost){
            $request_url = $url;
            logit("getacctok:INFO:request_url:$request_url");
            logit("getacctok:INFO:post_body:$query_parameter_string");
            $headers[] = 'Content-Type: application/x-www-form-urlencoded';
            $response = $oauth->do_post($request_url, $query_parameter_string, 443, $headers);
        } else {
            $request_url = $url . ($query_parameter_string ?
                            ('?' . $query_parameter_string) : '' );

            $oauth->logit("getacctok:INFO:request_url:$request_url");
            $response = $oauth->do_get($request_url, 443, $headers);
        }

        
        if (!empty($response)) {
            list($info, $header, $body) = $response;
            $body_parsed = $oauth->oauth_parse_str($body);
            if (!empty($body_parsed)) {
                $oauth->logit("getacctok:INFO:response_body_parsed:");                
            }
            $retarr = $response;
            $retarr[] = $body_parsed;
        }
        return $body_parsed;
    }


    function GetContacts($oauth, $access_token, $access_token_secret, $usePost=false, $passOAuthInHeader=true,$emails_count) {
        $retarr = array(); 
        $response = array();

        $url = "https://www.google.com/m8/feeds/contacts/default/full";
        $params['alt'] = 'json';
        $params['max-results'] = $emails_count;
        $params['oauth_version'] = '1.0';
        $params['oauth_nonce'] = mt_rand();
        $params['oauth_timestamp'] = time();
        $params['oauth_consumer_key'] = $oauth->oauth_consumer_key;
        $params['oauth_token'] = $access_token;

        $params['oauth_signature_method'] = 'HMAC-SHA1';
        $params['oauth_signature'] =
                $oauth->oauth_compute_hmac_sig($usePost ? 'POST' : 'GET', $url, $params,
                        $oauth->oauth_consumer_secret, $access_token_secret);
        
        if ($passOAuthInHeader){
            $query_parameter_string = $oauth->oauth_http_build_query($params, false);
            
            $header = $oauth->build_oauth_header($params);
           
            $headers[] = $header;
        } else {
            $query_parameter_string = $oauth->oauth_http_build_query($params);
        }

        if ($usePost){
            $request_url = $url;
            $oauth->logit("callcontact:INFO:request_url:$request_url");
            $oauth->logit("callcontact:INFO:post_body:$query_parameter_string");
            $headers[] = 'Content-Type: application/x-www-form-urlencoded';
            $response = $oauth->do_post($request_url, $query_parameter_string, 80, $headers);

        } else {
            $request_url = $url . ($query_parameter_string ?
                            ('?' . $query_parameter_string) : '' );
            $oauth->logit("callcontact:INFO:request_url:$request_url");
            $response = $oauth->do_get($request_url, 443, $headers);
        }

           
        if (!empty($response)) {
            list($info, $header, $body) = $response;
            if ($body) {

                $oauth->logit("callcontact:INFO:response:");
                $contact = json_decode($oauth->json_pretty_print($body), true);

               return $contact['feed']['entry'];     
            }
            $retarr = $response;
        }
        return $retarr;
    }
}
?>
