<?php
namespace cmk\spmn;

// SECTION - token
class token {
  static function secret(){
    $secret = AUTH_KEY;
    return $secret;
  }

  static function clean($text){
    $text = str_replace(array('+', '/', '='), array('-', '_', ''), $text);
    return $text;
  }

  // ANCHOR - generate token
  static function generate_token(){
    $header = array(
      'alg' => 'HS256',
      'typ' => 'JWT'
    );
    $oneyear = 365 * 24 * 60 * 60;
    $payload = array(
      'iss' => get_bloginfo('url'),
      'iat' => time(),
      'exp' => time() + $oneyear
    );
    $secret = self::secret();
    $token = self::clean(base64_encode(json_encode($header)).'.'.base64_encode(json_encode($payload)));
    $signature = self::clean(base64_encode(hash_hmac('sha256', $token, $secret)));
    $token = $token.'.'.$signature;
    return $token;
  }

  // ANCHOR - verify token
  static function verify_token($token){
    $save_token = get_option('cmk_spmn_token');

    if($save_token !== $token) return false;

    $token = explode('.', $token);
    $header = json_decode(base64_decode($token[0]));
    $payload = json_decode(base64_decode($token[1]));
    $signature = $token[2];

    if($header->alg != 'HS256') return false;
    if($payload->iss != get_bloginfo('url')) return false;
    if($payload->exp < time()) return false;
    
    $secret = self::secret();
    $verify = self::clean(base64_encode(hash_hmac('sha256', $token[0].".".$token[1], $secret)));
    
    if($verify != $signature) return false;
    
    return true;
  }
}
// !SECTION

// SECTION - table
class table {
  function __construct() {
    global $status, $page;

    $this->checkbox = false;
    $this->columns = array();
    $this->rows = array();

    $this->status = $status;
    $this->page = $page;
  }

  function prepare_items() {
    $columns = $this->get_columns();
    $hidden = $this->get_hidden_columns();
    $sortable = $this->get_sortable_columns();

    $this->_column_headers = array($columns, $hidden, $sortable);
  }

  function get_columns() {
    return $this->columns;
  }

  function get_hidden_columns() {
    return array();
  }

  function get_sortable_columns() {
    return array();
  }

  function single_row($row) {
    echo '<tr>';
    if($this->checkbox){
      echo '<th scope="row" class="check-column"><input type="checkbox" /></th>';
    }
    foreach($this->columns as $key => $value) {
      echo "<td class='column-$key'>".($row[$key] ?? '')."</td>";
    }
    echo '</tr>';
  }

  function display_header() {
    echo '<thead><tr>';
    if($this->checkbox){
      echo '<th scope="col" id="cb" class="manage-column column-cb" style="width:24px;">
        <input type="checkbox" />
      </th>';
    }
    foreach ($this->_column_headers[0] as $key => $value) {
      echo "<th scope='col' id='$key' class='manage-column column-$key'>";
      echo "<span>$value</span></th>";
    }
    echo '</tr></thead>';
  }

  function display_body() {
    echo '<tbody>';
    foreach($this->rows as $row) {
      $this->single_row($row);
    }
    if(count($this->rows) == 0) {
      echo '<tr><td colspan="'.count($this->columns).'">No data</td></tr>';
    }
    echo '</tbody>';
  }

  function display($return = false) {
    if($return) ob_start();
    echo '<table class="wp-list-table widefat fixed striped">';
    $this->display_header();
    $this->display_body();
    echo '</table>';
    if($return) return ob_get_clean();
  }
}
// !SECTION

// SECTION - menu
class manu {
  static function generate_token(){
    $token = token::generate_token();
    update_option('cmk_spmn_token', $token);
    ?>
    <div class="wrap">
      <h1 style="margin-bottom:1rem;">Simple Manage</h1>
      <div class="notice notice-success is-dismissible"><p>Token generated</p></div>
    </div>
    <script>setTimeout(function(){window.location.reload()}, 3000)</script>
    <?php
  }

  static function delete_token(){
    delete_option('cmk_spmn_token');
    ?>
    <div class="wrap">
      <h1 style="margin-bottom:1rem;">Simple Manage</h1>
      <div class="notice notice-success is-dismissible"><p>Token deleted</p></div>
    </div>
    <script>setTimeout(function(){window.location.reload()}, 3000)</script>
    <?php
  }

