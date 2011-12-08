<?php
# PHPVMC client
# Built By Ben Evans (github.com/bencevans # @bencevans # bensbit.co.uk)
class VMCPHP {
  public $VERSION = 0;

  # Targets
  public $DEFAULT_TARGET       = 'https://api.cloudfoundry.com';
  public $DEFAULT_LOCAL_TARGET = 'http://api.vcap.me';

  # General Paths
  public $INFO_PATH            = '/info';
  public $GLOBAL_SERVICES_PATH = '/info/services';
  public $GLOBAL_RUNTIMES_PATH = '/info/runtimes';
  public $RESOURCES_PATH       = '/resources';

  #user specific paths
  public $APPS_PATH     = '/apps';
  public $SERVICES_PATH = '/services';
  public $USERS_PATH    = '/users';
  

  //Don't Use Proxy
  //  $proxy = null;
  //Using a Proxy
  //  $proxy = 'location:port';
  //  e.g $proxy = 'localhost:8888';
  public $proxy = 'localhost:8888';

  public function version() {
    return $this->VERSION;
  }

  ######################################################
  # Target info
  ######################################################
  public function info() {
    return $this->json_get($this->INFO_PATH);
  }
  public function raw_info() {
    return $this->http_get($this->INFO_PATH);
  }
  # Global listing of services that are available on the target system
  public function services_info() {
    $this->check_login_status();
    return $this->json_get($this->GLOBAL_SERVICES_PATH);
  }
  public function runtimes_info() {
    return $this->json_get($this->GLOBAL_RUNTIMES_PATH);
  }

  ######################################################
  # Apps
  ######################################################

  public function apps() {
    $this->check_login_status();
    return $this->json_get($this->APPS_PATH);
  }
  public function create_app($name, $manifest = null) {
    $this->check_login_status();
    $app = $manifest;
    $app['name'] = $name;
    if(!isset($manifest['instances'])) {$app['instances'] = 1;}
    
    return $this->json_parse($this->json_post($this->APPS_PATH, $app));
  }
  public function update_app($name, $manifest) {
    $this->check_login_status();
    return $this->json_put($this->APP_PATH . '/' . $name, $manifest);
  }
  public function delete_app($name) {
    $this->check_login_status();
    return $this->http_delete($this->APPS_PATH . '/' . $name);
  }
  public function app_info($name) {
    $this->check_login_status();
    return $this->json_get($this->APPS_PATH . '/' . $name);
  }
  public function app_update_info($name) {
    $this->check_login_status();
    return $this->json_get($this->APPS_PATH . $name . '/update');
  }
  public function app_instances($name) {
    $this->check_login_status();
    return $this->json_get($this->APPS_PATH . '/' . $name . '/instances');
  }
  public function app_crashes($name) {
    $this->check_login_status();
    return $this->json_get($this->APPS_PATH . '/' . $name . '/crashes');
  }
  
  ######################################################
  # Services
  ######################################################
 
  # listing of services that are available in the system
  public function services() {
    $this->check_login_status();
    return $this->json_get($this->SERVICES_PATH);
  }

  ######################################################
  # Resources
  ######################################################
  # Send in a resources manifest array to the system to have
  # it check what is needed to actually send. Returns array
  # indicating what is needed. This returned manifest should be
  # sent in with the upload if resources were removed.
  # E.g. [{:sha1 => xxx, :size => xxx, :fn => filename}]
  public function check_resources($resources) {
    $this->check_login_status();
    $request = $this->json_post($this->RESOURCES_PATH, $resources);
    $body = $request['body'];
    json_parse($body);
  }

  ######################################################
  # Validation Helpers
  ######################################################

  # Checks that the target is valid
  public function target_valid($descr) {
    if (!$descr == $this->info()) {return false;}
    elseif (!$descr['name']) {return false;}
    elseif (!$descr['build']) {return false;}
    elseif (!$descr['version']) {return false;}
    elseif (!$descr['support']) {return false;}
    else {return true;}
  }
  # Checks that the auth_token is valid
  public function loggedin() {
    $descr = $this->info();
    if(!$descr) {return false;}
    elseif(!$descr['user']) {return false;}
    elseif(!$descr['usage']) {return false;}
    else {
      $this->USER = $descr['user'];
      return true;
    }
  }

  ######################################################
  # User login/password
  ######################################################

