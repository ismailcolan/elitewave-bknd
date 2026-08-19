<?php

function get_trans_table_name($date = '')
{
    global $getDatas;
    if ($date != '') {

        $count = strlen($date);
        $dats = $date;
        if ($count > 10) {
            $date = substr($dats, 0, 10);
        } else {
            $date = $dats;
        }
        $dates = trim($date, "`");
        $dt = (explode("-", $dates));
        $y = $dt[2];
        $m = $dt[1];
        if ($m <= 3)
            $m1 = 1;
        else if (($m >= 4) && ($m <= 6))
            $m1 = 2;
        else if (($m >= 7) && ($m <= 9))
            $m1 = 3;
        else
            $m1 = 4;

        $trans_name = "transaction_" . $m1 . "_" . $y;
        $trans_image_name = "transaction_images_" . $m1 . "_" . $y;
        $trans_invoice_name = "transaction_invoice_" . $m1 . "_" . $y;

        $table_name = array($trans_name, $trans_image_name, $trans_invoice_name);
        $table_main = array("transaction", "transaction_images", "transaction_invoice");
        $trans_tbl = $m1 . "_" . $y;
        for ($i = 0; $i < count($table_name); $i++) {
            $val = $getDatas->query("SHOW TABLES LIKE '$table_name[$i]'", 2);
            $count = count($val);
            if ($count == 0) {
                $db_creation = $getDatas->action_query("create table " . $table_name[$i] . " like " . $table_main[$i]);

                if ($i == 0) {
                    $val1 = $getDatas->query('SELECT * FROM transaction_tbls where table_name="' . $trans_tbl . '"', 2);
                    $count1 = count($val1);
                    if ($count1 == 0)
                        $db_name_store = $getDatas->action_query("insert into transaction_tbls(table_name,created_at) values ('$trans_tbl','$dates')");
                }
            }
        }
        return $table_name;
    } else {
        return false;
    }

}

function enc_name($name = '123')
{
    $enc = base64_encode(base64_encode(base64_encode(base64_encode('GraciousExpress') . ':$' . base64_encode($name) . ':$' . base64_encode('GraciousExpress'))));
    return $enc;
}

function dec_name($name = '')
{
    $enc2 = base64_decode(base64_decode(base64_decode($name)));
    $exp_arry = explode(':$', $enc2);
    $final_value = base64_decode($exp_arry[1]);
    return $final_value;
}


?>