  static function page_copy($token){
    ?>
    <div style="display:flex;flex-wrap:wrap;gap:0.5rem;">
      <input id="token" type="text" value="<?php echo $token; ?>" readonly style="width:480px" />
      <button class="button button-primary" onclick="copyToken()">Copy</button>
      <script>
        function copyToken() {
          var copyText = document.getElementById("token");
          copyText.select();
          copyText.setSelectionRange(0, 99999);
          document.execCommand("copy");
          alert("Copied");
        }
      </script>
      <form action="" method="post">
        <input type="hidden" name="action" value="delete_token" />
        <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('delete_token'); ?>" />
        <button class="button" onclick="deleteToken()">Delete</button>
      </form>
    </div>
    <?php
  }
  
  static function menu_page() {
    if($_POST["action"] == "generate_token"){
      if(!wp_verify_nonce($_POST["nonce"], 'generate_token')){
        wp_die('Nonce not valid');
      }
      self::generate_token();
      return;
    }
    if($_POST["action"] == "delete_token"){
      if(!wp_verify_nonce($_POST["nonce"], 'delete_token')){
        wp_die('Nonce not valid');
      }
      self::delete_token();
      return;
    }

    $token = get_option('cmk_spmn_token');

    echo '<div class="wrap">';
    echo '<h1 style="margin-bottom:1rem;">Simple Manage</h1>';
    if(!!$token){
      self::page_copy($token);
    } else {
      ?>
      <form action="" method="post">
        <input type="hidden" name="action" value="generate_token" />
        <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('generate_token'); ?>" />
        <input class="button button-primary" type="submit" value="Generate Token" />
      </form>
      <?php
    }
    echo '</div>';
  }

  static function add_menu() {
    add_menu_page(
      'Simple Manage',
      'Simple Manage',
      'manage_options',
      'cmk-spmn',
      array('cmk\spmn\manu', 'menu_page'),
      'dashicons-admin-generic',
      99
    );
  }
}
// !SECTION

// SECTION - api

// SECTION - Post
class PostCRUD {
  // ANCHOR - get thumbnail
  static function get_thumbnail($id, $content = ''){
    $thumbnail_id = get_post_thumbnail_id($id);
    if(!!$thumbnail_id){
      $thumbnail = wp_get_attachment_image_src($thumbnail_id, 'full');
      if(!!$thumbnail){
        return $thumbnail[0];  
      }
    }
    $doc = new \DOMDocument();
    @$doc->loadHTML($content);
    $tags = $doc->getElementsByTagName('img');
    foreach($tags as $tag){
      return $tag->getAttribute('src');
    }
    return "";
  }

  // ANCHOR - GET
  static function get($id){
    if(!$id){
      $posts = get_posts(array(
        'post_type' => 'post',
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'orderby' => 'post_modified',
        'order' => 'DESC'
      ));
      $result = array();
      foreach($posts as $post){
        $result[] = array(
          'id' => $post->ID,
          'title' => $post->post_title,
          'excerpt' => $post->post_excerpt,
          'date' => $post->post_date,
          'modified' => $post->post_modified,
          'status' => $post->post_status,
          'thumbnail' => self::get_thumbnail($post->ID, $post->post_content),
          'permalink' => get_permalink($post->ID)
        );
      }
      api::response(true, 'success', $result);
      return;
    } else {
      $post = get_post($id);
      if(!$post) return null;
      $result = array(
        'id' => $post->ID,
        'title' => $post->post_title,
        'content' => $post->post_content,
        'excerpt' => $post->post_excerpt,
        'status' => $post->post_status,
        'type' => $post->post_type,
        'date' => $post->post_date,
        'modified' => $post->post_modified
      );
      api::response(true, 'success', $result);
      return;
    }
  }