  # login and return an auth_token
  # Auth token can be retained and used in creating
  # new clients, avoiding login.
  public function login($user, $password) {
    $request = $this->json_post($this->USERS_PATH . '/' . $user . '/tokens', array('password' => $password));
    $body = $request;
    $response_info = $this->json_parse($body);
    if( $response_info) {
      $this->user = $user;
      $this->auth_token = $response_info['token'];

    }
  }

  # sets the password for the current logged user
  public function change_password($new_password) {
    $this->check_login_status();
    $user_info = $this->json_get($this->USERS_PATH . '/' . $this->user);
    if ($user_info) {
      $user_info['password'] = $new_password;
      json_put($this->USERS_PATH . '/' . $this->user, $user_info);
    }
  }

  ######################################################
  # System administration
  ######################################################

  public function proxy($proxy) {
    $this->proxy = $proxy;
  }
  public function proxy_for($proxy) {
    $this->proxy = $proxy;
  }
  public function users() {
    $this->check_login_status();
    $this->json_get($this->USERS_PATH);
  }
  public function add_user($user_email, $password) {
    $this->json_post($this->USERS_PATH, array( 'email' => $user_email, 'password' => $password ));
  }
  public function delete_user($user_email) {
    $this->check_login_status();
    $this->http_delete($this->USERS_PATH . '/' . $user_email);
  }
  ######################################################
  private function json_get($url) {
    $request = $this->http_get($url, 'application/json');
    return $this->json_parse($request);
  }
  private function json_post($url, $payload) {
    return $this->http_post($url, json_encode($payload), 'application/json');
  }
  private function json_put($url, $payload) {
    return $this->http_put($url, json_encode($payload), 'application/json');
  }
  private function json_parse($str) {
    return json_decode($str, 1);
  }
  # HTTP helpers
  private function http_get($path, $content_type=null) {
    return $this->request('get', $path, $content_type);
  }
  private function http_post($path, $body, $content_type=null) {
    return $this->request('post', $path, $content_type, $body);
  }
  private function http_put($path, $body, $content_type=null) {
    return $this->request('put', $path, $content_type, $body);
  }
  private function http_delete($path) {
    return $this->request('delete', $path);
  }

  private function request($method, $path, $content_type = null, $payload = null, $headers = array()) {
    if(isset($this->auth_token)){$headers['AUTHORIZATION'] = $this->auth_token;}
    if(isset($this->proxy)){$headers['PROXY-USER'] = $this->proxy;}
    if($content_type) {
      $headers['Content-Type'] = $content_type;
      $headers['Accept'] = $content_type;
    }
    $req = array(
        'method' => $method, 'url' => $this->target . $path,
        'payload' => $payload, 'headers' => $headers, 'multipart' => true );
    $request = $this->perform_http_request($req);
    
    return $request;

  }
  private function perform_http_request($req) {
       // is cURL installed yet?
    if (!function_exists('curl_init')){
        die('Sorry cURL is not installed!');
    }
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $req['url']);
    if($req['method']=='post') {
      curl_setopt($ch, CURLOPT_POST, 1);
      curl_setopt($ch, CURLOPT_POSTFIELDS, $req['payload']);
    } elseif ($req['method']=='put') {
      curl_setopt($ch, CURLOPT_PUT, 1);
      curl_setopt($ch, CURLOPT_PUTFIELDS, $req['payload']);
    } elseif ($req['method']=='delete') {
      curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
    } else {
         curl_setopt($ch, CURLOPT_HTTPGET, true);
    }

    curl_setopt($ch, CURLOPT_HEADER, 0);
    if(isset($this->auth_token)) {curl_setopt($ch, CURLOPT_HTTPHEADER,array('AUTHORIZATION: ' . $this->auth_token));}
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    if($this->proxy) {
      curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, 1); 
      curl_setopt($ch, CURLOPT_PROXY, $this->proxy); 
    }
    $output = curl_exec($ch);
    curl_close($ch);
    return $output;
  }
  private function truncate($str, $limit=30) {
    $etc = '...';
    $stripped = trim($str);
    if (strlen($stripped) > $limit) {
      return $stripped . $etc;
    } else {
      return $stripped;
    }
  }
  private function check_login_status() {
    if(isset($this->user)) {return true;
      } else {return false;}
  }
  private function logged_in() {
    
  }
}