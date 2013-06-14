<?php

namespace Klathmon;

class reCaptcha
{

    private $public_key = '6LcH3eISAAAAAHP5XO9leJUDn6Qbp0KHzyAxqKA2';
    private $private_key = '6LcH3eISAAAAAEJbWk8QL1BM7JPX9GbQ5EKQ5lXY';

    public $api_create = 'https://www.google.com/recaptcha/admin/create';
    public $api_server = 'http://www.google.com/recaptcha/api';
    public $api_secure_server = 'https://www.google.com/recaptcha/api';
    public $verify_server = 'www.google.com';
    public $is_valid;
    public $error_message;

    private function encode_query($data)
    {

        $request = '';

        foreach($data as $key => $value){
            $request .= $key . '=' . urlencode(stripslashes($value)) . '&';
        }

        // cut the last '&'
        $request = substr($request, 0, strlen($request) - 1);

        return $request;

    }

    private function post($host, $path, $data, $port = 80)
    {

        $request = $this->encode_query($data);

        $http_request = "POST $path HTTP/1.0\r\n";
        $http_request .= "Host: $host\r\n";
        $http_request .= "Content-Type: application/x-www-form-urlencoded;\r\n";
        $http_request .= "Content-Length: " . strlen($request) . "\r\n";
        $http_request .= "User-Agent: reCAPTCHA/PHP\r\n";
        $http_request .= "\r\n";
        $http_request .= $request;

        $response = '';

        if(FALSE == ($fs = fsockopen($host, $port, $errno, $errstr, 10))) exit('reCaptcha: Could not open socket');

        fwrite($fs, $http_request);

        while(!feof($fs)) $response .= fgets($fs, 1160); // One TCP-IP packet

        fclose($fs);

        $response = explode("\r\n\r\n", $response, 2);

        return $response;

    }

    public function form($error = FALSE, $use_ssl = FALSE)
    {

        if(is_null($this->public_key) || empty($this->public_key)) exit('To use reCAPTCHA you must get an API key from <a href="' . $this->api_create . '">' . $this->api_create . '</a>');

        $server = ($use_ssl ? $this->api_secure_server : $this->api_server);

        $errorpart = '';

        if($error) $errorpart = '&amp;error=' . $error;

        echo '
		<script type="text/javascript" src="' . $server . '/challenge?k=' . $this->public_key . $errorpart . '"></script>
		<noscript>
			<iframe src="' . $server . '/noscript?k=' . $this->public_key . $errorpart . '" height="300" width="500" frameborder="0"></iframe><br/>
			<textarea name="recaptcha_challenge_field" rows="3" cols="40"></textarea>
			<input type="hidden" name="recaptcha_response_field" value="manual_challenge"/>
		</noscript>';

    }


    public function check_answer($remote_ip, $challenge, $response, $extra_params = array())
    {

        if(is_null($this->private_key) || empty($this->private_key)) exit('To use reCAPTCHA you must get an API key from <a href="' . $this->api_create . '">' . $this->api_create . '</a>');

        if(is_null($remote_ip) || empty($remote_ip)) exit('For security reasons, you must pass the remote IP to reCAPTCHA');

        // discard spam submissions
        if(is_null($challenge) || strlen($challenge) == 0 || is_null($response) || strlen($response) == 0){
            $this->is_valid      = FALSE;
            $this->error_message = 'incorrect-captcha-sol';

            return NULL;
        }

        $response = $this->post(
            $this->verify_server,
            '/recaptcha/api/verify',
            array(
                'privatekey' => $this->private_key,
                'remoteip'   => $remote_ip,
                'challenge'  => $challenge,
                'response'   => $response
            ) + $extra_params
        );

        $answers = explode("\n", $response[1]);

        if(trim($answers[0]) == 'true'){

            $this->is_valid = TRUE;

        } else{

            $this->is_valid      = FALSE;
            $this->error_message = $answers [1];

        }

        return ($this->is_valid ? $this->is_valid : FALSE);

    }

    public function get_signup_url($domain = FALSE, $appname = FALSE)
    {

        return $this->api_create . '?' . $this->encode_query(array('domains' => $domain, 'app' => $appname));

    }

}