<?php
namespace App;

class Config
{
    public $cache_dir;

    public $feedly_client_id;

    public $feedly_client_secret;

    public $post_target_url;

    public $post_field_name;

    public function __construct()
    {
        $this->cache_dir = getenv('APP_CACHE_DIR');

        $this->feedly_client_id = getenv('FEEDLY_CLIENT_ID');
        $this->feedly_client_secret = getenv('FEEDLY_CLIENT_SECRET');

        $this->post_target_url = getenv('POST_TARGET_URL');
        $this->post_field_name = getenv('POST_FIELD_NAME');

        assert(is_dir($this->cache_dir));
        assert(strlen($this->feedly_client_id));
        assert(strlen($this->feedly_client_secret));
        assert(strlen($this->post_target_url));
        assert(strlen($this->post_field_name));
    }
}
