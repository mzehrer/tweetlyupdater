<?php

class TweetlyUpdater {

    public $bitlyApiUrl = "http://api.bit.ly";
    public $bitlyApiVersion = "2.0.1";
    public $twitterSource = "tweetlyupdater";
    public $twitterApiUrl = "http://twitter.com";
    public $debug = false;
    
    private $twittereUser;
    private $twitterPassword;
    private $bitlyUser;
    private $bitlyApiKey;
    
    function __construct($twittereUser, $twitterPassword, $bitlyUser, $bitlyApiKey) {
        $this->twittereUser = $twittereUser;
        $this->twitterPassword = $twitterPassword;
        $this->bitlyUser = $bitlyUser;
        $this->bitlyApiKey = $bitlyApiKey;
    }

    public function twitterVerifyCredentials() {
        $twObject = json_decode($this->curlGet($this->twitterApiUrl . "/account/verify_credentials.json",  $this->twittereUser, $this->twitterPassword));
        if ($twObject->{'authorized'}) {
                return true;
        }
        return false;
    }
    
    public function twitterUpdate($message) {
        $fields = array(    
            'status'=>urlencode($message),
            'source'=>$this->twitterSource
        );
        $twObject = json_decode($this->curlPost($this->twitterApiUrl ."/statuses/update.json", $fields, $this->twittereUser, $this->twitterPassword));  
        if ($twObject->{'id'} != null) {
            return  $twObject->{'id'} ;
        } else {
            error_log("Twitter update failed");
            return null;
        }
    }
    
    public function bitlyVerifyCredentials() {
        $bitlyObject = json_decode($this->curlGet($this->bitlyApiUrl . "/shorten?version=" . $this->bitlyApiVersion . "&long_url=http://zepan.org" , $this->bitlyUser, $this->bitlyApiKey));
        if ($bitlyObject->{'statusCode'} == 'OK') {
            return true;	
        }
        return false;
    }
    
    public function getBitlyUrl($longurl) {
        $shortUrl = $longurl;
    
        if($this->bitlyUser != '' && $this->bitlyApiKey != '') {
            $bitlyObject = json_decode($this->curlGet($this->bitlyApiUrl . "/shorten?version=" . $this->bitlyApiVersion . "&long_url=" . $longurl, $this->bitlyUser, $this->bitlyApiKey));
            if ($bitlyObject->{'statusCode'} == 'OK') {
                $shortUrl = $bitlyObject->{'results'}->{$longurl}->{'hashUrl'}; 	
            } else {
                error_log("bit.ly shorten request failed");                
            }
        } else {
                error_log("bit.ly login data incomplete");        
        }
    
        return $shortUrl;
    }

    private function curlGet($url, $user, $password) {
        if ($this->debug) {
                error_log("curlGet with url: " . $url);
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        if ($user != '' && $password != '') {
            if ($this->debug) {    
                error_log("Using authentication");
            }
            curl_setopt($ch, CURLOPT_USERPWD, $user . ":" . $password);
            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $data = curl_exec($ch);
        if ($this->debug) {
                error_log("curl GET response: " . $data);
        }
        curl_close($ch);                 
        return $data;
    }
    
    private function curlPost($url, $fields, $user, $password) {
        if ($this->debug) {
                error_log("curlPOST with url: " . $url);
        }
        foreach($fields as $key=>$value) { $fields_string .= $key.'='.$value.'&'; }  
        rtrim($fields_string,'&');  
        
        $ch = curl_init();  
            
        curl_setopt($ch,CURLOPT_URL,$url);  
        curl_setopt($ch,CURLOPT_POST,count($fields));  
        curl_setopt($ch,CURLOPT_POSTFIELDS,$fields_string);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER, 1);

        if ($user != '' && $password != '') {
            if ($this->debug) {    
                error_log("Using authentication");
            }
            curl_setopt($ch, CURLOPT_USERPWD, $user . ":" . $password);
            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
        }
          
        $result = curl_exec($ch);
        if ($this->debug) {
                error_log("curl POST response: " . $result);
        }    
        curl_close($ch);
        return $result;
    }

}
?>