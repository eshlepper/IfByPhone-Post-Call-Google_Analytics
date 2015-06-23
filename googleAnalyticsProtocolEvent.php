<?php

/* � 2013 Daniel Benjamin - eShlepper Client Services  ALL RIGHTS RESERVED.
 * THIS SOFTWARE IS SUBJECT TO COPYRIGHT. MODIFICATION, POSSESSION, STORAGE,
 * TRANSMITTAL, COPYING, DISTRIBUTION, OR USE OF ANY SORT CONSTITUTES CRIMINAL
 * COPYRIGHT INFRINGEMENT. THIS IS NOT FREE SOFTWARE AND IS NOT SUBJECT TO ANY
 * PUBLIC USE LICENSE; ANY POSSESSION IS SUBJECT TO A PAID LICENSING AGREEMENT.
 * TO PURCHASE A LICENSE OR REPORT UNAUTHORIZED AT LICENSING@ESHLEPPER.COM. 
 * THE RIGHTS OF THE COPYRIGHT HOLDER WILL BE STRICTLY ENFORCED.
 */


class googleAnalyticsMeasurementProtocolEvent  {
   
    /* 
     * Basic default items
     */
    
    /* Output Test & Troubleshooting info or not */
    private $testing = FALSE ;
    
    /* Use POST/GET/AUTO for input */
    private $default_behavior = 'POST'; 
    
    /* Use Text Logging Function */
    private $log_file = 'GAMPE-log.txt';
    private $use_log = 'TRUE';
    
    /* Storage for the above choice */
    private $_ARRAY = array();
    
     /* associative array of all google parameter values keyed to parameter names */
    private $payload = array() ;
    
    /* Endpoint of Google Analytics API Call */
    private $base_url = 'www.google-analytics.com/collect';
    
    /* Test URL for debugging - returns JSON Validation info on call */
    private $test_url = 'www.google-analytics.com/debug/collect';
    
    /* Flags whether payload is sent, so that __destruct() knows whether to send or not */
    private $payload_is_sent = FALSE ;
    
    /* Flags whether or not Javascript is displayed -- allows "AUTO" mode to know whether to 
     * wrap pixel in "<noscript>". If javascript prints, use noscript. Otherwise, use bare pixel.
     * How the fuck would this be accomplished?
     */
    private $javascript_is_displayed = FALSE ;
    
    /* Required fields for flagging info; mostly text formatting of troubleshooting messages */
    private $required =  array('v', 'tid', 'cid', 'dp');
    
    /* How to make API Call: pixel, auto, silent, iframe, javascript, noscript 
     * 
     */ 
    private $output = 'pixel';
    
    /* Map of custom dimensions from GA->Account->Property->Custom Dimensions setup.
     * Mapped as int custom_dimension_id => variable_name (as expected in $_POST/$_GET array)
     * If POST/GET parameters by these names are passed, they will be mapped to
     * the corresponding GA custom dimensions. Obviously, the mapping here must match
     * the custom dimension mapping in the property setup for this property.
     * GA supports up to 20. If more are needed, perhaps explore custom metrics... ?
     */
    
    private $custom_dimension_map = array( 
        1 => 'call_duration',
        2 => 'dialed_ivr',
        3 => 'last_action',
        4 => 'campaign_name',
        5 => 'campaign_id',
        6 => 'first_action',
        7 => 'all_actions',
        8 => 'session_id',
        9 => 'click_description',
        10 => 'click_id',
        11 => 'phone_label',
        12 => FALSE,
        13 => FALSE,
        14 => FALSE,
        15 => FALSE,
        16 => FALSE,
        17 => FALSE,
        18 => FALSE,
        19 => FALSE,
        20 => FALSE,
    );
    
    
    /* Base Mandatory Parameters */
    private $basic_parameters = array('v', 'tid', 'cid', 't');
    private $v = 1 ;                // Version.
    private $tid = 'UA-XXXX-Y';     // Tracking ID / Property ID.
    private $cid = 555 ;            // Anonymous Client ID.
    private $t = 'pageview';        // Hit Type.
    
    
    /* Page Tracking Parameters */
    //private $t = 'pageview';     // Pageview hit type.
    private $page_tracking_parameters = array('dh', 'dp', 'dt');
    private $dh = 'mydemo.com';  // Document hostname.
    private $dp = '/home';       // Page.
    private $dt = 'homepage';    // Title.
    
    
    
