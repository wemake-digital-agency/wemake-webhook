<?php

if (!class_exists('Wemake_Webhook_GitHub_Updater')) {

    class Wemake_Webhook_GitHub_Updater
    {
        private $plugin_file;
        private $plugin_slug;
        private $github_repo = 'wemake-digital-agency/wemake-webhook';
        private $github_api_url = 'https://api.github.com/repos/wemake-digital-agency/wemake-webhook/releases/latest';

        public function __construct($plugin_file)
        {
            $this->plugin_file = $plugin_file;
            $this->plugin_slug = plugin_basename($plugin_file);

            add_filter('pre_set_site_transient_update_plugins', [$this, 'check_update']);
            add_filter('plugins_api', [$this, 'plugin_info'], 10, 3);
        }

        public function check_update($transient)
        {
            if (empty($transient->checked)) {
                return $transient;
            }

            $response = wp_remote_get($this->github_api_url, [
                'headers' => [
                    'Accept' => 'application/vnd.github.v3+json',
                    'User-Agent' => 'WordPress',
                ],
                'timeout' => 15,
            ]);

            if (is_wp_error($response)) {
                return $transient;
            }

            $release = json_decode(wp_remote_retrieve_body($response));

            if (!$release || empty($release->tag_name)) {
                return $transient;
            }

            $latest_version = ltrim($release->tag_name, 'v');
            $current_version = $this->get_plugin_version();

            if (version_compare($latest_version, $current_version, '>')) {
                $plugin = new stdClass();
                $plugin->slug = $this->plugin_slug;
                $plugin->new_version = $latest_version;
                $plugin->url = $release->html_url;

                $package_url = '';
                if (!empty($release->assets)) {
                    foreach ($release->assets as $asset) {
                        if (strpos($asset->name, '.zip') !== false) {
                            $package_url = $asset->browser_download_url;
                            break;
                        }
                    }
                }

                if (!$package_url) {
                    $package_url = $release->zipball_url;
                }

                $plugin->package = $package_url;
                $transient->response[$this->plugin_slug] = $plugin;
            }

            return $transient;
        }

        public function plugin_info($res, $action, $args)
        {
            global $wp_version;

            if (!isset($args->slug) || $args->slug !== $this->plugin_slug) {
                return $res;
            }

            $response = wp_remote_get($this->github_api_url, [
                'headers' => [
                    'Accept' => 'application/vnd.github.v3+json',
                    'User-Agent' => 'WordPress',
                ],
                'timeout' => 15,
            ]);

            if (is_wp_error($response)) {
                return $res;
            }

            $release = json_decode(wp_remote_retrieve_body($response));

            if (!$release) {
                return $res;
            }

            $download_link = '';
            if (!empty($release->assets)) {
                foreach ($release->assets as $asset) {
                    if (strpos($asset->name, '.zip') !== false) {
                        $download_link = $asset->browser_download_url;
                        break;
                    }
                }
            }
            if (!$download_link) {
                $download_link = $release->zipball_url;
            }

            $res = new stdClass();
            $res->name = $this->get_plugin_name();
            $res->slug = $this->plugin_slug;
            $res->version = ltrim($release->tag_name, 'v');
            $res->tested = $wp_version;
            $res->author = '<a target="_blank" href="https://www.wemake.co.il/">Wemake Team</a>';
            $res->download_link = $download_link;
            $res->sections = [
                'description' => $this->get_plugin_description(),
                'changelog' => isset($release->body) ? nl2br($release->body) : '',
            ];

            return $res;
        }

        private function get_plugin_version()
        {
            $plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/' . $this->plugin_slug);
            return $plugin_data['Version'];
        }

        private function get_plugin_name()
        {
            $plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/' . $this->plugin_slug);
            return $plugin_data['Name'];
        }

        private function get_plugin_description()
        {
            $plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/' . $this->plugin_slug);
            return $plugin_data['Description'];
        }
    }

    add_action('admin_init', function () {
        new Wemake_Webhook_GitHub_Updater(wmhk_get_plugin_row_path());
    });
}
?>