  // ANCHOR - CREATE
  static function create($data){
    $date = new \DateTime();
    $date = $date->format('Y-m-d H:i:s');
    $post = array(
      'post_title' => $data['title'],
      'post_content' => $data['content'],
      'post_status' => $data['status'] ?? 'publish',
      'post_type' => 'post',
      'post_date' => $date,
      'post_modified' => $date
    );
    $id = wp_insert_post($post);
    if(!$id){
      api::response(false, 'Failed to create post');
      return;
    }
    return self::get($id);
    return;
  }

  // ANCHOR - UPDATE
  static function update($data){
    $id = $data['id'];
    $modified = (new \DateTime())->format('Y-m-d H:i:s');
    
    if(!$id) api::response(false, 'ID not found');

    $post = array(
      'ID' => $id,
      'post_title' => $data['title'],
      'post_content' => $data['content'],
      'post_excerpt' => $data['excerpt'],
      'post_status' => $data['status'],
      'post_modified' => $modified
    );
    $id = wp_update_post($post);
    if(!$id){
      api::response(false, 'Failed to update post', $post);
      return;
    }
    $result = self::get($id);
    api::response(true, 'success', $result);
    return;
  }

  // ANCHOR - DELETE
  static function delete($id){
    $post = get_post($id);
    if(!$post){
      api::response(false, 'Post not found');
      return;
    };
    if($post->post_status == 'trash'){
      $result = wp_delete_post($id);
      if(!$result){
        api::response(false, 'Failed to delete post');
        return;
      }
      api::response(true, 'success');
      return;
    } else {
      $result = wp_trash_post($id);
      if(!$result){
        api::response(false, 'Failed to delete post');
        return;
      }
      api::response(true, 'success');
      return;
    }
  }
}
// !SECTION

// SECTION - Media
class media {
  // ANCHOR - GET
  static function get($id){
    if(!!$id){
      // get single media
      $media = get_post($id);
      if(!$media){
        api::response(false, 'Media not found');
        return;
      };
      $result = array(
        'id' => $media->ID,
        'title' => $media->post_title,
        'date' => $media->post_date,
        'modified' => $media->post_modified,
        'status' => $media->post_status,
        'thumbnail' => wp_get_attachment_image_src($media->ID, 'full')[0]
      );
      api::response(true, 'success', $result);
      return;
    } else {
      // get all media
      $result = array();
      $medias = get_posts(array(
        'post_type' => 'attachment',
        'post_status' => 'inherit',
        'posts_per_page' => -1,
        'orderby' => 'post_modified',
        'order' => 'DESC'
      ));
      foreach($medias as $media){
        $result[] = array(
          'id' => $media->ID,
          'title' => $media->post_title,
          'date' => $media->post_date,
          'modified' => $media->post_modified,
          'status' => $media->post_status,
          'thumbnail' => wp_get_attachment_image_src($media->ID, 'full')[0]
        );
      }
      api::response(true, 'success', $result);
      return;
    }
  }

  // ANCHOR - CREATE
  static function create(){
    $allowed = array('jpg', 'jpeg', 'png', 'gif', 'svg');
    $file = $_FILES['file'];

    // check file error
    if($file['error'] !== 0){
      api::response(false, 'Failed to upload file');
      return;
    }

    // check file type
    $filetype = wp_check_filetype($file['name']);
    if(!in_array($filetype['ext'], $allowed)){
      api::response(false, 'File type not allowed', $filetype);
      return;
    }

    // upload file
    $upload = wp_upload_bits($file['name'], null, file_get_contents($file['tmp_name']));
    if(!$upload['url']){
      api::response(false, 'Failed to upload file', $upload);
      return;
    }

    // insert attachment
    $attachment = array(
      'post_title' => $file['name'],
      'post_content' => '',
      'post_status' => 'inherit',
      'post_mime_type' => $filetype['type'],
      'guid' => $upload['url']
    );

    $id = wp_insert_attachment($attachment, $upload['file']);
    if(!$id){
      api::response(false, 'Failed to insert attachment', $attachment);
      return;
    }

    // get attachment
    $result = self::get($id);

    api::response(true, 'success', $result);
    return;
  }

  // ANCHOR - DELETE
  static function delete($id){
    $media = get_post($id);
    if(!$media){
      api::response(false, 'Media not found');
      return;
    };
    $result = wp_delete_attachment($id);
    if(!$result){
      api::response(false, 'Failed to delete media');
      return;
    }
    api::response(true, 'success');
    return;
  }

