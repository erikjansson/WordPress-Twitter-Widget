<?php
/*
 * Plugin Name: JW Twitter Widget
 * Plugin URI: http://net.tutsplus.com
 * Description: Display tweets in the sidebar of your blog.
 * Author: Jeffrey Way
 * Author URI: http://net.tutsplus.com
 * Version: 1.0
 */

class JW_Twitter_Widget extends WP_Widget {
    function __construct() {
    	$params = array(
    		'description' => 'Display and cache recent tweets to your readers.',
    		'name' => 'Twitter'
    	);
    	
    	// id, name, params
        parent::__construct('JW_Twitter_Widget', '', $params);
    }
    
    public function form($instance) {
       // $instance = title, twitter username, num_tweets
       extract($instance);
       ?>

        <p>
            <label for="">Title: </label>
            <input type="text"
                   class="widefat"
                   id="<?php echo $this->get_field_id('title'); ?>"
                   name="<?php echo $this->get_field_name('title'); ?>"
                   value="<?php if ( isset($title) ) echo esc_attr($title); ?>"
       </p>

        <p>
            <label for="<?php echo $this->get_field_id('username'); ?>">Twitter Username:</label> 
            <input class="widefat"
               type="text"
            	id="<?php echo $this->get_field_id('username'); ?>"
            	name="<?php echo $this->get_field_name('username'); ?>"
            	value="<?php if ( isset($username) ) echo esc_attr($username); ?>" />
        </p>
        
        <p>
            <label for="<?php echo $this->get_field_id('tweet_count'); ?>">
            	Number of Tweets to Retrieve: 
            </label>

            <input
               type="number"
               class="widefat"
               style="width: 80px;"
               id="<?php echo $this->get_field_id('tweet_count');?>"
               name="<?php echo $this->get_field_name('tweet_count');?>"
               min="1" 
               max="10"
               value="<?php echo !empty($tweet_count) ? $tweet_count : 5; ?>" />
        </p>
        <?php 
    }

	// What the visitor sees...
    public function widget($args, $instance) {
       extract($instance);
       extract( $args );

       if ( empty($title) ) $title = 'Recent Tweets';

       $data = $this->fetch_tweets($tweet_count, $username);

       if ( $data->tweets ) {
          echo $before_widget;
            echo $before_title;
               echo $title;
            echo $after_title;
               echo '<ul><li>' . implode('</li><li>', $data->tweets) . '</li></ul>';
          echo $after_widget;
       }
    }

    private function fetch_tweets($num_tweets, $username)
    {
       if ( empty($username) ) return; 

       $tweets = get_transient('recent_tweets_widget');
       if ( !$tweets ) {
         $tweets = curl("http://twitter.com/statuses/user_timeline/$username.json");
         
         $data = new StdClass();
         $data->username = $username;
         $data->num_tweets = $num_tweets;
         
         if ( !empty($tweets) ) {
            foreach($tweets as $tweet) {
               if ( $num_tweets-- === 0 ) break;
               $data->tweets[] = $this->filter_tweet( $tweet->text );
            }
         }
         
         set_transient('recent_tweets_widget', $data, 60 * 5); // five minutes
         return $data;
       } 
       else {
          // what if the username was changed. In that case, fetch new tweets.
          if ( $tweets->username !== $username || $tweets->num_tweets !== $num_tweets ) {
             delete_transient('recent_tweets_widget');
             $this->fetch_tweets($num_tweets, $username);
          }
       }
       return $tweets;
    }

    private function curl($url)
    {
      $c = curl_init($url);
      curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
      curl_setopt($c, CURLOPT_CONNECTTIMEOUT, 3);
      curl_setopt($c, CURLOPT_TIMEOUT, 5);

      return json_decode( curl_exec($c) );
    }

    private function filter_tweet($tweet)
    {
       // Username links
       $tweet = preg_replace('/(http[^\s]+)/im', '<a href="$1">$1</a>', $tweet);
       $tweet = preg_replace('/@([^\s]+)/i', '<a href="http://twitter.com/$1">@$1</a>', $tweet);
       // URL links
       return $tweet;
    }

}

// Here we gooooooo! (Mario voice)
add_action('widgets_init', function() {
	register_widget('JW_Twitter_Widget');
});

