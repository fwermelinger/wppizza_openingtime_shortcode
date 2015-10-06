<?php
    /**
        * Plugin Name: .WPPizza Customer Orders
        * Plugin URI: https://github.com/fwermelinger/wppizza_customer_orders
        * Description: Shows an overview of all email addresses that ever placed a successful order, and details about their orders.
        * Version: 0.1
        * Author: Florian Wermelinger
        * Author URI: http://www.webtopf.ch
        * License: GPL2
        */
    
        /*  Copyright 2014  Florian Wermelinger  (email : florian@webtopf.ch)
    
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
        global $wpaddrbook_dbversion;
        $wpaddrbook_dbversion = '1.0';
    
    
        //add_action( 'plugins_loaded', 'wppizza_extend_otc');
        register_uninstall_hook( __FILE__, 'wppizza_customer_orders_uninstall');
    
        /*************************************************************************
        *
        *
        *   [Shortcode Code]
        *
        *                
        **************************************************************************/
        // Settings page
        if ( ! class_exists( 'WPPIZZA_CO_CUSTOMERPAGE' ) )
        {
            class WPPIZZA_CO_CUSTOMERPAGE
            {    
                //Holds the values to be used in the fields callbacks
                private $options;   
    
                public function __construct()
                {
                    add_action('admin_menu', array($this, 'add_plugin_page'));
                    add_action('admin_init', array($this, 'page_init'));
                    if(!is_admin()){
                        add_action('init', array( $this, 'wppizza_co_wpml_localization'),99);
                    }
                }
    
                //Add options page                
                public function add_plugin_page()
                {
                    // This page will be under "Settings"
                    add_submenu_page(
                        'edit.php?post_type=wppizza',
                        'Customer Orders', 
                        '~ Customer Orders', 
                        'edit_posts', 
                        'wppizza-customer-orders',  
                        array( $this, 'wppizza_co_createpage' )                        
                    );
                }
    
                // Options page callback             
                public function wppizza_co_createpage()
                {
                    include 'inc/options.php';    //page with the layout
                }
    
                // Register and add settings             
                public function page_init()
                {        
                    
                    
                }
    
                
    
                

                /*******************************************************
                *
                *	[WPML : make localizations strings wpml compatible]
                *
                ******************************************************/
                function wppizza_otc_wpml_localization() {
                    require('inc/wpml.inc.php');
                }


                /*PART FOR NEW ADDRESSBOOK*/




                // function jal_install_data() {
                //     global $wpdb;

                //     $welcome_name = 'Mr. WordPress';
                //     $welcome_text = 'Congratulations, you just completed the installation!';

                //     $table_name = $wpdb->prefix . 'liveshoutbox';

                //     $wpdb->insert( 
                //         $table_name, 
                //         array( 
                //             'time' => current_time( 'mysql' ), 
                //             'name' => $welcome_name, 
                //             'text' => $welcome_text, 
                //             ) 
                //         );
                // }



            }
        }

        function wpadr_install() {
            global $wpdb;
            global $wpaddrbook_dbversion;

            $table_name = $wpdb->prefix . 'wppizza_addressbook';

            $charset_collate = $wpdb->get_charset_collate();

            $sql = "CREATE TABLE $table_name (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                datelastchange datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
                emailaddress tinytext NOT NULL,
                customeraddress text NOT NULL,                        
                UNIQUE KEY id (id)
                ) $charset_collate;";

            require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
            dbDelta( $sql );
            //echo $sql;

            add_option( 'wpaddrbook_dbversion', $wpaddrbook_dbversion );
        }

        function orderhistory_addressbook_callback() {
            global $wpdb; // this is how you get access to the database

            $email = $_POST['email'];
            $addrbook = $_POST['addressBookEntry'];

            //update this
            global $wpdb;
            $table_name = $wpdb->prefix . 'wppizza_addressbook';

            $data = array('customeraddress' => $addrbook, 'datelastchange' => current_time( 'mysql' ));
            $where = array('emailaddress'=>$email);
            $rowsaff = $wpdb->update($table_name, $data, $where);
            if ($rowsaff === false){
                status_header( 500 );
            }else{
                if ($rowsaff === 0){                
                    $combinedData = array('customeraddress' => $addrbook, 'datelastchange' => current_time( 'mysql' ), 'emailaddress'=> $email);
                    $resultInsert = $wpdb->insert($table_name, $combinedData);
                    if ($resultInsert === false){
                        status_header( 500 );
                                              
                    } else{
                        status_header( 201 );
                    }

                } else{
                    status_header( 202 );
                }
            }
            
            wp_die(); // this is required to terminate immediately and return a proper response
        }

        if (is_admin()) 
        {
           register_activation_hook( __FILE__, 'wpadr_install' );
           //register_activation_hook( __FILE__, 'jal_install_data' );
            $my_settings_page = new WPPIZZA_CO_CUSTOMERPAGE();

            //hook for our ajax addressbook call
            add_action( 'wp_ajax_orderhistory_addressbook', 'orderhistory_addressbook_callback' );
        }

?>