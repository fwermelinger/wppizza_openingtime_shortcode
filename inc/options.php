<?php
global $wpdb;    

$txtnumber = 0;
$ddlnumbertypefilter = 'more';
$emailfilter = $_POST['emailfilter'];
if (isset($_POST['txtnumber'])){
    $txtnumber = $_POST['txtnumber'];    
}
if (isset($_POST['ddlnumbertypefilter'])){
    $ddlnumbertypefilter = $_POST['ddlnumbertypefilter'];
}
?>

<div class="wrap">  
    <h1>WPPizza Customer Orders</h1>    
    <form method="post" action="edit.php?post_type=wppizza&amp;page=wppizza-customer-orders">      
        <label for="emailfilter">Search email addresses (divide with ,)</label>
        <br>
        <textarea id="emailfilter" name="emailfilter"><?php echo $emailfilter ?></textarea>
        <br>
        <br>
        <label for='ddlnumbertypefilter'>With </label>
        <select name="ddlnumbertypefilter" id="ddlnumbertypefilter">
            <option value="more" <?php echo ($ddlnumbertypefilter == 'more' ? "selected":"") ?>>more than</option>
            <option value="less" <?php echo ($ddlnumbertypefilter == 'less' ? "selected":"") ?>>less than</option>
            <option value="exact" <?php echo ($ddlnumbertypefilter == 'exact' ? "selected":"") ?>>exactly</option>
        </select>
        <input style="width:40px;" type="text" name="txtnumber" id="txtnumber" value="<?php echo $txtnumber ?>">
        <span> orders</span><br>
        <button type="submit">Search</button>
    </form>
    <?php

    // Set class property
    $this->options = get_option( 'wppizza_co_name' );
    

    $query = "select wp_user_id,order_ini,customer_ini,transaction_id,order_date from ". $wpdb->prefix . "wppizza_orders WHERE payment_status = 'COMPLETED' ";        
    $results = $wpdb->get_results( $query );    
    
    
    //lets create our grouped object
    $emailaddresses = array();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {

        if (strlen($emailfilter) > 0){
            $emailfilter = str_replace(' ', ',', $emailfilter);
            $emailfilter = str_replace(PHP_EOL, ',', $emailfilter);
            $emailfilter = explode(',',$emailfilter);
        }
        else{
            $emailfilter = array();
        }        

        foreach($results as $order){
            $customerdata = unserialize($order->customer_ini);
            $orderdata = unserialize($order->order_ini);
            $key = trim(strtolower($customerdata["cemail"]));

            //this is our new object for every order
            $newordervalue = array(
                "transaction_id" => $order->transaction_id,
                "ordertotal" => $orderdata["total"],
                "deliveryfee" => $orderdata["delivery_charges"], 
                "deliveryarea" => $customerdata["wppizza-dbp-area"],            
                "orderdate" => $order->order_date,            
                "name" => $customerdata["cname"],
                "tel" => $customerdata["ctel"],
                "address" => $customerdata["caddress"], 
                "loggedin" => $order->wp_user_id > 0 ? 'yes' : 'no'
                );       

            if ( count($emailfilter) == 0 || in_array($key, $emailfilter)){
                $emailaddresses[$key][] = $newordervalue;
            }
        }

        //take all out that are not the expected number
        foreach($emailaddresses as $email=>$orders){
            if ($ddlnumbertypefilter == 'exact' && $txtnumber != count($orders)){
                unset($emailaddresses[$email]) ;      
            } else if ($ddlnumbertypefilter == 'more' && count($orders) <= $txtnumber ){
                unset($emailaddresses[$email]) ;
            } else if ($ddlnumbertypefilter == 'less' && count($orders) > $txtnumber ){
                unset($emailaddresses[$email]) ;
            }
        }
        
        

        ksort($emailaddresses);
        ?>
        <h3>
            <?php echo count($results) ?> 
            <span>Orders were scanned, </span>
            <?php echo count($emailaddresses) ?> 
            <span>different email addresses found</span>
        </h3>
        
        <table class="">
            <?php
        }
        $counter = 0;
        foreach($emailaddresses as $email=>$orders) {
            $counter = $counter +1;
            $lastorderIndex = count($orders) - 1;
            $cleanPhoneNumber = preg_replace("/[^0-9,.]/", "", $orders[$lastorderIndex]['tel']);
            $customerName = $orders[$lastorderIndex]['name'];
            ?>

            <tr>
                <td style="vertical-align: top; width: 250px;"><?php echo $email ?></td>
                <td style="vertical-align: top; width: 250px;"><?php echo $customerName ?></td>
                <td style="vertical-align: top; width: 250px;"><?php echo $cleanPhoneNumber ?></td>
                <td style="text-align: left;">
                    <a href="" onclick="jQuery('#email<?php echo $counter ?>').toggle(); return false;"> <?php echo count($orders) ?> order(s)</a>                
                </td>                                                            
            </tr>
            <tr>
                <td colspan="4">
                 <table style="display: none;" id="email<?php echo $counter ?>">
                    <tr>                        
                        <td><strong>Order #</strong></td>
                        <td><strong>Price</strong></td>
                        <td><strong>Fee</strong></td>
                        <td><strong>Area</strong></td>
                        <td><strong>Date</strong></td>
                        <td><strong>Name</strong></td>
                        <td><strong>Phone</strong></td>
                        <td><strong>Address</strong></td>
                        <td><strong>Registered</strong></td>        
                    </tr>                
                    <?php
                    $totalorder = 0;
                    foreach($orders as $order){
                        $totalorder += $order["ordertotal"];
                        ?>                       
                        <tr>
                            <td>
                                <?php echo implode('</td><td>', $order) ?>
                            </td>
                        </tr>
                        <?php
                    }                    
                    ?>
                    <tr>
                        <td colspan="1"><strong>Total</strong></td><td colspan="3"><strong><?php echo $totalorder ?></strong></td>
                    </tr>
                </table>    
            </td>
        </tr>
        <?php   
    }


    ?>
</table>

</div>