    function __construct($ua_property_id, $event = FALSE) {
        $this->payload['tid'] = $ua_property_id ; /* Tracking ID AKA Google Universal Analytics Property ID AKA "UA Number" */
        $this->cid = substr(sha1(time()), 0, 8) . '.' . substr(md5(time()), 0, 8);
        $this->t = ( ( $event ) ? ( $event ) : ( $this->t ) );
        
        $this->setDefaultBehavior();
        
        
        $this->payload['dl'] = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; /* Document Location */
        
        $this->payload['dh'] = $_SERVER['HTTP_HOST']; /* Document Host */
        
        $uri = explode("?", $_SERVER['REQUEST_URI']);
        $this->payload['dp'] = array_shift($uri); /* Document Path */
        $this->payload['v'] = $this->v ; /* GA Version */
        $this->payload['cid'] = $this->cid ; /* Client ID */
        $this->payload['t'] = $this->t ; /* event type */
        
    }
    
    public function testMode($testing = TRUE) {
        $this->testing = $testing ;
    }
    
    
    /* Tells which array to use by default to set various automatic parameters */
    public function setDefaultBehavior($array = FALSE) {
        switch($array) {
            case 'GET': case 'get':
                $this->_ARRAY = $_GET ;
                break;
            default:
                $this->_ARRAY = $_POST ;
                break;
                
        }
    }
    
    
    /* Provide your own associative array with numeric keys 1-20 to map to your
     * GA Custom Dimensions. Alternately, just edit the above map in the signature.
     */
     
    public function mapCustomDimensions($map) {
        $this->custom_dimension_map = $map ;
    }
    
    
    public function setDataSource($ds) {
        /* Friendly Name for data source */
        $this->payload['ds'] = $ds ;
    }
    
    public function createEventData($category = FALSE, $action = FALSE, $label = FALSE, $value = 0) {
        $this->payload['t'] = 'event';                                                          // Event Type. Required.
        $this->payload['ec'] = ( ( $category ) ? ( $category ) : ( 'Unknown Event' ) );         // Event Category. Required.
        $this->payload['ea'] = ( ( $action ) ? ( $action ) : ( $this->payload['ec'] ) ) ;       // Event Action. Required.
        $this->payload['el'] = ( ( $label ) ? ( $label ) : ( $this->payload['ea'] ) ) ;         // Event label.
        $this->payload['ev'] = $value ;                                                         // Event value.
    }
    
    
    /* If mapped, translates POST/GET parameter names to GA custom dimensions, according
     * to the mapping defined in the above map.
     */
     
    public function createCustomDimensions() {
        foreach($this->_ARRAY as $i => $v) {
            if($v && in_array($i, $this->custom_dimension_map) && strlen($v) > 0) {
                $index = array_search($i, $this->custom_dimension_map);
                $this->payload['cd' . $index] = $this->_ARRAY["$i"] ;
            } 
        }
    }
    
    /* Create a one-off custom dimension.
     * Not terribly useful, as these can't really be created on the fly: they must
     * Be specified in the GA Account as such.
     */
     
    public function createCustomDimension($name, $value) {
        $i = array_search($name, $this->custom_dimension_map);
        if($i) $this->payload['cd' . $i] = $value ;
    }
    
    /* Adds more automated fields. Called on __destruct() */
    
    public function createProxyData($ip = FALSE, $ua = FALSE) {
        $this->payload['uip'] = $_SERVER['REMOTE_ADDR'] ;    // IP address override.
        $this->payload['ua'] = $_SERVER['HTTP_USER_AGENT'] ; // User agent override.
    }
    
    
    /* Your most basic basic "pageview" Google Analytics tracking functionality.
     * Title it if you want, or by default GA will display the URL instead.
     */
     
