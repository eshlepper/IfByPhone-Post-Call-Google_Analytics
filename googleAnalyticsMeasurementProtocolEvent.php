<?php



class googleAnalyticsMeasurementProtocolEvent  {
   
    /* 
     * Basic default items
     */
    
    /* Output Test & Troubleshooting info or not */
    private $testing = TRUE ;
    
    /* Use POST/GET/AUTO for input */
    private $default_behavior = 'POST'; 
    
    /* Storage for the above choice */
    private $_ARRAY = array();
    
     /* associative array of all google parameter values keyed to parameter names */
    private $payload = array() ;
    
    /* Endpoint of Google Analytics API Call */
    private $base_url = 'www.google-analytics.com/collect';
    
    /* Flag to prevent double-sends when sendPayload() method is called directly */
    private $payload_is_sent = FALSE ;
    
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
        11 => FALSE,
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
    
    
    
    function __construct($ua_property_id, $event = 'pageview') {
        $this->setDefaultBehavior();
        
        $this->payload['dl'] = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; /* Document Location */
        
        $this->payload['dh'] = $_SERVER['HTTP_HOST']; /* Document Host */
        
        $uri = explode("?", $_SERVER['REQUEST_URI']);
        $this->payload['dp'] = array_shift($uri); /* Document Path */
        
        $this->payload['tid'] = $ua_property_id ; /* Tracking ID AKA Google Universal Analytics Property ID "UA Number" */
        $this->payload['v'] = $this->v ; /* GA Version */
        $this->payload['cid'] = $this->cid ; /* Client ID */
        $this->payload['t'] = $this->t ; /* Tracking ID */
        

    }
    
    
    /* Tells which array to use by default to set various automatic parameters */
    public function setDefaultBehavior($array = FALSE) {
        switch($array) {
            case 'GET': case 'get':
                $this->_ARRAY = $_GET ;
                break;
            case 'AUTO': case 'auto':
                $this->_ARRAY = array_merge($_POST, $_GET);
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
        $this->payload['t'] = 'event';
        $this->payload['ec'] = ( ( $category ) ? ( $category ) : ( 'Unknown Event' ) );                     // Event Category. Required.
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
        $this->payload['dh'] = $_SERVER['HTTP_HOST'];
        $this->payload['dp'] = $_SERVER['PHP_SELF'];
        $this->payload['dt'] = $this->dt ;
        $this->payload['dr'] = $_SERVER['HTTP_REFERER'];
        parse_str($_SERVER['HTTP_REFERER'], $params);
        
        /* This tricky little bit is a general function, parsing Google Search
         * URL-appended custom parameters into their constituent elements and 
         * sending them to GA (which would be done automatically w/Javascript API/analytics.js
         */
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
    

 
   

  /* Wrap things up, compute automatic fields, and send the data on destruct 
   * if not already sent.
   */
    function __destruct() {
        if(!$this->payload_is_sent) $this->sendPayload();

    }
    
    
    public function sendPayload() {
        /* URL Returns a 1x1 gif tracking pixel, ie, binary image content
         * So, you must either call header('Content-type:image/gif');
         * or wrap in HTML as image <img src='$url' /> to Display.
         */ 
        
            $this->createProxyData();
            $this->createCustomDimensions();
            
            foreach($this->payload as $i => $v) {
                if(strlen($v) > 0) {
                    $params[] = urlencode($i) . '=' . urlencode($v) ;
                }
            }
            
            $url = 'http://' . $this->base_url . '?' . implode('&', $params);
            
            if($this->testing) {
              echo "<div style='margin: 10px; color: #FFFFFF; background-color: #000000; border: 5px double red; padding: 5px;'>
                        <img src='$url' style='background-color: red; height: 3px; width: 3px; padding: 1px; margin: 3px;' title='This tiny dot is the Google Analytics Measurement Protocol Tracking Pixel.' /> 
                        &larr; This tiny dot is the Google Analytics Measurement Protocol Tracking Pixel.
                        </span>
                    </div>" ;
              echo "<h2>Data Sent to {$this->base_url}</h2>";
              foreach($this->payload as $i => $v) {
                  echo "<br />$i => $v";
              }
            } else {
                echo "<img src='$url' />";
            }
            
            $this->payload_is_sent = TRUE ;
    
    }
    




?>
