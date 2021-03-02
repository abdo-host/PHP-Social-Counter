# PHP Social Counter Plugin
## Grabs the latest counts of your fans/followers etc.

You can define what social networking sites you want to the plugin to display the information for. You can add any user ID or website URL to the plugin so that you can retrieve the counts for a different site than the one you have the plugin installed on.

[![Demo](https://www.tatwerat.com/demo/php-social-counter/screenshot.jpg)](https://www.tatwerat.com/demo/php-social-counter)

# [View Demo](https://www.tatwerat.com/demo/php-social-counter/)

## How To Use ?

##### Require Plugin File
 --
 ```ssh
 composer require tatwerat/social-counter
```
 
```php
// use composer
use tatwerat\SocialCounter;
```

```php
// require plugin
require "./SocialCounter.php";
```

```php
$SocialCounter = new SocialCounter([
    'facebook_id' => '{user_id}',
    'twitter_id' => '{user_id}',
    'youtube_id' => '{user_name or channel id}',
    'dribbble_id' => '{user_id}',
    'github_id' => '{user_id}',
    'soundcloud_id' => '{user_id}',
    'behance_id' => '{user_id}',
    'instagram_id' => '{user_id}',
    'google_api_key' => 'xxxxxxxx',
    'dribbble_access_token' => 'xxxxxxxxxxxxxx',
    'soundcloud_api_key' => 'xxxxxxxxxxxxxx',
    'behance_api_key' => 'xxxxxxxxxxxxxx',
]);
 
$SocialCounter->cache = true; // Cache Social Counts ( improvement the loading of your server )

```

>If you want to return all social count you can use this code
```php
$counts_data = $SocialCounter->print_data();
echo $counts_data['facebook'];
echo $counts_data['twitter'];
echo $counts_data['instagram'];
echo $counts_data['youtube'];
echo $counts_data['dribbble'];
echo $counts_data['behance'];
echo $counts_data['soundcloud'];
echo $counts_data['github'];
```

>If you want to call function one by one use this code
```php
echo $SocialCounter->facebook_count();
echo $SocialCounter->twitter_count();
echo $SocialCounter->youtube_count(); // parameter ($type = 'channel' or 'user') : default value='channel'
echo $SocialCounter->dribbble_count();
echo $SocialCounter->github_count();
echo $SocialCounter->soundcloud_count();
echo $SocialCounter->behance_count();
echo $SocialCounter->instagram_count();
```

## List Of Social Networks

- Display Facebook Fans Count
- Display Twitter Follower Count
- Display Instagram Follower Count
- Display YouTube Subscribers Count
- Display GitHub Follower Count
- Display SoundCloud Follower Count
- Display Behance Follower Count
- Display Dribbble Follower Count