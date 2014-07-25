<?php
    /**
        * Plugin Name: .WPPizza Openingtime Shortcode
        * Plugin URI: https://github.com/fwermelinger/wppizza_openingtime_shortcode
        * Description: Replaces [wppizza_otc] tags with the widget for the next opening time
        * Version: 0.1
        * Author: Florian Wermelinger
        * Author URI: http://www.webtopf.ch
        * License: GPL2
        */
    
        /*  Copyright 2014  Florian Wermelinger  (email : info@webtopf.ch)
    
        This program is free software; you can redistribute it and/or modify
        it under the terms of the GNU General Public License, version 2, as 
        published by the Free Software Foundation.
    
        This program is distributed in the hope that it will be useful,
        but WITHOUT ANY WARRANTY; without even the implied warranty of
        MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
        GNU General Public License for more details.
    
        You should have received a copy of the GNU General Public License
        along with this program; if not, write to the Free Software
        Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
    */
    
        defined('ABSPATH') or die("No direct access!");
    
    
        //add_action( 'plugins_loaded', 'wppizza_extend_otc');
        register_uninstall_hook( __FILE__, 'wppizza_openingtime_shortcode_uninstall');
    
        /*************************************************************************
        *
        *
        *   [Shortcode Code]
        *
        *                
        **************************************************************************/
        if (!is_admin()) 
        {
          add_action( 'plugins_loaded', 'wppizza_otc_addshortcode');
        }
    
        //tell wordpress about this shortcode
        function wppizza_otc_addshortcode() 
        {
            add_shortcode('wppizza_otc', 'wppizza_otc_process_shortcode');
        }    
    
        function wppizza_otc_process_shortcode($attributes, $content = null) 
        {
            include('inc/helperFunctions.php');
    
            //register script
            wp_enqueue_script('openingwidget.js', plugins_url().'/wppizza-openingtime-shortcode/js/scripts.custom.openingwidget.js', NULL, '0.3');
    
            //get options
            $optionstmp = get_option('wppizza');
            $options = $optionstmp['opening_times_standard'];    
    
            $dayIterator = getdate(current_time('timestamp'));
            $debugContent = '';
    
            $sortedArray = array();
            $daycnt = $dayIterator["wday"];
            while ($daycnt < 14 && sizeof($sortedArray) < 7)
            {                
                $dayNumber = $dayIterator["wday"];
                $timestamp = $dayIterator[0];
    
                //$debugContent.= '['.$options[$dayNumber].']';
                //$debugContent.= 'o='.$options[$dayNumber]['open'].'c='.$options[$dayNumber]['close'].'; '.PHP_EOL;
    
                $openTime = getDateWithTime($timestamp, $options[$dayNumber]['open']);
                $closeTime = getDateWithTime($timestamp, $options[$dayNumber]['close']);
    
                $sortedArray[$daycnt]["open"] = $openTime;
                $sortedArray[$daycnt]["close"] = $closeTime;
    
                //add 1 day to the date
                $dayIterator = getdate(strtotime('+1 day', $dayIterator[0]));
    
                $daycnt++;
            }
    
            $currentTimeLocal = current_time('timestamp');    
            //get the next opening time
    
            $isOpen = FALSE;   
            $nextOpeningTime = 'dd';
            //$debugContent.='time now is: '.$currentTimeLocal.' - ';
            foreach($sortedArray as $k=>$v)
            {                    
                $openTime = $v['open'];
                $closeTime = $v['close'];
    
    
                //check if we are open
                if($currentTimeLocal > $openTime && $currentTimeLocal < $closeTime)
                {
                    $isOpen = TRUE;
                    $nextOpeningTime = '';
                    break;
                }
    
                else if ($openTime > $currentTimeLocal && $openTime != $closeTime)
                {  
                    $nextOpeningTime = $v['open'];
                    break;
                }    
                else 
                {
                    $debugContent .="compared: " .$currentTimeLocal.' to '.$openTime.' and '.$closeTime.PHP_EOL;
                }
            }
            //$debugContent.= $nextOpeningTime;
    
            $pluginOptions = get_option( 'wppizza_otc_name' );
            $content ='nothing';
            if($isOpen)
            {
                $content = $pluginOptions['titleopen'];
            }
            else 
            {
                $closedText = $pluginOptions['titleclosed'];
                $dateText = formatDate($nextOpeningTime, $pluginOptions);
    
                if ((strpos($closedText, '%datestring%') !== false))
                {                    
                    $content = str_replace('%datestring%', $dateText, $closedText);                    
                }
                else 
                {
                    $content = $closedText . $dateText;
                }
            }
    
            return '<div class="pizza_opentimes_widget '.$arr['class'].'">'.$content.'</div>';
        }
    
    
        // Settings page
        class WPPIZZA_OTC_SETTINGSPAGE
        {    
            //Holds the values to be used in the fields callbacks
            private $options;   
    
            public function __construct()
            {
                add_action('admin_menu', array($this, 'add_plugin_page'));
                add_action('admin_init', array($this, 'page_init'));
            }
    
            //Add options page                
            public function add_plugin_page()
            {
                // This page will be under "Settings"
                add_options_page(
                    'WPPizza Openingtimes Shortcode', 
                    'WPPizza Openingtimes Shortcode', 
                    'manage_options', 
                    'wppizza-otc-options',  
                    array( $this, 'wppizza_otc_options_createpage' )
                );
            }
    
            // Options page callback             
            public function wppizza_otc_options_createpage()
            {
                // Set class property
                $this->options = get_option( 'wppizza_otc_name' );
                include 'inc/options.php';    //page with the layout
            }
    
            // Register and add settings             
            public function page_init()
            {        
                register_setting(
                    'wppiza_otc_group', // Option group
                    'wppizza_otc_name', // Option name
                    array( $this, 'sanitize' ) // Sanitize
                );
    
                add_settings_section(
                    'setting_section_id', // ID
                    'My Custom Settings', // Title
                    array( $this, 'print_section_info' ), // Callback
                    'wppizza-otc-options' // Page
                );  
    
                add_settings_field(
                    'titleopen', 
                    'Content to display when open', 
                    array( $this, 'titleopen_callback' ), 
                    'wppizza-otc-options', 
                    'setting_section_id'
                );      
                add_settings_field(
                    'titleclosed', 
                    'Title to display when closed <br /> (time till next opening will be automatically added after this)', 
                    array( $this, 'titleclosed_callback' ), 
                    'wppizza-otc-options', 
                    'setting_section_id'
                );   
                add_settings_field(
                    'translation_today', 
                    'What to display for the word \'Today\' <br />', 
                    array( $this, 'translation_today_callback' ), 
                    'wppizza-otc-options', 
                    'setting_section_id'
                ); 
            }
    
            /**
             * Sanitize each setting field as needed
             *
             * @param array $input Contains all settings fields as array keys
             */
            public function sanitize( $input )
            {
                $new_input = array();
    
                if(isset($input['titleopen']))
                    $new_input['titleopen'] = $input['titleopen'];
                if(isset($input['titleclosed']))
                    $new_input['titleclosed'] = $input['titleclosed'];
                if(isset($input['translation_today']))
                    $new_input['translation_today'] = $input['translation_today'];
    
                return $new_input;
            }
    
            //Print the Section text
            public function print_section_info()
            {
                print 'Enter your settings below. HTML is absolutely welcome.';
            }
    
            // Get the settings option array and print one of its values             
            public function titleopen_callback()
            {
                printf('<textarea type="text" id="titleopen" name="wppizza_otc_name[titleopen]">%s</textarea>',
                    isset( $this->options['titleopen'] ) ? esc_attr( $this->options['titleopen']) : '');
            }
    
            // Get the settings option array and print one of its values             
            public function titleclosed_callback()
            {
                printf('<textarea type="text" id="titleclosed" name="wppizza_otc_name[titleclosed]">%s</textarea>',
                    isset( $this->options['titleclosed'] ) ? esc_attr( $this->options['titleclosed']) : '');
            }

            // Get the settings option array and print one of its values             
            public function translation_today_callback()
            {
                printf('<textarea type="text" id="translation_today" name="wppizza_otc_name[translation_today]">%s</textarea>',
                    isset( $this->options['translation_today'] ) ? esc_attr( $this->options['translation_today']) : '');
            }
        }
    
        if (is_admin()) 
        {
            $my_settings_page = new WPPIZZA_OTC_SETTINGSPAGE();
        }
    
?>