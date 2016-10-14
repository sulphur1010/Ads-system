
<!--




Created the following db tables:
om_adverts (user_id, title, summary, img_url, daily_budget, ad_url,  ad_plan, status, date_created)--Major model
om_advert_daily_billing()
om_transactions()


 -->



<?php

//Get advert part.
function getAdPart($id, $part)
{

        $part = chop($part);
        $query = mysql_query("SELECT * FROM om_adverts WHERE 
                 id = '".mysql_real_escape_string($id)."' ") or die(mysql_error()); 
        $count = mysql_num_rows($query);
        $row = mysql_fetch_assoc($query);
        if($count == 1)
        {
            $content = $row[$part];
            return $content;
        }else{
            return FALSE;
        }
}

//He is authorized to manage advert.
function isAuthorizedAds($ad_id, $user_id)
{

        //check if contact exist
        if(getAdPart($ad_id, 'title'))
        {
                $advertiser_id = getAdPart($ad_id, 'user_id');

                if(getUserPart($user_id, 'level_access') >= 5){ return true; }
                elseif($advertiser_id == $user_id){ return true; }
                else{ return false; }

        }else{ return false; }
}

//List adverts randonly.
function listRandomAdverts()
{

        $status = 1;
        //Build final queries.
        $query = mysql_query("SELECT id FROM om_adverts WHERE
                 status = '".mysql_real_escape_string($status)."'
                 ORDER BY rand() Limit 3") or die(mysql_error());

        $count = mysql_num_rows($query);
        $row = mysql_fetch_assoc($query);

        if($count >= 1){
            do{
                $list[] = $row['id'];
            }while($row = mysql_fetch_assoc($query));
            return $list;
        }
        else{ return FALSE; }
}

//List all my ads
function listMyAds($user_id=0, $status=1, $page=1, $max_per_page=100)
{

    if(!is_numeric($page) && !is_numeric($max_per_page)){ return FALSE; }
    $x = ($page - 1) * $max_per_page;
    $y = $max_per_page;

    //Check if User ID is set
    if(is_numeric($user_id) && $user_id >= 1){
        $user_sqlstr = "user_id = '" . mysql_real_escape_string($user_id) . "' AND ";
    }else{ $user_sqlstr = ''; }

    $query = mysql_query("SELECT id FROM om_adverts WHERE
             $user_sqlstr
             status = '".mysql_real_escape_string($status)."'
             ORDER BY id DESC LIMIT $x,$y") or die(mysql_error());

    $query_2 = mysql_query("SELECT id FROM om_adverts WHERE
             $user_sqlstr
             status = '".mysql_real_escape_string($status)."'") or die(mysql_error());

        $count = mysql_num_rows($query);
        $count2 = mysql_num_rows($query_2);
        $row = mysql_fetch_assoc($query);

        if($count >= 1){
            do{
                $list['total'] = $count2;
                $list['list'][] = $row['id'];
            }while($row = mysql_fetch_assoc($query));
            return $list;
        }
        else{ return FALSE; }
}

//List ad daily billing stats.
function adDailyStats($ad_id, $page=1, $max_per_page=100)
{

    if(!is_numeric($page) && !is_numeric($max_per_page)){ return FALSE; }
    $x = ($page - 1) * $max_per_page;
    $y = $max_per_page;

    $query = mysql_query("SELECT id FROM om_advert_daily_billing WHERE
             advert_id = '".mysql_real_escape_string($ad_id)."'
             ORDER BY id DESC LIMIT $x,$y") or die(mysql_error());

    $query_2 = mysql_query("SELECT id FROM om_advert_daily_billing WHERE
             advert_id = '".mysql_real_escape_string($ad_id)."'") or die(mysql_error());

        $count = mysql_num_rows($query);
        $count2 = mysql_num_rows($query_2);
        $row = mysql_fetch_assoc($query);

        if($count >= 1){
            do{
                $list['total'] = $count2;
                $list['list'][] = $row['id'];
            }while($row = mysql_fetch_assoc($query));
            return $list;
        }
        else{ return FALSE; }
}

//Get advert stats part.
function getAdStatsPart($id, $part)
{

        $part = chop($part);
        $query = mysql_query("SELECT * FROM om_advert_daily_billing WHERE
                 id = '".mysql_real_escape_string($id)."' ") or die(mysql_error());
        $count = mysql_num_rows($query);
        $row = mysql_fetch_assoc($query);
        if($count == 1)
        {
            $content = $row[$part];
            return $content;
        }else{
            return FALSE;
        }
}

//Get current billing day
function getAdCurrentBillingDay($ad_id)
{

        $query = mysql_query("SELECT billing_day FROM om_advert_daily_billing WHERE
                 advert_id = '".mysql_real_escape_string($ad_id)."'
                 ORDER BY id DESC LIMIT 1") or die(mysql_error());
        $count = mysql_num_rows($query);
        $row = mysql_fetch_assoc($query);
        if($count == 1)
        {
            $content = $row['billing_day'];
            return $content;
        }else{
            return FALSE;
        }
}

//Get Current Billing Stat ID
function getAdCurrentStatsID($ad_id)
{

        $query = mysql_query("SELECT id FROM om_advert_daily_billing WHERE
                 advert_id = '".mysql_real_escape_string($ad_id)."'
                 ORDER BY id DESC LIMIT 1") or die(mysql_error());
        $count = mysql_num_rows($query);
        $row = mysql_fetch_assoc($query);
        if($count == 1)
        {
            $content = $row['id'];
            return $content;
        }else{
            return FALSE;
        }
}

//Get Ad transaction ID
function getAdTransactionID($ad_id)
{

        $component = "upghana_ad";
        $component_id = $ad_id;

        $query = mysql_query("SELECT id FROM om_transactions WHERE
                 component = '".mysql_real_escape_string($component)."' AND
                 component_id = '".mysql_real_escape_string($component_id)."'") or die(mysql_error());
        $count = mysql_num_rows($query);
        $row = mysql_fetch_assoc($query);
        if($count == 1)
        {
            $content = $row['id'];
            return $content;
        }else{
            return FALSE;
        }
}

//The ad billing function
function adBillingProcessor($ad_id, $cost_per_impression)
{
        $current_timestamp = time();
        $billing_day = date('d', $current_timestamp);
        $current_billing_day = getAdCurrentBillingDay($ad_id);
        $ad_owner_id = getAdPart($ad_id, 'user_id');

        //Check if there is still enough credit to enable this advert.
        $current_cash_balance = getAvailableCashBalance($ad_owner_id);
        if($current_cash_balance >= $cost_per_impression)
        {
                //check if daily budget has been reached.
                $daily_budget = getAdPart($ad_id, 'daily_budget');
                $stats_id = getAdCurrentStatsID($ad_id);
                $current_amount = getAdStatsPart($stats_id, 'amount');
                $current_impression = getAdStatsPart($stats_id, 'total_impression');
                //Check if we are still on the latest billing day or cycle.
                if($current_billing_day == $billing_day)
                {
                        //check if budget has been reached for the day.
                        if($current_amount >= $daily_budget){ //Budget reached
                                //stop rotating this ad as budget has been reached.
                                $rotation = 2;
                                $query = mysql_query("UPDATE om_adverts SET
                                         rotation = '".mysql_real_escape_string($rotation)."' WHERE
                                         id  = '".mysql_real_escape_string($ad_id)."'") or die(mysql_error());
                        }else{
                                //Budget not yet reached. Update billing records.
                                $new_amount = $current_amount + $cost_per_impression;
                                $new_impression = $current_impression + 1;
                                //Update records on database.
                                $query = mysql_query("UPDATE om_advert_daily_billing SET
                                         amount = '".mysql_real_escape_string($new_amount)."',
                                         total_impression = '".mysql_real_escape_string($new_impression)."' WHERE
                                         billing_day = '".mysql_real_escape_string($current_billing_day)."' AND
                                         advert_id  = '".mysql_real_escape_string($ad_id)."'") or die(mysql_error());
                                //Update Ad transaction
                                $trans_id = getAdTransactionID($ad_id);
                                $total_trans_amount = getTransactionPart($trans_id, 'amount') + $cost_per_impression;
                                $queryt = mysql_query("UPDATE om_transactions SET
                                         amount = '".mysql_real_escape_string($total_trans_amount)."' WHERE
                                         id  = '".mysql_real_escape_string($trans_id)."'") or die(mysql_error());
                        }

                }else{
                        //This should trigger a new billing cycle for all ads stopped.
                        updateStoppedAds(); //Resume ad rotation for ads.

                        //Create new billing cycle.
                        $total_impression = 1;
                        $total_clicks = 0;
                        $date_created = time();
                        $status = 1;
                        $ad_owner = getAdPart($ad_id, 'user_id');

                        $query = mysql_query("INSERT INTO om_advert_daily_billing
                                (advert_id, billing_day, amount, total_impression, total_clicks,
                                date_created, status, user_id)
                                 VALUES('".mysql_real_escape_string($ad_id)."',
                                  '".mysql_real_escape_string($billing_day)."',
                                  '".mysql_real_escape_string($cost_per_impression)."',
                                  '".mysql_real_escape_string($total_impression)."',
                                  '".mysql_real_escape_string($total_clicks)."',
                                  '".mysql_real_escape_string($date_created)."',
                                  '".mysql_real_escape_string($status)."',
                                  '".mysql_real_escape_string($ad_owner)."')")
                                  or die(mysql_error());

                        //Update Ad transaction
                        $trans_id = getAdTransactionID($ad_id);
                        $total_trans_amount = getTransactionPart($trans_id, 'amount') + $cost_per_impression;
                        $queryt = mysql_query("UPDATE om_transactions SET
                                 amount = '".mysql_real_escape_string($total_trans_amount)."' WHERE
                                 id  = '".mysql_real_escape_string($trans_id)."'") or die(mysql_error());

                }
        }else{
                //User has ran out of cash. Stop advert.
                $stop_ad = 2;
                $query = mysql_query("UPDATE om_adverts SET
                         status = '".mysql_real_escape_string($stop_ad)."' WHERE
                         id  = '".mysql_real_escape_string($ad_id)."'") or die(mysql_error());
                //Send user an email that his/her ad has stopped running on UpGhana
        }
}

//last ad ID
function getLastAdID($user_id, $date_created)
{

        $query = mysql_query("SELECT * FROM om_adverts WHERE
                 user_id = '".mysql_real_escape_string($user_id)."' AND
                 date_created = '".mysql_real_escape_string($date_created)."' ") or die(mysql_error());
        $count = mysql_num_rows($query);
        $row = mysql_fetch_assoc($query);
        if($count == 1)
        {
            $ad_id = $row['id'];
            return $ad_id;
        }else{
            return FALSE;
        }
}

//Ad Rotator
function adRotator($country_id, $max_per_page=3)
{

        //Select available Ads
        $rotation = 0; //available
        $status = 1; //Active
        $all_countries = 0;
        $query = mysql_query("SELECT id FROM om_adverts WHERE
                status = '".mysql_real_escape_string($status)."' AND
                rotation = '".mysql_real_escape_string($rotation)."' AND
                country_id = '".mysql_real_escape_string($country_id)."' OR
                country_id = '".mysql_real_escape_string($all_countries)."'
                ORDER BY last_display_date ASC LIMIT $max_per_page") or die(mysql_error());
        $count = mysql_num_rows($query);
        $row = mysql_fetch_assoc($query);

        if($count >= 1){
                do{
                    $list[] = $row['id'];
                }while($row = mysql_fetch_assoc($query));
                //Check if required advert is returned.
                if($count < $max_per_page)
                {
                        $balance_ad = $max_per_page - $count;
                        $rotation_2 = 1; //unavailable
                        $query_2 = mysql_query("SELECT id FROM om_adverts WHERE
                                status = '".mysql_real_escape_string($status)."' AND
                                rotation = '".mysql_real_escape_string($rotation_2)."' AND
                                country_id = '".mysql_real_escape_string($country_id)."' OR
                                country_id = '".mysql_real_escape_string($all_countries)."'
                                ORDER BY last_display_date ASC LIMIT $balance_ad") or die(mysql_error());
                        $count_2 = mysql_num_rows($query_2);
                        $row_2 = mysql_fetch_assoc($query_2);
                        if($count_2 >= 1){
                                do{
                                    $list[] = $row_2['id'];
                                }while($row_2 = mysql_fetch_assoc($query_2));
                        }
                        //Make every related ads available for next rotation
                        $rotation_3 = 0; //available
                        $budget_reached_status = 2;
                        $status = 1;
                        $query_3 = mysql_query("UPDATE om_adverts SET
                                   rotation = '".mysql_real_escape_string($rotation_3)."' WHERE
                                   status = '".mysql_real_escape_string($status)."' AND
                                   rotation != '".mysql_real_escape_string($budget_reached_status)."' AND
                                   country_id  = '".mysql_real_escape_string($country_id)."' OR
                                   country_id  = '".mysql_real_escape_string($all_countries)."'") or die(mysql_error());
                }
                return array_unique($list);
        }

        else{ //No ad found. Select from unavailable ads and make the rest available
                $rotation = 1; //unavailable
                $status = 1; //Active
                $all_countries = 0;
                $query = mysql_query("SELECT id FROM om_adverts WHERE
                        status = '".mysql_real_escape_string($status)."' AND
                        rotation = '".mysql_real_escape_string($rotation)."' AND
                        country_id = '".mysql_real_escape_string($country_id)."' OR
                        country_id = '".mysql_real_escape_string($all_countries)."'
                        ORDER BY last_display_date ASC LIMIT $max_per_page") or die(mysql_error());
                $count = mysql_num_rows($query);
                $row = mysql_fetch_assoc($query);
                if($count >= 1)
                {
                        do{
                            $list[] = $row['id'];
                        }while($row = mysql_fetch_assoc($query));
                        //Make every other ads available.
                        //Make every related ads available for next rotation
                        $rotation_3 = 0; //available
                        $budget_reached_status = 2;
                        $status = 1;
                        $query_3 = mysql_query("UPDATE om_adverts SET
                                   rotation = '".mysql_real_escape_string($rotation_3)."' WHERE
                                   status = '".mysql_real_escape_string($status)."' AND
                                   rotation != '".mysql_real_escape_string($budget_reached_status)."' AND
                                   country_id  = '".mysql_real_escape_string($country_id)."' OR
                                   country_id  = '".mysql_real_escape_string($all_countries)."'") or die(mysql_error());
                        return array_unique($list);
                }else{
                        return false;
                }
        }
}


//Update advert rotation and last display date.
function updateAdRotator($ad_id, $rotation)
{
        $last_display_date = time();
        $query = mysql_query("UPDATE om_adverts SET
               rotation = '".mysql_real_escape_string($rotation)."',
               last_display_date = '".mysql_real_escape_string($last_display_date)."' WHERE
               id  = '".mysql_real_escape_string($ad_id)."'") or die(mysql_error());
}

//Update all stopped adverts as a result of budget being reached.
function updateStoppedAds()
{

        $active = 1;
        $rotation = 2;

        $query = mysql_query("SELECT id FROM om_adverts WHERE
                 rotation = '".mysql_real_escape_string($rotation)."' AND
                 status = '".mysql_real_escape_string($active)."'
                 ORDER BY last_display_date ASC") or die(mysql_error());
        $count = mysql_num_rows($query);
        $row = mysql_fetch_assoc($query);

        if($count >= 1)
        {
                do{
                        $ad_id = $row['id'];
                        //check if this ad's current billing cycle was yesterday
                        $current_day = date('d', time());
                        $current_billing_day = getAdCurrentBillingDay($ad_id);
                        $ad_owner_id = getAdPart($ad_id, 'user_id');
                        if($current_day != $current_billing_day)
                        {
                                //Update ad rotation to available.
                                updateAdRotator($ad_id, 0);
                        }
                }while($row = mysql_fetch_assoc($query));
        }
}

//Total impression
function totalAdImpression($ad_id)
{
        $total_impression = 0;
        $query = mysql_query("SELECT total_impression FROM om_advert_daily_billing WHERE
                advert_id = '".mysql_real_escape_string($ad_id)."'") or die(mysql_error());
        $count = mysql_num_rows($query);
        $row = mysql_fetch_assoc($query);
        if($count >= 1)
        {
                do{
                        $impression = $row['total_impression'];
                        $total_impression = $total_impression + $impression;
                }while($row = mysql_fetch_assoc($query));
                return $total_impression;
        }else{ return 0; }
}

//Total clicks
function totalAdClicks($ad_id)
{
        $total_clicks = 0;
        $query = mysql_query("SELECT total_clicks FROM om_advert_daily_billing WHERE
                advert_id = '".mysql_real_escape_string($ad_id)."'") or die(mysql_error());
        $count = mysql_num_rows($query);
        $row = mysql_fetch_assoc($query);
        if($count >= 1)
        {
                do{
                        $clicks = $row['total_clicks'];
                        $total_clicks = $total_clicks + $clicks;
                }while($row = mysql_fetch_assoc($query));
                return $total_clicks;
        }else{ return 0; }
}

//Total amount spent
function totalAdSpending($ad_id)
{
        $total_spent = 0;
        $query = mysql_query("SELECT amount FROM om_advert_daily_billing WHERE
                advert_id = '".mysql_real_escape_string($ad_id)."'") or die(mysql_error());
        $count = mysql_num_rows($query);
        $row = mysql_fetch_assoc($query);
        if($count >= 1)
        {
                do{
                        $amount = $row['amount'];
                        $total_spent = $total_spent + $amount;
                }while($row = mysql_fetch_assoc($query));
                return $total_spent;
        }else{ return 0; }
}

//Count ads
function countAds($user_id=0, $status=1)
{


    //Check if User ID is set
    if(is_numeric($user_id) && $user_id >= 1){
        $user_sqlstr = "user_id = '" . mysql_real_escape_string($user_id) . "' AND ";
    }else{ $user_sqlstr = ''; }

    $query = mysql_query("SELECT id FROM om_adverts WHERE
             $user_sqlstr
             status = '".mysql_real_escape_string($status)."'") or die(mysql_error());

    $count = mysql_num_rows($query);
    $row = mysql_fetch_assoc($query);

    if($count >= 1){
        return $count;
    }
    else{ return 0; }
}


