<?php
namespace App;

use Zend\Http\Request;

class Application
{
    /**
     * @var Config
     */
    private $config;

    public static function create()
    {
        return new self(new Config());
    }

    public function __construct(Config $config)
    {
        set_error_handler(function($severity, $message, $file, $line) {
            if (error_reporting() & $severity) {
                throw new \ErrorException($message, 0, $severity, $file, $line);
            }
        });

        $this->config = $config;
    }

    private function log($fmt, ...$args)
    {
        if ($args) {
            vfprintf(STDERR, $fmt . PHP_EOL, $args);
        } else {
            fputs(STDERR, $fmt . PHP_EOL);
        }
    }

    public function get_authorization_url()
    {
        return sprintf("https://cloud.feedly.com/v3/auth/auth?%s", http_build_query([
            'client_id' => $this->config->feedly_client_id,
            'redirect_uri' => 'http://localhost:8080',
            'scope' => 'https://cloud.feedly.com/subscriptions',
            'response_type' => 'code',
        ]));
    }

    public function authorization(string $code)
    {
        $client = new HttpClient('http://cloud.feedly.com/v3/auth/token');
        $client->setMethod(Request::METHOD_POST);
        $client->setParameterPost([
            'client_id' => $this->config->feedly_client_id,
            'client_secret' => $this->config->feedly_client_secret,
            'grant_type' => 'authorization_code',
            'redirect_uri' => 'http://localhost:8080',
            'code' => $code,
        ]);

        $data = $client->safeRequestAsJson();
        $this->save_access_token($data);
    }

    public function run()
    {
        list ($access_token, $user_id) = $this->load_access_token();

        $entries = $this->fetch_saved_entries($access_token, $user_id);
        $entries = $this->filter_entries_response($entries);
        $this->log("fetch %d entries", count($entries));

        foreach ($entries as $id => $url) {
            $this->log("post %s", $url);
            $this->post_to_target($url);
        }

        if ($entries) {
            $this->unsaved_entries($access_token, $entries);
            $this->log("unsaved %d entries", count($entries));
        }
    }

    public function get_cache_filename()
    {
        return $this->config->cache_dir . DIRECTORY_SEPARATOR . 'feedly_access_token.json';
    }

    public function load_access_token()
    {
        $filename = $this->get_cache_filename();
        $contents = file_get_contents($filename);
        $data = json_decode($contents, true);

        $expires_at = $data['expires_at'] ?? 0;
        if (time() > $expires_at) {
            $data = $this->refresh_access_token($data['refresh_token']);
            $this->save_access_token($data);
        }

        return [$data['access_token'], $data['id']];
    }

    public function save_access_token(array $data)
    {
        $filename = $this->get_cache_filename();
        if (!is_dir(dirname($filename))) {
            mkdir(dirname($filename), 0777, true);
        }
        $data['expires_at'] = time() + (int)($data['expires_in'] / 2);
        $contents = json_encode($data, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE);
        file_put_contents($filename, $contents);
    }

    public function refresh_access_token(string $refresh_token)
    {
        $client = new HttpClient('http://cloud.feedly.com/v3/auth/token');
        $client->setMethod(Request::METHOD_POST);
        $client->setParameterPost([
            'client_id' => $this->config->feedly_client_id,
            'client_secret' => $this->config->feedly_client_secret,
            'grant_type' => 'refresh_token',
            'refresh_token' => $refresh_token,
        ]);

        return $client->safeRequestAsJson();
    }

    public function fetch_saved_entries(string $access_token, string $user_id)
    {
        $client = new HttpClient('http://cloud.feedly.com/v3/streams/contents');
        $client->setAccessToken($access_token);
        $client->setParameterGet([
            'streamId' => "user/{$user_id}/tag/global.saved",
        ]);

        return $client->safeRequestAsJson();
    }

    public function filter_entries_response(array $entries)
    {
        return array_reduce($entries['items'], function ($r, $item) {
            $r[$item['id']] = $item['originId'];
            return $r;
        }, []);
    }

    public function unsaved_entries(string $access_token, array $entries)
    {
        $r = new Request();
        $client = new HttpClient('http://cloud.feedly.com/v3/markers');
        $client->setMethod(Request::METHOD_POST);
        $client->setAccessToken($access_token);
        $client->setJsonBody([
            'action' => 'markAsUnsaved',
            'type' => 'entries',
            'entryIds' => array_keys($entries),
        ]);
        $client->safeRequest();
    }

    public function post_to_target(string $url)
    {
        $client = new HttpClient($this->config->post_target_url);
        $client->setMethod(Request::METHOD_POST);
        $client->setJsonBody([
            $this->config->post_field_name => $url,
        ]);
        $client->safeRequest();
    }
}
