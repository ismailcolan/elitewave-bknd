 //Auto Calculation Part

	        //GST Rate Based on Mode of Transport
	        function handleSelectChange(event) {
	            var value = event.target.value;

	            if (value === '1' || value === '2' || value === '3') {
	                gst = 18;
	            } else {
	                gst = 12;
	            }
	            if (!isNaN(gst)) {
	                $('#gst_rate').val(gst);
                    sum_amount();
	            }

	        }
	        //End GST Part

	        //Charge Weight Part
	        function calculate_charge_weight() {

	            var titles = $('input[name^=charged]').map(function(idx, elem) {
	                return $(elem).val();
	            }).get();

	            var res = titles.map(function(x) {
	                return parseInt(x);
	            });

	            var unique_weight = res.filter(function(value) {
	                return !Number.isNaN(value);
	            });
	            var total = 0;

	            for (let i = 0; i < unique_weight.length; i++) {
	                if (isNaN(unique_weight[i])) {
	                    total = total + 0;

	                } else {

	                    total = total + unique_weight[i];
	                }
	            }
	            //console.log(total);


	            if (!isNaN(total)) {
	                $('#cumulative_charged').val(total);

	            }
	            // ss();
	            calc_charge_amt();
	        }

	        //End Charge Weight Part

	        //AddZero Function    
	        function addZeroes(num) {
	            var num = Number(num);
	            if (String(num).split(".").length < 2 || String(num).split(".")[1].length <= 2) {
	                num = num.toFixed(2);
	            }
	            return num;
	        }
	        //End AddZero Function   

	        //Fov Calculation 
	        function fov_calc() {
	            var fov = 0.2;
	            var goods_val = $("#goods_dedared_value").val()
	            fov_chrge = (fov / 100) * goods_val;
	            if (!isNaN(fov_chrge)) {
	                $("#fov_amount").val(addZeroes(fov_chrge));

	                sum_amount();
	            }

	        }

	        //End Fov

	        //Calculate Amount
	        function calc_charge_amt() {
                //alert("tr");
	            var charge_weight = $('#cumulative_charged').val();

	            var rate = $("#frieght_rate").val();

	            var total_amt = parseInt(rate) * parseInt(charge_weight);
	            //console.log("total",total_amt);

	            if (!isNaN(total_amt)) {
	                $('#frieght_amount').val(addZeroes(total_amt));
	                // $('#frieght_amount').keypress();
	                sum_amount()
	            }
	        }


	        //End Calculate Amount


	        //Sum Amount
	        function sum_amount() {

	            var fright_amt = $('#frieght_amount').val() ? $('#frieght_amount').val() : 0;
	            var l = $('#loading_unload_chrg').val() ? $('#loading_unload_chrg').val() : 0;
	            console.log(l);
	            var cr = $('#crane_forklift_chrg').val() ? $('#crane_forklift_chrg').val() : 0;
	            var cod = $('#cod_amount').val() ? $('#cod_amount').val() : 0;
	            var fov = $('#fov_amount').val() ? $('#fov_amount').val() : 0;
	            var dc = $('#doc_amount').val() ? $('#doc_amount').val() : 0;
	            var cartge = $('#cartage_amount').val() ? $('#cartage_amount').val() : 0;
	            var lc = $('#labour_amount').val() ? $('#labour_amount').val() : 0;
	            var oc = $('#other_amount').val() ? $('#other_amount').val() : 0;
	            var gst_rate = $("#gst_rate").val();

	            // console.log("f_amount " + fright_amt +" l: " + l + "cr " + cr + " dc " + dc + " lc " + lc + " cartge " + cartge + " gst_rate " + gst_rate + "fov " + fov + "oc " + oc + "cod " + cod) ;

	            var totals = 0;
	            //if (fov != '')
	            var totals = parseFloat(fright_amt) + parseFloat(l) + parseFloat(cr) + parseFloat(cod) + parseFloat(fov) + parseFloat(dc) + parseFloat(cartge) + parseFloat(lc) + parseFloat(oc);
	            // else
	            // var totals = parseFloat(fright_amt);

	            //console.log(totals,"tt");

	            var gsts = (gst_rate / 100) * totals;
	            // console.log(gsts);

	            if (!isNaN(gsts)) {
	                var gst1 = $("#gst_amount").val(addZeroes(gsts.toFixed(2)));
	            }
	            //console.log(gst1)
	            // //addZeroes(totals_pay.toFixed(0))

	            var totals_pay = parseFloat(gsts) + parseFloat(totals);


	            if (!isNaN(totals_pay)) {
	                //console.log(totals_pay);
	                $("#total").val(addZeroes(totals_pay.toFixed(0)));
	                get_total();
	                //console.log(addZeroes(totals_pay));
	            }
	        }

	        //End Sum Amount


	        // Payment in Words

	        function get_total() {
	            let sum = $('#total').val();
	            //alert(sum);
	            $.ajax({
	                url: '../fetch_details.php',
	                type: "post",
	                data: {
	                    cmd: "get_amount_words",
	                    val: sum
	                },
	                success: function(result) {
	                    console.log(result);
	                    $('#amount_in_words').val(result);
	                },
	                error: function(jqxhr) {
	                    //alert(jqxhr.responseText);
	                }
	            });
	        }


	        //Auto Calculation Part End