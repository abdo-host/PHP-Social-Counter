<?php

/*
 * PHP Social Counter Plugin
 *
 * Grabs the latest counts of your Fans/Followers etc.
 * You can define what social networking sites you want to the plugin to display the information for. You can add any user ID or website URL to the plugin so that you can retrieve the counts for a different site than the one you have the plugin installed on.
 */

namespace tatwerat\SocialCounter;


class SocialCounter {


    public $options = [];
    public $cache = false;

    /*
     * __construct
     *
     * Class constructor where we will call our filter and action hooks.
     */
    public function __construct($options = []) {
        $this->options = $options;
    }

    /*
     * remote_get
     *
     * Get data from API's
     */
    function remote_get($url, $post_paramtrs = false) {
        // check if CURL is enabled
        if (!function_exists('curl_version')) {
            return;
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 3);
        curl_setopt($ch, CURLOPT_ENCODING, '');
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_AUTOREFERER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept-Language: en_US']);
        curl_setopt($ch, CURLOPT_USERAGENT, $this->random_user_agent());
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        if ($post_paramtrs) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_paramtrs));
        }
        $the_request = curl_exec($ch);
        //error checking
        if ($the_request === false) {
            curl_close($ch);
        }
        else {
            return $the_request;
        }
    }

    /*
     * format_number
     *
     * Number format function
     */
    function format_number($number) {
        if (!is_numeric($number)) {
            return $number;
        }
        if ($number >= 1000000) {
            return round(($number / 1000) / 1000, 1) . 'M';
        }
        else if ($number >= 100000) {
            return round($number / 1000, 0) . 'k';
        }
        return number_format($number);
    }

    /*
     * facebook_count
     *
     * facebook page fans counter
     */
    function facebook_count() {
        $url = "https://www.facebook.com/plugins/likebox.php?href=https://facebook.com/{$this->options['facebook_id']}&show_faces=true&header=false&stream=false&show_border=false&locale=en_US";
        $request_data = $this->remote_get($url);
        if ($request_data) {
            $pattern = '/_1drq[^>]+>(.*?)<\/a/s';
            preg_match($pattern, $request_data, $matches);
            if ($matches and isset($matches[1]) and !empty($matches[1])) {
                $counts = strip_tags($matches[1]);
                $counts_number = (int)filter_var($counts, FILTER_SANITIZE_NUMBER_INT);
                return ($counts_number && is_numeric($counts_number)) ? $this->format_number($counts_number) : "0";
            }
            else {
                return "0";
            }
        }
    }

    /*
     * twitter_count
     *
     * twitter account followers
     */
    function twitter_count() {
        $get_data = $this->remote_get("https://cdn.syndication.twimg.com/widgets/followbutton/info.json?screen_names=" . $this->options['twitter_id']);
        $json_data = ($get_data) ? json_decode($get_data, true) : [];
        return ($json_data and isset($json_data[0]) and isset($json_data[0]['followers_count'])) ? $this->format_number($json_data[0]['followers_count']) : "0";
    }

    /*
     * youtube_count
     *
     * youtube subscribers
     */
    function youtube_count($type = 'channel') {
        if (!empty($type) && $type == 'channel') {
            $api_url = "https://www.googleapis.com/youtube/v3/channels?part=statistics&id={$this->options['youtube_id']}&key={$this->options['google_api_key']}";
        }
        else if (!empty($type) && $type == 'user') {
            $api_url = "https://www.googleapis.com/youtube/v3/channels?part=statistics&forUsername={$this->options['youtube_id']}&key={$this->options['google_api_key']}";
        }
        $get_data = $this->remote_get($api_url);
        $json_data = ($get_data) ? json_decode($get_data, true) : [];
        return ($json_data and isset($json_data['items']) and $json_data['items'][0] and $json_data['items'][0]['statistics'] and $json_data['items'][0]['statistics']['subscriberCount']) ? $this->format_number($json_data['items'][0]['statistics']['subscriberCount']) : "0";
    }

    /*
     * vimeo_count
     *
     * vimeo subscribers
     */
    function vimeo_count() {
        $get_data = $this->remote_get("http://vimeo.com/api/v2/channel/{$this->options['vimeo_id']}/info.json");
        return $get_data;
    }

    /*
     * dribbble_count
     *
     * dribbble followers
     */
    function dribbble_count() {
        $get_data = $this->remote_get("https://api.dribbble.com/v2/user/?access_token={$this->options['dribbble_access_token']}");
        $json_data = ($get_data) ? json_decode($get_data, true) : [];
        return ($json_data and isset($json_data['followers_count'])) ? $this->format_number($json_data['followers_count']) : "0";
    }

    /*
     * github_count
     *
     * github followers
     */
    function github_count() {
        $get_data = $this->remote_get("https://api.github.com/users/{$this->options['github_id']}");
        $json_data = ($get_data) ? json_decode($get_data, true) : [];
        return ($json_data and isset($json_data['followers'])) ? $this->format_number($json_data['followers']) : "0";
    }

    /*
     * soundcloud_count
     *
     * soundcloud followers
     */
    function soundcloud_count() {
        $get_data = $this->remote_get("http://api.soundcloud.com/users/{$this->options['soundcloud_id']}.json?consumer_key={$this->options['soundcloud_api_key']}");
        $json_data = ($get_data) ? json_decode($get_data, true) : [];
        return ($json_data and isset($json_data['followers_count'])) ? $this->format_number($json_data['followers_count']) : "0";
    }

    /*
     * behance_count
     *
     * Behance Followers
     */
    function behance_count() {
        $get_data = $this->remote_get("http://www.behance.net/v2/users/{$this->options['behance_id']}?api_key={$this->options['behance_api_key']}");
        $json_data = ($get_data) ? json_decode($get_data, true) : [];
        return ($json_data and isset($json_data['user']) and isset($json_data['user']['stats']) and isset($json_data['user']['stats']['followers'])) ? $this->format_number($json_data['user']['stats']['followers']) : "0";
    }

    /**
     * instagram_count
     *
     * instagram followers
     */
    function instagram_count() {
        $get_data = $this->remote_get("https://www.instagram.com/{$this->options['instagram_id']}");
        $doc = new DOMDocument('1.0', 'UTF-8');
        @$doc->loadHTML($get_data);
        $xpath = new DOMXPath($doc);
        $js = $xpath->query('//body/script[@type="text/javascript"]')->item(0)->nodeValue;
        $start = strpos($js, '{');
        $end = strrpos($js, ';');
        $json = substr($js, $start, $end - $start);
        $json_data = json_decode($json, true);
        return ($json_data and isset($json_data['entry_data']) and isset($json_data['entry_data']['ProfilePage']) and isset($json_data['entry_data']['ProfilePage'][0]) and isset($json_data['entry_data']['ProfilePage'][0]['graphql']) and isset($json_data['entry_data']['ProfilePage'][0]['graphql']['user']) and isset($json_data['entry_data']['ProfilePage'][0]['graphql']['user']['edge_followed_by'])) ? $this->format_number($json_data["entry_data"]["ProfilePage"][0]["graphql"]["user"]["edge_followed_by"]["count"]) : "0";
    }

    /*
     * print_data
     *
     * print social counts
     */
    function print_data() {
        if ($this->cache) {
            if ($_COOKIE and isset($_COOKIE['php_social_counts'])) {
                return json_decode($_COOKIE['php_social_counts'], true);
            }
            else {
                $data = [
                    'facebook' => $this->facebook_count(),
                    'twitter' => $this->twitter_count(),
                    'youtube' => $this->youtube_count(),
                    'dribbble' => $this->dribbble_count(),
                    'github' => $this->github_count(),
                    'soundcloud' => $this->soundcloud_count(),
                    'behance' => $this->behance_count(),
                    'instagram' => $this->instagram_count(),
                ];
                setcookie('php_social_counts', json_encode($data), time() + 24 * 3600);
                return $data;
            }
        }
        else {
            $data = [
                'facebook' => $this->facebook_count(),
                'twitter' => $this->twitter_count(),
                'youtube' => $this->youtube_count(),
                'dribbble' => $this->dribbble_count(),
                'github' => $this->github_count(),
                'soundcloud' => $this->soundcloud_count(),
                'behance' => $this->behance_count(),
                'instagram' => $this->instagram_count(),
            ];
            return $data;
        }
    }

    /*
     * get_instance
     *
     * SocialCounter class instance method
     */
    public static function get_instance() {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /*
     * dumb_array
     */
    public function dumb_array($array) {
        echo '<pre style="overflow:auto; width:100%;">';
        print_r($array);
        echo '</pre>';
    }

    /*
     * dumb_array
     */
    public function random_user_agent() {
        $userAgentArray[] = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/62.0.3202.94 Safari/537.36";
        $userAgentArray[] = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/63.0.3239.84 Safari/537.36";
        $userAgentArray[] = "Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:57.0) Gecko/20100101 Firefox/57.0";
        $userAgentArray[] = "Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/62.0.3202.94 Safari/537.36";
        $userAgentArray[] = "Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/63.0.3239.84 Safari/537.36";
        $userAgentArray[] = "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_12_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/62.0.3202.94 Safari/537.36";
        $userAgentArray[] = "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_13_1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/62.0.3202.94 Safari/537.36";
        $userAgentArray[] = "Mozilla/5.0 (Windows NT 6.1; Win64; x64; rv:57.0) Gecko/20100101 Firefox/57.0";
        $userAgentArray[] = "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_13_1) AppleWebKit/604.3.5 (KHTML, like Gecko) Version/11.0.1 Safari/604.3.5";
        $userAgentArray[] = "Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:57.0) Gecko/20100101 Firefox/57.0";
        $userAgentArray[] = "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_13_2) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/63.0.3239.84 Safari/537.36";
        $userAgentArray[] = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/62.0.3202.89 Safari/537.36 OPR/49.0.2725.47";
        $userAgentArray[] = "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_13_2) AppleWebKit/604.4.7 (KHTML, like Gecko) Version/11.0.2 Safari/604.4.7";
        $userAgentArray[] = "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_12_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/63.0.3239.84 Safari/537.36";
        $userAgentArray[] = "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/62.0.3202.94 Safari/537.36";
        $userAgentArray[] = "Mozilla/5.0 (Macintosh; Intel Mac OS X 10.13; rv:57.0) Gecko/20100101 Firefox/57.0";
        $userAgentArray[] = "Mozilla/5.0 (Windows NT 6.1; WOW64; Trident/7.0; rv:11.0) like Gecko";
        $userAgentArray[] = "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_11_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/62.0.3202.94 Safari/537.36";
        $userAgentArray[] = "Mozilla/5.0 (Windows NT 6.3; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/62.0.3202.94 Safari/537.36";
        $userAgentArray[] = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/63.0.3239.108 Safari/537.36";
        $userAgentArray[] = "Mozilla/5.0 (X11; Linux x86_64; rv:57.0) Gecko/20100101 Firefox/57.0";
        $userAgentArray[] = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/52.0.2743.116 Safari/537.36 Edge/15.15063";
        $userAgentArray[] = "Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/62.0.3202.94 Safari/537.36";
        $userAgentArray[] = "Mozilla/5.0 (Macintosh; Intel Mac OS X 10.12; rv:57.0) Gecko/20100101 Firefox/57.0";
        $userAgentArray[] = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.110 Safari/537.36 Edge/16.16299";
        $userAgentArray[] = "Mozilla/5.0 (Windows NT 6.3; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/63.0.3239.84 Safari/537.36";
        $userAgentArray[] = "Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/63.0.3239.84 Safari/537.36";
        $userAgentArray[] = "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_13_1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/63.0.3239.84 Safari/537.36";
        $userAgentArray[] = "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_12_6) AppleWebKit/604.4.7 (KHTML, like Gecko) Version/11.0.2 Safari/604.4.7";
        $userAgentArray[] = "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_12_6) AppleWebKit/604.3.5 (KHTML, like Gecko) Version/11.0.1 Safari/604.3.5";
        $userAgentArray[] = "Mozilla/5.0 (X11; Linux x86_64; rv:52.0) Gecko/20100101 Firefox/52.0";
        $userAgentArray[] = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/61.0.3163.100 Safari/537.36";
        $userAgentArray[] = "Mozilla/5.0 (Windows NT 6.3; Win64; x64; rv:57.0) Gecko/20100101 Firefox/57.0";
        $userAgentArray[] = "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_11_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/63.0.3239.84 Safari/537.36";
        $userAgentArray[] = "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/63.0.3239.84 Safari/537.36";
        $userAgentArray[] = "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/63.0.3239.108 Safari/537.36";
        $userAgentArray[] = "Mozilla/5.0 (Windows NT 10.0; WOW64; Trident/7.0; rv:11.0) like Gecko";
        $userAgentArray[] = "Mozilla/5.0 (Windows NT 6.1; Win64; x64; rv:52.0) Gecko/20100101 Firefox/52.0";
        $userAgentArray[] = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/62.0.3202.94 Safari/537.36 OPR/49.0.2725.64";
        $userAgentArray[] = "Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/63.0.3239.108 Safari/537.36";
        $userAgentArray[] = "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_13_2) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/62.0.3202.94 Safari/537.36";
        $userAgentArray[] = "Mozilla/5.0 (Windows NT 6.1; rv:57.0) Gecko/20100101 Firefox/57.0";
        $userAgentArray[] = "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/51.0.2704.106 Safari/537.36";
        $userAgentArray[] = "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_10_5) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/62.0.3202.94 Safari/537.36";
        $userAgentArray[] = "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_11_6) AppleWebKit/604.4.7 (KHTML, like Gecko) Version/11.0.2 Safari/604.4.7";
        $userAgentArray[] = "Mozilla/5.0 (Macintosh; Intel Mac OS X 10.11; rv:57.0) Gecko/20100101 Firefox/57.0";
        $userAgentArray[] = "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Ubuntu Chromium/62.0.3202.94 Chrome/62.0.3202.94 Safari/537.36";
        $userAgentArray[] = "Mozilla/5.0 (Windows NT 10.0; WOW64; rv:56.0) Gecko/20100101 Firefox/56.0";
        $userAgentArray[] = "Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/62.0.3202.94 Safari/537.36";
        $userAgentArray[] = "Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:58.0) Gecko/20100101 Firefox/58.0";
        $userAgentArray[] = "Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/62.0.3202.94 Safari/537.36";
        $userAgentArray[] = "Mozilla/5.0 (Windows NT 6.1; Trident/7.0; rv:11.0) like Gecko";
        $userAgentArray[] = "Mozilla/5.0 (Windows NT 6.1; WOW64; rv:52.0) Gecko/20100101 Firefox/52.0";
        $userAgentArray[] = "Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.1; Trident/5.0;  Trident/5.0)";
        $userAgentArray[] = "Mozilla/5.0 (Windows NT 6.1; rv:52.0) Gecko/20100101 Firefox/52.0";
        $userAgentArray[] = "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Ubuntu Chromium/63.0.3239.84 Chrome/63.0.3239.84 Safari/537.36";
        $userAgentArray[] = "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_12_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/61.0.3163.100 Safari/537.36";
        $userAgentArray[] = "Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/61.0.3163.100 Safari/537.36";
        $userAgentArray[] = "Mozilla/5.0 (X11; Fedora; Linux x86_64; rv:57.0) Gecko/20100101 Firefox/57.0";
        $userAgentArray[] = "Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:56.0) Gecko/20100101 Firefox/56.0";
        $userAgentArray[] = "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/61.0.3163.100 Safari/537.36";
        $userAgentArray[] = "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_13_2) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/63.0.3239.108 Safari/537.36";
        $userAgentArray[] = "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/62.0.3202.89 Safari/537.36";
        $userAgentArray[] = "Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.0; Trident/5.0;  Trident/5.0)";
        $userAgentArray[] = "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_10_5) AppleWebKit/603.3.8 (KHTML, like Gecko) Version/10.1.2 Safari/603.3.8";
        $userAgentArray[] = "Mozilla/5.0 (Windows NT 6.1; WOW64; rv:57.0) Gecko/20100101 Firefox/57.0";
        $userAgentArray[] = "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_12_5) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/62.0.3202.94 Safari/537.36";
        $userAgentArray[] = "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_11_6) AppleWebKit/604.3.5 (KHTML, like Gecko) Version/11.0.1 Safari/604.3.5";
        $userAgentArray[] = "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_12_6) AppleWebKit/603.3.8 (KHTML, like Gecko) Version/10.1.2 Safari/603.3.8";
        $userAgentArray[] = "Mozilla/5.0 (Windows NT 10.0; WOW64; rv:57.0) Gecko/20100101 Firefox/57.0";
        $userAgentArray[] = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/51.0.2704.79 Safari/537.36 Edge/14.14393";
        $userAgentArray[] = "Mozilla/5.0 (Windows NT 6.1; Win64; x64; rv:56.0) Gecko/20100101 Firefox/56.0";
        $userAgentArray[] = "Mozilla/5.0 (iPad; CPU OS 11_1_2 like Mac OS X) AppleWebKit/604.3.5 (KHTML, like Gecko) Version/11.0 Mobile/15B202 Safari/604.1";
        $userAgentArray[] = "Mozilla/5.0 (Windows NT 10.0; WOW64; Trident/7.0; Touch; rv:11.0) like Gecko";
        $userAgentArray[] = "Mozilla/5.0 (Macintosh; Intel Mac OS X 10.13; rv:58.0) Gecko/20100101 Firefox/58.0";
        $userAgentArray[] = "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_13) AppleWebKit/604.1.38 (KHTML, like Gecko) Version/11.0 Safari/604.1.38";
        $userAgentArray[] = "Mozilla/5.0 (Windows NT 10.0) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/62.0.3202.94 Safari/537.36";
        $userAgentArray[] = "Mozilla/5.0 (X11; CrOS x86_64 9901.77.0) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/62.0.3202.97 Safari/537.36";
        $getArrayKey = array_rand($userAgentArray);
        return $userAgentArray[$getArrayKey];
    }

}