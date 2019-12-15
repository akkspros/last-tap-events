<?php
/**
 * @version 1.0
 *
 * @package LastTapEvents/inc/controller
 * @see LastTap_BaseController
 */


class LastTap_ParticipantController extends LastTap_BaseController
{
    public $settings;

    public $callbacks;

    public function lt_register()
    {
        if (!$this->lt_activated('participant_manager')) return;

        $this->settings = new LastTap_SettingsApi();

        $this->callbacks = new LastTap_ParticipantCallbacks();

        add_action('init', array($this, 'lt_participant_cpt'));
        add_action('add_meta_boxes', array($this, 'lt_add_meta_boxes'));
        add_action('save_post', array($this, 'lt_save_meta_box'));
        add_action('manage_participant_posts_columns', array($this, 'lt_set_partici_custom_columns'));
        add_action('manage_participant_posts_custom_column', array($this, 'lt_set_partici_custom_columns_data'), 10, 2);
        add_filter('manage_edit-participant_sortable_columns', array($this, 'lt_set_partici_custom_columns_sortable'));
        add_action('admin_menu', array($this, 'lastTap_count_participant'));
        add_shortcode('particip-form', array($this, 'lt_participant_form'));
        add_shortcode('particip-slideshow', array($this, 'lt_participant_slideshow'));
        add_action('wp_ajax_submit_participant', array($this, 'lt_submit_participant'));
        add_action('wp_ajax_nopriv_submit_participant', array($this, 'lt_submit_participant'));
    }

    public function lt_submit_participant()
    {
        if (!DOING_AJAX || !check_ajax_referer('participant-nonce', 'nonce')) {
            return $this->return_json('error');
        }


        $name = sanitize_text_field($_POST['name']);
        $email = sanitize_email($_POST['email']);
        $telephone = sanitize_text_field($_POST['telephone']);
        $message = sanitize_text_field($_POST['message']);
        $party = isset($_POST['party']) ? 1 : 0;
        $post_event_id = sanitize_text_field($_POST['post_event_id']);
         
        $data = array(
            'post_event_id' => $post_event_id,
            'name' => $name,
            'email' => $email,
            'telephone' => $telephone,
            'approved' => 0,
            'party' => $party,
        );


        $args = array(
            'post_title' => __( 'New participant ', 'last-tap-event'),
            'post_content' => $message,
            'post_author' => 1,
            'post_status' => 'publish',
            'post_type' => 'participant',
            'meta_input' => array(
                '_event_participant_key' => $data
            )
        );

            $event_organizer_email = get_post_meta( $post_event_id, '_lt_event_organizer', true );


            $to = ' <'.$event_organizer_email.'>';

            $subject = apply_filters( 'lt_subject_participant', __('Hei! vou participar', 'last-tap-event'));

            $message = apply_filters( 'lt_message_participant', sprintf(  __('Hi! %s,', 'last-tap-event'), "<br>". $message) );

           (new LastTap_EmailController())->lt_send_email($to, $subject, $message);
            

        $postID = wp_insert_post($args);

        if ($postID) {
            return $this->lt_return_json('success');
        }

        return $this->lt_return_json('error');
    }

    public function lt_return_json($status)
    {
        $return = array(
            'status' => $status
        );
        wp_send_json($return);

        wp_die();
    }

    public function lt_participant_form()
    {

        require_once("$this->plugin_path/templates/participe-form.php");
    }

    public function lt_participant_slideshow()
    {
        require_once("$this->plugin_path/templates/slider.php");
    }

    public function lt_participant_cpt()
    {
        $labels = array(
            'name' => __('Participants','last-tap-event'),
            'singular_name' => __('Participant', 'last-tap-event')
        );

        $args = array(
            'labels' => $labels,
            'public' => true,
            'has_archive' => false,
            'menu_icon' => 'dashicons-admin-site-alt',
            'exclude_from_search' => true,
            'publicly_queryable' => false,
            'supports' => array('thumbnail', 'title', 'editor'),
            'show_in_rest' => false
        );

        register_post_type('participant', $args);
    }

    public function lt_add_meta_boxes()
    {
        add_meta_box(
            'participant_author',
            __( 'participant Options', 'last-tap-event'),
            array($this, 'lt_render_features_box'),
            'participant',
            'side',
            'default'
        );
    }