    public function createPageView($title = 'Untitled Page') {
        if($title != 'Untitled Page') $this->dt = $title ;
        $this->payload['dh'] = $_SERVER['HTTP_HOST'];       // Document Host
        $this->payload['dp'] = $_SERVER['PHP_SELF'];        // Document Page
        $this->payload['dt'] = $this->dt ;                  // Document Title
        $this->payload['dr'] = $_SERVER['HTTP_REFERER'];    // Document Referer

        
        /* This tricky little bit is a general function, parsing Google Search
         * URL-appended custom parameters into their constituent elements and 
         * sending them to GA (which would be done automatically w/Javascript API/analytics.js
         */
        parse_str($_SERVER['HTTP_REFERER'], $params);
        if(is_array($params)) {
            foreach($params as $name => $value) {
                if(!isset($this->payload["$name"])) $this->payload["$name"] = $value ;
            }
        }
        
    
    }
    
    
    /* Include UTM parameters if you so desire, either as a single associative array with utm_ names, or individually
     * It is done this way for a good reason, though not one likely to be of use to anyone else.
     */
    public function createCampaignParameters($campaign = FALSE, $source = FALSE, $medium = FALSE, $content = FALSE, $keyword = FALSE, $id = FALSE) {
        if(is_array($campaign)) { 
            $source = $campaign['utm_source'];
            $medium = $campaign['utm_medium'];
            $content = $campaign['utm_content'];
            $keyword = $campaign['utm_term'];
            $campaign = $campaign['utm_campaign'];

        }  
        
        if(!$campaign) {
            $source = $_GET['utm_source'];
            $medium = $_GET['utm_medium'];
            $content = $_GET['utm_content'];
            $keyword = $_GET['utm_term'];
            $campaign = $_GET['utm_campaign'];
        }
            /* proprietary eShlepper, Inc Marketing Automation Protocols */
            if($campaign) $this->payload['cn'] =  $campaign ; /* CampaignName AKA utm_campaign */
            if($source) $this->payload['cs'] = $source  ; /* CampaignSource AKA utm_source */
            if($medium) $this->payload['cm'] = $medium ; /* CampaignMedium AKA utm_medium */
            if($content) $this->payload['cc'] = $content ; /* CampaignContent AKA utm_content */
            if($keyword) $this->payload['ck'] = $keyword ; /* CampaignKeyword AKA utm_term */
            if($id) $this->payload['ci'] = $id ; /* CampaignID AKA... um... nothing */
        
    }
    
    
    /* Uses the User Id (UID) function for known users 
     * This is proprietary: in essence, an array of data corresponding to the information in a user's account
     * can be passed in, and the "pin" array element is set to the GA "uid" field.
     */
    public function importLead($lead = FALSE) {
        if(is_array($lead)) {
            $this->payload['uid'] = $lead['pin'];
        }
    }
    
  
    /* Wrap things up, compute automatic fields, and send the data on destruct */
    function __destruct() {
        if(!$this->payload_is_sent) $this->sendPayload();


    }
    
    
    public function sendPayload() {
        /* URL Returns a 1x1 gif tracking pixel, ie, binary image content
         * So, you must either call header('Content-type:image/gif');
         * or wrap in HTML as image <img src='$url' /> to Display.
         */ 
        
        
            /* Check and add last-second data that may have been changed by public methods */
            $this->createProxyData();
            $this->createCustomDimensions();
            if(!$this->payload['cn']) $this->createCampaignParameters(); /* ie, check $_GET array for them if still unset */
            
            foreach($this->payload as $i => $v) {
                if(strlen($v) > 0) {
                    $params[] = urlencode($i) . '=' . urlencode($v) ;
                }
            }
            
            $url =  ( ( $this->testing) ? ( $this->test_url ) : ( $this->base_url ) ) . 
                    '?' . implode('&', $params);
            
            
            switch($this->output) {
                case 'silent':
                    $this->sendCurl($url);
                    break;
                case 'noscript':
                    /* outputs only a noscript element containing the pixel for Non-Java Operation */
                    $this->displayNoScriptPixel($url);
                    break;
                case 'pixel':
                    echo "<img height='1' width='1' style='border-style:none;' alt='' src='http://$url&method=NAKED_PIXEL' />";
                    break;
                case 'javascript':
                    /* TODO build javascript version */
                    $this->displayJavascript();
                    break;
                case 'auto':
                    $this->displayJavascript();
                    $this->displayNoScriptPixel($url);
                    break;
                case 'test':
                    $this->displayTestPixel($url);
                    break;
                case 'iframe':
                    $this->displayIframe($url);
                    
                    break;
                   
            }   
             
              
             

            
            
            $this->payload_is_sent = TRUE ;
            $this->writeLog($url); 
    }
    
    
    public function setOutput($option = FALSE) {
        /* this should configure for various output options */
        $this->output = $option ;
    }
    
