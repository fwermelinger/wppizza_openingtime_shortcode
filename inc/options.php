<?php
    
?>

<div class="wrap">  
    <h2>WPPizza Openingtimes Countdown Settings</h2>

    <form method="post" action="options.php">
        <?php            
            settings_fields( 'wppiza_otc_group' );
            do_settings_sections( 'wppizza-otc-options' );   
            submit_button(); 
        ?>    
    </form>
</div>