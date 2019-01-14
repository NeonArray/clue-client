<?php

class WP_Post {

    public $ID;
    public $post_author;
    public $post_date;
    public $post_date_gmt;
    public $post_content;
    public $post_title;
    public $post_excerpt;
    public $post_status;
    public $comment_status;
    public $ping_status;
    public $post_password;
    public $post_name;
    public $to_ping;
    public $pinged;
    public $post_modified;
    public $post_modified_gmt;
    public $post_content_filtered;
    public $post_parent;
    public $guid;
    public $menu_order;
    public $post_type;
    public $post_mime_type;
    public $comment_count;
    public $filter;
    public $db_id;
    public $menu_item_parent;
    public $object_id;
    public $object;
    public $type;
    public $type_label;
    public $url;
    public $title;
    public $target;
    public $attr_title;
    public $description;
    public $classes;
    public $xfn;


    public function __construct( array $override_values = array() ) {
        $date = gmdate( 'Y-m-d H:i:s' );

        $this->ID                    = $override_values['ID'] ?? random_int( 1, 999 );
        $this->post_author           = $override_values['post_author'] ?? (string) random_int( 1, 999 );
        $this->post_date             = $override_values['post_date'] ?? $date;
        $this->post_date_gmt         = $override_values['post_date_gmt'] ?? $date;
        $this->post_content          = $override_values['post_content'] ?? '';
        $this->post_title            = $override_values['post_title'] ?? 'Web of Murder Color';
        $this->post_excerpt          = $override_values['post_excerpt'] ?? '';
        $this->post_status           = $override_values['post_status'] ?? 'inherit';
        $this->comment_status        = $override_values['comment_status'] ?? 'open';
        $this->ping_status           = $override_values['ping_status'] ?? 'closed';
        $this->post_password         = $override_values['post_password'] ?? '';
        $this->post_name             = $override_values['post_name'] ?? 'web-of-murder-color';
        $this->to_ping               = $override_values['to_ping'] ?? '';
        $this->pinged                = $override_values['pinged'] ?? '';
        $this->post_modified         = $override_values['post_modified'] ?? $date;
        $this->post_modified_gmt     = $override_values['post_modified_gmt'] ?? $date;
        $this->post_content_filtered = $override_values['post_content_filtered'] ?? '';
        $this->post_parent           = $override_values['post_parent'] ?? 0;
        $this->guid                  = $override_values['guid'] ?? 'http://clue.local/wp-content/uploads/2018/05/Web-of-Murder-Color.jpg';
        $this->menu_order            = $override_values['menu_order'] ?? 0;
        $this->post_type             = $override_values['post_type'] ?? 'attachment';
        $this->post_mime_type        = $override_values['post_mime_type'] ?? 'image/jpeg';
        $this->comment_count         = $override_values['comment_count'] ?? '0';
        $this->filter                = $override_values['filter'] ?? 'raw';

        if ( isset( $override_values['_menu_item'] ) ) {
            $this->make_menu_item();
        }
    }


    private function make_menu_item() {
        $this->db_id = $override_values['db_id'] ?? random_int( 1, 9999 );
        $this->menu_item_parent = $override_values['menu_item_parent'] ?? '';
        $this->object_id = $override_values['object_id'] ?? '';
        $this->object = $override_values['object'] ?? '';
        $this->type = $override_values['type'] ?? '';
        $this->type_label = $override_values['type_label'] ?? '';
        $this->url = $override_values['url'] ?? '';
        $this->title = $override_values['title'] ?? '';
        $this->target = $override_values['target'] ?? '';
        $this->attr_title = $override_values['attr_title'] ?? '';
        $this->description = $override_values['description'] ?? '';
        $this->classes = $override_values['classes'] ?? '';
        $this->xfn = $override_values['xfn'] ?? '';
    }
}