    public function lt_render_features_box($post)
    {
        wp_nonce_field('event_participant', 'event_participant_nonce');

        $data = get_post_meta($post->ID, '_event_participant_key', true);

        $name = isset($data['name']) ? $data['name'] : '';
        $email = isset($data['email']) ? $data['email'] : '';
        $telephone = isset($data['telephone']) ? $data['telephone'] : '';
        $approved = isset($data['approved']) ? $data['approved'] : false;
        $party = isset($data['party']) ? $data['party'] : false;
        $post_event_id = isset($data['post_event_id']) ? $data['post_event_id'] : false;
        ?>
        <p>

            <?php



            ?>
            <input type="hidden" id="post_event_id" name="post_event_id" class="widefat"
                   value="<?php echo esc_attr($post_event_id); ?>">
            <label class="meta-label" for="event_participant_author"><?php _e('Author Name', 'last-tap-event'); ?></label>
            <input type="text" id="event_participant_author" name="event_participant_author" class="widefat"
                   value="<?php echo esc_attr($name); ?>">
        </p>
        <p>
            <label class="meta-label" for="event_participant_email"><?php _e('Author Email', 'last-tap-event'); ?></label>
            <input type="email" id="event_participant_email" name="event_participant_email" class="widefat"
                   value="<?php echo esc_attr($email); ?>">
        </p>
        <p>
            <label class="meta-label" for="event_participant_telephone"><?php _e('Author Telephone', 'last-tap-event'); ?></label>
            <input type="text" id="event_participant_telephone" name="event_participant_telephone" class="widefat"
                   value="<?php echo esc_attr($telephone); ?>">
        </p>

        <div class="meta-container">
            <label class="meta-label w-50 text-left"
                   for="event_participant_approved"><?php _e('Approved', 'last-tap-event'); ?></label>
            <div class="text-right w-50 inline">
                <div class="ui-toggle inline"><input type="checkbox" id="event_participant_approved"
                                                     name="event_participant_approved"
                                                     value="1" <?php echo $approved ? 'checked' : ''; ?>>
                    <label for="event_participant_approved">
                        <div></div>
                    </label>
                </div>
            </div>
        </div>
        <div class="meta-container">
            <label class="meta-label w-50 text-left"
                   for="event_participant_party"><?php _e('Partic', 'last-tap-event'); ?></label>
            <div class="text-right w-50 inline">
                <div class="ui-toggle inline"><input type="checkbox" id="event_participant_party"
                                                     name="event_participant_party"
                                                     value="1" <?php echo $party ? 'checked' : ''; ?>>
                    <label for="event_participant_party">
                        <div></div>
                    </label>
                </div>
            </div>
        </div>
        <?php
    }

    public function lt_save_meta_box($post_id)
    {

        if (!isset($_POST['event_participant_nonce'])) {
            return $post_id;
        }

        $nonce = $_POST['event_participant_nonce'];
        if (!wp_verify_nonce($nonce, 'event_participant')) {
            return $post_id;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return $post_id;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return $post_id;
        }

        $data = array(
            'post_event_id' => sanitize_text_field($_POST['post_event_id']),
            'name' => sanitize_text_field($_POST['event_participant_author']),
            'email' => sanitize_email($_POST['event_participant_email']),
            'telephone' => sanitize_text_field($_POST['event_participant_telephone']),
            'approved' => isset($_POST['event_participant_approved']) ? 1 : 0,
            'party' => isset($_POST['event_participant_party']) ? 1 : 0,
        );

$tempDir = wp_upload_dir();
$uploadfile = $tempDir['path'] . '/' . $filename;

    // here our data
        $post_event_id = $data['post_event_id'];
        $name = $data['name'];
        $email = $data['email'];
        $telephone = $data['telephone'];
        $approved =  $data['approved'] == 1  ? "YES" : "NO";
        $party = $data['party'] == 1 ? "YES" : "NO";

        $codeContents= '';
        $codeContents .= "Code: ".$post_event_id. "\n";
        $codeContents .= "Name: ".$name. "\n";
        $codeContents .= "Email: ".$email. "\n";
        $codeContents .= "Telephone: ".$telephone. "\n";
        $codeContents .= "Approved: ".$approved. "\n";
        $codeContents .= "Party: ".$party. "\n";


    // generating
    QRcode::png($codeContents, $tempDir.'026.png', QR_ECLEVEL_L, 3);

    $wp_filetype = wp_check_filetype(basename($tempDir.'026.png'), null );

$attachment = array(
    'post_mime_type' => $wp_filetype['type'],
    'post_title' => $filename,
    'post_content' => '',
    'post_status' => 'inherit'
);

        $attach_id = wp_insert_attachment( $attachment, $uploadfile );

        $imagenew = get_post( $attach_id );
        $fullsizepath = get_attached_file( $imagenew->ID );
        $attach_data = wp_generate_attachment_metadata( $attach_id, $fullsizepath );
        wp_update_attachment_metadata( $attach_id, $attach_data );


        $data['code_qr'] =  'http://localhost:8888/wordpress/wp-content/uploads/026.png';
// Gives us access to the download_url() and wp_handle_sideload() functions
require_once( ABSPATH . 'wp-admin/includes/file.php' );

// URL to the WordPress logo
$url = 'http://s.w.org/style/images/wp-header-logo.png';
$timeout_seconds = 5;

// Download file to temp dir
$temp_file = download_url( $url, $timeout_seconds );

if ( !is_wp_error( $temp_file ) ) {

    // Array based on $_FILE as seen in PHP file uploads
    $file = array(
        'name'     => basename($url), // ex: wp-header-logo.png
        'type'     => 'image/png',
        'tmp_name' => $temp_file,
        'error'    => 0,
        'size'     => filesize($temp_file),
    );

    $overrides = array(
        // Tells WordPress to not look for the POST form
        // fields that would normally be present as
        // we downloaded the file from a remote server, so there
        // will be no form fields
        // Default is true
        'test_form' => false,

        // Setting this to false lets WordPress allow empty files, not recommended
        // Default is true
        'test_size' => true,
    );

    // Move the temporary file into the uploads directory
    $results = wp_handle_sideload( $file, $overrides );

    if ( !empty( $results['error'] ) ) {
        // Insert any error handling here
    } else {

        $filename  = $results['file']; // Full path to the file
        $local_url = $results['url'];  // URL to the file in the uploads dir
        $type      = $results['type']; // MIME type of the file

        // Perform any actions here based in the above results
    }

}

    // displaying
    echo "<img src=".$this->plugin_url."026.png />";


        update_post_meta($post_id, '_event_participant_key', $data);
    }

