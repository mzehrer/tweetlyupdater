<?php

class TweetlyUpdater {

    public $bitlyApiUrl = "http://api.bit.ly";
    public $bitlyApiVersion = "2.0.1";
    public $twitterSource = "tweetlyupdater";
    public $twitterApiUrl = "http://twitter.com";
    public $debug = false;

    private $twitterConsumerKey = "wgrjkYs4d1CAVPMQrqF9NA";
    private $twitterConsumerSecret = "byB67cTTMV9PdeWELrRn2LzPhk0zM3fLWz3I2k65g";
    private $twitterConnection;
    private $bitlyUser;
    private $bitlyApiKey;
    
    function __construct($twitterKey, $twitterSecret, $bitlyUser, $bitlyApiKey) {
        $this->twitterConnection = new TwitterOAuth($this->twitterConsumerKey, $this->twitterConsumerSecret, $twitterKey, $twitterSecret);
        $this->bitlyUser = $bitlyUser;
        $this->bitlyApiKey = $bitlyApiKey;
    }

    public function twitterVerifyCredentials() {
        $twObject = $this->twitterConnection->get("account/verify_credentials");
        if ($twObject->{'name'}) {
                return true;
        } else {
                error_log("Twitter auth failed: " . $twObject->{'error'});
        }
        return false;
    }
    
    public function twitterUpdate($message) {
        //error_log("Sending twitter update");
        $twObject =$this->twitterConnection->post('statuses/update', array('status' => $message));
        if ($twObject->{'id'} != null) {
            return  $twObject->{'id'} ;
        } else {
            error_log("Twitter update failed");
            return null;
        }
    }
    
    public function bitlyVerifyCredentials() {
        $bitlyObject = json_decode($this->curlGet($this->bitlyApiUrl . "/shorten?version=" . $this->bitlyApiVersion . "&longUrl=http://zepan.org&login=" . $this->bitlyUser . "&apiKey=" . $this->bitlyApiKey , '', ''));
        if ($bitlyObject->{'statusCode'} == 'OK') {
            return true;	
        }
        return false;
    }
    
    public function getBitlyUrl($longurl) {
        $shortUrl = $longurl;
    
        if($this->bitlyUser != '' && $this->bitlyApiKey != '') {
            $bitlyResp = $this->curlGet($this->bitlyApiUrl . "/shorten?version=" . $this->bitlyApiVersion . "&history=1&longUrl=" . $longurl . "&login=" . $this->bitlyUser . "&apiKey=" . $this->bitlyApiKey, '', '');
            $bitlyObject = json_decode($bitlyResp);
            if ($bitlyObject->{'statusCode'} == 'OK') {
                $shortUrl = $bitlyObject->{'results'}->{$longurl}->{'shortUrl'}; 	
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