  static function main(){
    $method = $_SERVER['REQUEST_METHOD'];

    $token = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

    // check token
    if(!$token) self::response(false, 'Token not found');

    // verify token
    $token = explode(' ', $token)[1];
    $verify = token::verify_token($token);
    if(!$verify) api::response(false, 'Token not valid');

    $post = json_decode(file_get_contents('php://input'), true);

    switch($method){
      case "GET":
        self::get($post["id"] ?? $_REQUEST['id'] ?? '');
        break;
      case "POST":
      case "PUT":
        self::create();
        break;
      case "DELETE":
        self::delete($post["id"] ?? $_REQUEST['id'] ?? '');
        break;
      default:
        api::response(false, "Invalid method ($method)");
        break;
    }
    return;
  }
}
// !SECTION

class api {
  static function response($status = false, $message = '', $data = null){
    header('Content-Type: application/json');
    $result = array(
      'status' => $status,
      'message' => $message,
    );
    if(!!$data) $result['data'] = $data;
    echo json_encode($result);
    die();
  }

  static function type_post(){
    $method = $_POST["method"] ?? "POST";

    $token = $_POST["token"] ?? '';

    // check token
    if(!$token) self::response(false, 'Token not found');

    // verify token
    $verify = token::verify_token($token);
    if(!$verify) self::response(false, 'Token not valid');

    $post = json_decode(file_get_contents('php://input'), true);

    switch($method){
      case "GET":
      case "POST":
        $id = $_REQUEST['id'] ?? '';
        PostCRUD::get($id);
        break;
      case "PUT":
        PostCRUD::create($post);
        break;
      case "PATCH":
        PostCRUD::update($post);
        break;
      case "DELETE":
        $id = $_REQUEST['id'] ?? '';
        PostCRUD::delete($id);
        break;
      default:
        self::response(false, "Invalid method ($method)");
        break;
    }
    die();
  }

  // SECTION - Category
  static function category(){
    switch($_POST["method"]){
      case "GET":
      case "POST":
        $cats = get_categories(array(
          'orderby' => 'name',
          'order' => 'ASC',
          'hide_empty' => false
        ));
        self::response(true, 'success', $cats);
        break;
      case "PUT":
        $nicename = sanitize_title($_POST['name']);
        $cat = wp_insert_category(array(
          'cat_name' => $_POST['name'],
          'category_nicename' => $nicename,
          'category_parent' => $_POST['parent'] ?? 0
        ));
        if(!$cat){
          self::response(false, 'Failed to create category');
          return;
        }
        self::response(true, 'success', $cat);
        break;
      case "DELETE":
        $cat = wp_delete_category($_POST['id']);
        if(!$cat){
          self::response(false, 'Failed to delete category');
          return;
        }
        self::response(true, 'success', $cat);
        break;
      case "PATCH":
        $cat = wp_update_category(array(
          'cat_ID' => $_POST['id'],
          'cat_name' => $_POST['name'],
          'category_nicename' => $_POST['slug'] ?? sanitize_title($_POST['name']),
          'category_description' => $_POST['description'] ?? '',
          'category_parent' => $_POST['parent'] ?? 0
        ));
        if(!$cat){
          self::response(false, 'Failed to update category');
          return;
        }
        self::response(true, 'success', $cat);
        break;
      default:
        self::response(false, "Invalid method ($_POST[method])");
        break;
    }
    die();
  }
  // !SECTION

  // ANCHOR - main
  static function main(){
    // allow access from javascript fetch
    header('Access-Control-Allow-Origin: http://localhost:3000');
    header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE');
    header("Content-Type: application/json; charset=UTF-8");

    $post = json_decode(file_get_contents('php://input'), true);
    $type = $post['type'] ?? $_REQUEST['type'] ?? '';

    switch($type){
      case "post":
        self::type_post();
        break;
      case 'media':
        media::main();
        break;
      case 'cats':
        self::category();
        break;
      default:
        echo json_encode(array(
          'status' => false,
          'message' => "Invalid type ($type)",
        ));
        die();
    }
    
    die();
  }
}
// !SECTION