    public function lt_set_partici_custom_columns($columns)
    {
        $title = $columns['title'];
        $date = $columns['date'];
        unset($columns['title'], $columns['date']);

        $columns['name'] = __('Partic Name', 'last-tap-event');
        $columns['title'] = $title;
        $columns['telephone'] =  __('Telphone', 'last-tap-event');
        $columns['approved'] = __('Approved', 'last-tap-event');
        $columns['party'] = __('Partic', 'last-tap-event');
        $columns['code_qr'] = 'codigo';
        $columns['date'] = $date;

        return $columns;
    }

    public function lt_set_partici_custom_columns_data($column, $post_id)
    {
        $data = get_post_meta($post_id, '_event_participant_key', true);
        $name = isset($data['name']) ? $data['name'] : '';
        $email = isset($data['email']) ? $data['email'] : '';
        $telephone = isset($data['telephone']) ? $data['telephone'] : '';
        $approved = isset($data['approved']) && $data['approved'] === 1 ? '<strong>'. __( 'YES', 'last-tap-event').'</strong>' : __(  'NO', 'last-tap-event');
        $party = isset($data['party']) && $data['party'] === 1 ? '<strong>'. __( 'YES', 'last-tap-event').'</strong>' : __(  'NO', 'last-tap-event');

  $upload_dir = wp_upload_dir();
  $upload_dir = $upload_dir['baseurl'] . '/2019/12/wp-header-logo.png' ;
  $a =preg_replace('/^https?:/', '', $upload_dir);


        switch ($column) {
            case 'name':
                echo '<strong>' . $name . '</strong><br/><a href="mailto:' . $email . '">' . $email . '</a>';
                break;

            case 'telephone':
                echo $telephone;
                break;
            case 'approved':
                echo $approved;
                break;

            case 'party':
                echo $party;
                break;
            case 'code_qr':
                echo '<img src=' .$a.'/>';
                break;
            
        }

    }

    public function lt_set_partici_custom_columns_sortable($columns)
    {
        $columns['name'] = __( 'name', 'last-tap-event');
        $columns['approved'] = __( 'approved', 'last-tap-event');
        $columns['party'] = __( 'partic', 'last-tap-event');

        return $columns;
    }

    /*
    *total de numeros de participantes 
    */
    public function lastTap_count_participant() {
        global $menu;

         // get poty_type participant
        $all_post_ids = get_posts(array(

                'fields'          => 'post_id',
                'posts_per_page'  => -1,
                'post_type' => 'participant'
            ));
        $count_participant = [];

        // get post meta from post type participant
        foreach ($all_post_ids as $k => $v) {
            $count = get_post_meta( $v->ID, '_event_participant_key', false );
                foreach ($count as $key => $value) {
                    if( $value['approved'] == 0){
                        $count_participant[] = $value['post_event_id'];
                    }
                }
        }
                       
        // only display the number of pending posts over a certain amount
        if ( count($count_participant) > 0 ) {
            foreach ( $menu as $key => $value ) {
                if ( $menu[$key][2] == 'edit.php?post_type=participant' ) {
                    $menu[$key][0] .= ' <span class="update-plugins count-2"><span class="update-count">' . count($count_participant) . '</span></span>';
                    return;
                }
            }
        }
    }
     
}