    /*
     * getCampaignData() may not be a terribly useful method for anyone. It is intended to pull
     * down dynamically-fetched UTM data for clients as stored in a proprietary CRM system.
     * However, the 5 key utm_XXXX parameters can be passed in as an associative array, keyed with 
     * the utm_ parameter names using the createCampaignParameters($array) method.
     */ 
    
    private function getCampaignData($campaign = FALSE) {
        $sql = "SELECT * FROM `campaigns` WHERE `id`='$campaign' ORDER BY `serial` DESC LIMIT 1";
        $result = mysql_query($sql);
        $this->campaign = mysql_fetch_assoc($result);
        $this->pixel_id = $this->campaign["$this->tag_field_name"];
    }
    

    private function sendCurl($url) {
        
        /* CURL must hit using HTTPS: secure protocol! */
        $handle = curl_init();
        $opts = curl_setopt_array($handle, array(
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_URL => 'https://' . $url . "&method=CURL",
            CURLOPT_USERAGENT => $_SERVER['HTTP_USER_AGENT'],
            CURLOPT_POST => 1,
            CURLOPT_POSTFIELDS => $this->payload
        ));
        // Send the request & save response to $resp
        $exec = curl_exec($handle);
        // Close request to clear up some resources
        $close = curl_close($handle);

        //$json_array = json_decode($exec, TRUE);

        if($this->testing) echo "<hr /><h1>JSON RESPONSE:</h1><br />$exec <hr />";
    }
    
    private function displayNoScriptPixel($url) {
          echo "<noscript>
            <div style='display:inline;'>
                <img height='1' width='1' style='border-style:none;' alt='' src='http://$url&method=NOSCRIPT_PIXEL' />
            </div>
          </noscript>\n";
    }
    
    private function displayJavascript() {
        /* TODO - Build this out to write all the attributes using the Javascript API */
        $this->javascript_is_displayed = TRUE ;
    }
    
    private function displayTestPixel($url) {
        echo "<div style='margin: 10px; color: #FFFFFF; background-color: #000000; border: 5px double red; padding: 5px;'>
                        <img src='https://$url&method=STYLED_PIXEL' style='background-color: red; height: 3px; width: 3px; padding: 1px; margin: 3px;' title='This tiny dot is the Google Analytics Measurement Protocol Tracking Pixel.' /> 
                        &larr; This tiny dot is the Google Analytics Measurement Protocol Tracking Pixel.
                        </span>
                    </div>" ;
        echo "<h3>Data Sent to {$url}</h3>";
              foreach($this->payload as $i => $v) {
                  echo "<br /><span style='color: " . ( ( in_array($i, $this->required) ) ? ( ' green ' ) : ( ' black ' ) ) . " ;'><strong>$i</strong> => $v</span>";
              }
    }
    
    private function displayIframe($url) {
        echo "<iframe height='1' width='1' src='http://$url' style='display: none'></iframe>";
    }
    
    private function writeLog($url) {
        if($this->use_log = TRUE) {
             $fh = fopen($this->log_file, 'a');
             fwrite($fh, "\n" . date("D, m/d/Y g:i:sa", time()) . " - $url");
             fclose($fh);
        }
    }
    
}



/* � 2013 Daniel Benjamin - eShlepper Client Services  ALL RIGHTS RESERVED.
 * THIS SOFTWARE IS SUBJECT TO COPYRIGHT. MODIFICATION, POSSESSION, STORAGE,
 * TRANSMITTAL, COPYING, DISTRIBUTION, OR USE OF ANY SORT CONSTITUTES CRIMINAL
 * COPYRIGHT INFRINGEMENT. THIS IS NOT FREE SOFTWARE AND IS NOT SUBJECT TO ANY
 * PUBLIC USE LICENSE; ANY POSSESSION IS SUBJECT TO A PAID LICENSING AGREEMENT.
 * TO PURCHASE A LICENSE OR REPORT UNAUTHORIZED AT LICENSING@ESHLEPPER.COM. 
 * THE RIGHTS OF THE COPYRIGHT HOLDER WILL BE STRICTLY ENFORCED.
 */
?>
