<?php
if (!defined('ABSPATH')) {
	exit;
}

if (!class_exists('LPGuestPostShortcodes')) {
    /**
	 * This class is used to all functions related to shortcodes.
	 *
	 * @since 1.0.0
	*/
    class LPGuestPostShortcodes {

		public function __construct() {
            /**
             * Add shortcodes to display form and content
             *
             * @since 1.0.0
            */
            add_shortcode( 'lp-guest-post-form', array($this, 'shortcode_submit_post_form'));
            add_shortcode( 'lp-guest-posts-list', array($this, 'shortcode_post_list'));

            /**
             * Hook to handle ajax request for submitting form.
             *
             * @since 1.0.0
            */
            add_action('wp_ajax_lpgp_submit_post',array($this, 'submit_post'));
        }

        /**
		 * Function to handle [lp-guest-post-form] shortcode.
		 * @since 1.0.0
		 * @return void
		 */
        public function shortcode_submit_post_form(){
            ob_start();
            
            /*
            Load scripts only when shortcode is used 
            */
            wp_enqueue_script('lp-guest-post-submission-front-js');
            wp_enqueue_media();
            wp_enqueue_style('bootstrap-min-css');

            if( !is_user_logged_in() ){ 
                $login_url = esc_url( wp_login_url( get_permalink() ) );
                ?>
                <div class="alert alert-danger" role="alert">
                    <?php _e('Please Login to view form.', LP_GUEST_POST_SUBMIT_TXTDOMAIN); ?> <a href="<?php echo $login_url; ?>" class="alert-link"><?php _e('Click here', LP_GUEST_POST_SUBMIT_TXTDOMAIN); ?></a>.
                </div>
                <?php
                return;
            }

            if(!current_user_can( 'edit_posts' )){
            ?>
                <div class="alert alert-danger" role="alert">
                    <h4><?php _e('Access Denied!', LP_GUEST_POST_SUBMIT_TXTDOMAIN); ?></h4>
                    <p><?php _e('You do not have rights to create a post.', LP_GUEST_POST_SUBMIT_TXTDOMAIN); ?>.</p>
                </div>
            <?php
                return;
            }
            ?>
            <div class="alert-wrapper d-none">
                <div class="alert" role="alert">
                    <h5 class="alert-heading"></h5>
                    <p></p>
                </div>
            </div>
            <form name="lpgp-create-post-form" method="post" id="lpgp-post-form" class="needs-validation" novalidate>
                <?php wp_nonce_field( 'lpgp-insert-post-nonce', 'lpgp-insert-post' ); ?>
                <div class="form-group">
                    <label for="lpgp-post-title"><?php _e('Post Title', LP_GUEST_POST_SUBMIT_TXTDOMAIN); ?></label>
                    <input type="text" class="form-control" id="lpgp-post-title" placeholder="Enter Post Title" name="lpgp-post-title" required />
                    <div class="invalid-feedback" id="invalid-post-title">
                        <?php _e('Please enter post title.', LP_GUEST_POST_SUBMIT_TXTDOMAIN); ?>
                    </div>
                </div>
                <div class="form-group">
                    <label for="lpgp-post-description"><?php _e('Post Description', LP_GUEST_POST_SUBMIT_TXTDOMAIN); ?></label>
                    <?php wp_editor( '', 'lpgp-post-description' ); ?>
                </div>
                <div class="form-group">
                    <label for="lpgp-post-excerpt"><?php _e('Post Excerpt', LP_GUEST_POST_SUBMIT_TXTDOMAIN); ?></label>
                    <textarea class="form-control" id="lpgp-post-excerpt" rows="3" name="lpgp-post-excerpt"></textarea>
                </div>
                <div class="form-group">
                    <label for="lpgp-post-image"><?php _e('Featured Image', LP_GUEST_POST_SUBMIT_TXTDOMAIN); ?></label>
                    <input type="hidden" name="lpgp-featured-image-id" id="lpgp-featured-image-id" />
                    <button type="button" class="btn btn-secondary btn-lg btn-block" id="lpgp-select-image"><?php _e('Select Image', LP_GUEST_POST_SUBMIT_TXTDOMAIN); ?></button>
                </div>
                <div class="form-group">
                    <div id="featured-image-preview" class="d-none">
                    </div>
                </div>
                <button type="submit" class="btn btn-primary btn-lg" id="lpgp-btn-submit"><?php _e('Submit', LP_GUEST_POST_SUBMIT_TXTDOMAIN); ?></button>
            </form>
            <?php
            return ob_get_clean();
        }

        /**
		 * Function to ajax request of form submission
		 * @since 1.0.0
		 * @return void
		 */
        public function submit_post(){
            if ( ! current_user_can( 'edit_posts' ) || !wp_verify_nonce( $_POST['lpgp-insert-post'], 'lpgp-insert-post-nonce' ))
            {
                echo json_encode(array('status' => 'failed', 'message_heading' => 'Denied!', 'message' => __('You don\'t have permission to create post')));
                die;
            }
            
            $post_title = sanitize_text_field($_POST['lpgp-post-title']);
            $post_description = $_POST['lpgp-post-description'];
            $post_excerpt = sanitize_text_field($_POST['lpgp-post-excerpt']);
            $post_featured_image_id = sanitize_text_field($_POST['lpgp-featured-image-id']);
            $post_array = array(
                'post_title'    => $post_title,
                'post_content'  => $post_description,
                'post_excerpt'  => $post_excerpt,
                'post_status'   => 'draft',
                'post_author'   => get_current_user_id(),
                'post_type'     => 'guest-post'
            );
            
            // Insert the post into the database.
            $post_id = wp_insert_post( $post_array );
            if($post_featured_image_id != ""){
                set_post_thumbnail( $post_id, $post_featured_image_id );
            }
            $this->send_notification($post_id);
            echo json_encode(array('status' => 'success', 'message_heading' => 'Thank You!', 'message' => __('Post added and sent to admin for moderation.', '')));
            die;
        }

        /**
		 * Function to send email notification to admin when new post is added
		 * @since 1.0.0
		 * @return void
		 */
        public function send_notification($post_id){
            $admin_email = get_option('admin_email');
            $headers = array('Content-Type: text/html; charset=UTF-8');
            $post = get_post($post_id);
            $post_title = $post->post_title;
            $post_author = $post->post_author;
            $post_permalink = get_permalink($post_id);

            $author = get_user_by('ID', $post_author);
            $username = $author->user_login;

            $subject = 'New Post added - '.$post_title;
            $content = '<p>Hi Admin</p>';
            $content .= '<p>'.$username.' has added a new post. Please check details below.</p>';
            $content .= '<p><b>Post Details:</b></p><br />';
            $content .= '<table>';
            $content .= '<tr><th align="left">ID : </th><td>'.$post_id.'</td></tr>';
            $content .= '<tr><th align="left">Post Title : </th><td>'.$post_title.'</td></tr>';
            $content .= '<tr><td colspan="2">&nbsp;</td></tr>';
            $content .= '<tr><td colspan="2" align="left"><a href="'.$post_permalink.'">'.__('Click here', '').'</a> to view post.</td>';
            $content .= '</table>';
            wp_mail( $admin_email, $subject, $content, $headers );
        }

        /**
		 * Function to handle [lp-guest-posts-list] shortcode.
		 * @since 1.0.0
		 * @return void
		 */
        public function shortcode_post_list(){
            ob_start();

            wp_enqueue_style('bootstrap-min-css');
            wp_enqueue_script('lp-guest-post-submission-front-js');

            if( !is_user_logged_in() ){ 
                $login_url = esc_url( wp_login_url( get_permalink() ) );
                ?>
                <div class="alert alert-danger" role="alert">
                    <?php _e('Please Login to view data.', LP_GUEST_POST_SUBMIT_TXTDOMAIN); ?> <a href="<?php echo $login_url; ?>" class="alert-link"><?php _e('Click here', LP_GUEST_POST_SUBMIT_TXTDOMAIN); ?></a>.
                </div>
                <?php
                return;
            }
            $user_id = get_current_user_id();
            $paged = ( get_query_var('paged') ) ? get_query_var('paged') : 1;
            $args = array(
                'post_type' => 'guest-post',
                'posts_per_page' => 10,
                'author' => $user_id, 
                'orderby' => 'ID',
                'order' => 'DESC',
                'paged' => $paged,
                'post_status' => array('draft', 'publish', 'trash')
            );
            $guest_posts = new WP_Query($args);
            if($guest_posts->have_posts()):
                $count = ($paged==1) ? 0 : ($paged-1) * 10;
                ?>
                <table class="table table-striped table-hover">
                    <thead class="thead-light">
                        <tr>
                            <th scope="col"><?php _e('Sr. No.', LP_GUEST_POST_SUBMIT_TXTDOMAIN); ?></th>
                            <th scope="col"><?php _e('Post Name', LP_GUEST_POST_SUBMIT_TXTDOMAIN); ?></th>
                            <th scope="col"><?php _e('Featured Image', LP_GUEST_POST_SUBMIT_TXTDOMAIN); ?></th>
                            <th scope="col"><?php _e('Status', LP_GUEST_POST_SUBMIT_TXTDOMAIN); ?></th>
                            <th scope="col"><?php _e('Post Date', LP_GUEST_POST_SUBMIT_TXTDOMAIN); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    while($guest_posts->have_posts()):
                        $count++;
                        $guest_posts->the_post();
                        if(has_post_thumbnail()):
                            $featured_img = '<img src="'.get_the_post_thumbnail_url(get_the_ID(),'post-thumbnail').'" alt="'.get_the_title().'"  title="'.get_the_title().'" class="img-thumbnail img-responsive" style="max-width:200px;"/>';
                        else:
                            $featured_img = 'Not set';
                        endif;
                        
                        $post_status = get_post_status();
                        $post_status_display = '';
                        if($post_status=='draft'):
                            $post_status_display = '<span class="badge badge-pill badge-primary">'.__('Pending', LP_GUEST_POST_SUBMIT_TXTDOMAIN).'</span>';
                        elseif($post_status=='publish'):
                            $post_status_display = '<span class="badge badge-pill badge-success">'.__('Published', LP_GUEST_POST_SUBMIT_TXTDOMAIN).'</span>';
                        elseif($post_status == 'trash'):
                            $post_status_display = '<span class="badge badge-pill badge-danger">'.__('Reject', LP_GUEST_POST_SUBMIT_TXTDOMAIN).'</span>';
                        endif;
                        
                    ?>
                        <tr>
                            <th scope="row"><?php echo $count; ?></th>
                            <td><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></td>
                            <td><?php echo $featured_img; ?></td>
                            <td><?php echo $post_status_display; ?></td>
                            <td><?php echo get_the_date( 'l, dS M Y' ); ?></td>
                        </tr>
                    <?php
                    endwhile; wp_reset_postdata();
                    ?>
                    </tbody>
                </table>
            <?php
            else: ?>
                <div class="alert alert-danger" role="alert">
                    <?php _e('No posts available.', LP_GUEST_POST_SUBMIT_TXTDOMAIN); ?> 
                </div>
            <?php
            endif;

            if($guest_posts->max_num_pages > 1): ?>
                <?php
                    $pagination = paginate_links( array(
                        'base' => get_pagenum_link(1) . '%_%',
                        'format' => 'page/%#%',
                        'type' => 'array',
                        'total' => $guest_posts->max_num_pages,
                        'current' => $paged
                    )); ?>
                    <?php 
                    if ( ! empty( $guest_posts ) ) : ?>
                        <ul class="pagination justify-content-center">
                            <?php 
                            foreach ( $pagination as $key => $page_link ): ?>
                                <li class="page-item<?php if ( strpos( $page_link, 'current' ) !== false ) { echo ' active'; } ?>"><?php echo $page_link; ?></li>
                            <?php 
                            endforeach; ?>
                        </ul>
                    <?php 
                    endif; ?>
            <?php
            endif; 
            return ob_get_clean();
        }
        
    }
}
/**
 * Guest Post Shortcode function Object to initialize constructor and load hooks
 */
$LPGuestPostShortcodesObj = new LPGuestPostShortcodes();
