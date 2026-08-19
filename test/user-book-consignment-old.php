<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, minimal-ui">
		<title>Gracious Express - Book Consignment</title>

		<link href="favicon.png" type="image/x-icon" rel="shortcut icon">
		<link href="assets/css/master.css" rel="stylesheet">
		<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/animate.css@3.5.2/animate.min.css">
		<!-- book consignment css and js starts here -->
		<link rel="stylesheet" href="assets/css/book-consignment.css">
		<link rel="stylesheet" href="font-awesome-4.7.0/css/font-awesome.min.css">
		<!-- book consignment css and js finished here -->
		<script src="assets/plugins/jquery/jquery-1.11.3.min.js"></script>
		<script src="assets/js/modernizr.custom.js"></script>
	</head>


	<body style>


    <div class="user-dashboard" id="user-book-consignment">


       <?php include 'user-db-header.php' ?>

        <div class="user-book-consignment col-sm-12">
            <div class="ds-white-cover ">
                <!-- <h4 class="text-center">Mode Of Consignment</h4> -->
                <div class="parent-block ubc-parent-block">
                <div class="block" id="select-shipping" style="display:block">
							<h5>I Prefer Mode Of Shipping Through ...</h5>
							<div class="row">
								<div class="col-xs-2 col-sm-2 cust-padding-margin">
									<div class="sub-block" id="by-air">
										<span class="fa fa-plane custom-icon-size"></span>
										<!-- <p> &nbsp; </p> -->
										<h6>By Air</h6>
										<span style="display:none;">1</span>
										<i class="fa fa-check"></i>
									</div>
								</div>
								<div class="col-xs-2 col-sm-2 cust-padding-margin">
									<div class="sub-block" id="by-train">
										<span class="fa fa-subway custom-icon-size"></span>
										<!-- <p> &nbsp; </p> -->
										<h6>By Train</h6>
										<span style="display:none;">2</span>
										<i class="fa fa-check"></i>
									</div>
								</div>
								<div class="col-xs-2 col-sm-2 cust-padding-margin">
									<div class="sub-block"  id="by-road-surface">
										<span class="fa fa-road custom-icon-size"></span>
										<h6>By Road</h6>
										<p class="text-center">Surface</p>
										<span style="display:none;">3</span>
										<i class="fa fa-check"></i>
									</div>
								</div>
								<div class="col-xs-2 col-sm-2 cust-padding-margin">
									<div class="sub-block" id="by-road-express">
										<span class="fa fa-truck custom-icon-size"></span>
										<h6>By Road</h6>
										<p class="text-center">Express</p>
										<span style="display:none;">4</span>
										<i class="fa fa-check"></i>
									</div>
								</div>
								<div class="col-xs-2 col-sm-2 cust-padding-margin">
									<div class="sub-block" id="by-local">
										<span class="fa fa-motorcycle custom-icon-size"></span>
										<!-- <p class="text-center">&nbsp;</p> -->
										<h6>Local Delivery</h6>
										<span style="display:none;">5</span>
										<i class="fa fa-check"></i>
									</div>
								</div>
							</div>			
						</div>

                    <div class="block" id="select-payment-mode">
                        <h5>I Wish To Pay By...</h5>
                        <div class="row">
                        <div class="col-xs-3 col-sm-3 cust-padding-margin">
									<div class="sub-block" id="to-billed">
										<h6>To Billed</h6>
										<p class="text-center">By Sender</p>
										<i class="fa fa-check"></i>
									</div>
								</div>
								<div class="col-xs-3 col-sm-3 cust-padding-margin">
									<div class="sub-block" id="to-pay">
										<h6>To Pay</h6>
										<p class="text-center">By Receiver</p>
										<i class="fa fa-check"></i>
									</div>
								</div>
								<div class="col-xs-3 col-sm-3 cust-padding-margin">
									<div class="sub-block" id="cod">
										<h6>Cash On </h6>
										<h6 class="text-center"><strong>Delivery</strong></h6>
										<i class="fa fa-check"></i>
									</div>
								</div>
                            <div class="col-xs-3 col-sm-3 cust-padding-margin">
                                <div class="sub-block" id="in-advance">
                                    <h6>Paid</h6>
                                    <p class="text-center">In Advance</p>
                                    <i class="fa fa-check"></i>
                                </div>
                            </div>
                        </div>			
                    </div>
                    
                    <div class="block" id="select-sender-dtl">
                        <h5>Consignee / Package Informations.</h5>
                        <div class="row">
                            <div class="col-sm-12 cust-padding-margin">
                                <div class="sub-block">
                                    <form id="user-booking">

                                        <div id="sender-details">
                                            <div class="details-hdr">
                                                <!-- <span class="far fa-address-card"></span> -->
                                                <h5>Consignee Details</h5>
                                            </div>
                                            <div class="send-rcv-dtl">
                                                <div class="form-group">
                                                    <label for="sender-name">Select Consignee</label>
                                                    <input type="text" name="sel-consignee" id="sel-consignee" class="form-control"/>
                                                    <!-- <select class="form-control" nam="sel-consignee" id="sel-consignee" >
                                                        <option value="">Select Consigne</option>
                                                        <option value="1">Leather B Unit</option>
                                                        <option value="2">Forward Shoes - Gurgaon</option>
                                                        <option value="3">Metro Exports</option>
                                                        <option value="4">Farida & Groups</option>
                                                    </select> -->
                                                </div>
                                                <div class="hide" id="consignee-address" style="display:none">
                                                    <table class="table mb-0">
                                                        <tbody>
                                                        <tr>
                                                            <td>Address 1</td>
                                                            <td id="consignor_name">13, Patel Yousuf Sahib Street</td>
                                                        </tr>
                                                        <tr>
                                                            <td>Address 2</td>
                                                            <td id="consignor_contact">R.N.Palayam</td>
                                                        </tr>
                                                       <!--  <tr>
                                                            <td>State</td>
                                                            <td>TamilNadu</td>
                                                        </tr>
                                                        <tr>
                                                            <td>City</td>
                                                            <td>Vellore</td>
                                                        </tr>
                                                        <tr>
                                                            <td>PinCode</td>
                                                            <td>632001</td>
                                                        </tr>
                                                        <tr>
                                                            <td>GST</td>
                                                            <td>19AFFA1852R1ZZ</td>
                                                        </tr> -->
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>	
                                        </div>

                                        <div id="reciever-details" class="disabled">
                                            <div class="details-hdr">
                                                <!-- <span class="far fa-address-card"></span> -->
                                                <h5>Package Details</h5>
                                            </div>
                                            <div class="send-rcv-dtl ">
                                                <div id="package-info1" class="package-info">
                                                    <div class="form-group col-sm-3">
                                                        <label for="reciever-name">No Of Packages</label>
                                                        <input type="text" name="package-qty[]" class="form-control" id="package-qty[]">
                                                    </div>
                                                    <div class="form-group col-sm-3">
                                                        <label for="reciever-contact-no">Type Of Package</label>
                                                        <select class="form-control" name="sel1[]" id="sel1[]">
                                                            <option value="1">COTTON BOX</option>
                                                            <option value="1">Poly Bag</option>
                                                            <option value="1">Roll</option>
                                                            <option value="1">Sheet</option>
                                                            <option value="1">Bundle</option>
                                                            <option value="1">Cover</option>
                                                            <option value="1">Poly Bundle</option>
                                                            <option value="1">Can</option>
                                                            <option value="1">Box</option>
                                                            <option value="1">Mould</option>
                                                            <option value="1">Packet</option>
                                                            <option value="1">Cess</option>
                                                            <option value="1">CAT</option>
                                                            <option value="1">Gross Roll</option>
                                                            <option value="1">Poly Roll</option>
                                                        </select>
                                                    </div>
                                                    <div class="form-group col-sm-3">
                                                        <label for="reciever-email">Invoice No</label>
                                                        <input type="email" class="form-control" name="invoice-no[]" id="invoice-no[]">
                                                    </div>
                                                    <div class="form-group col-sm-3">
                                                        <label for="reciever-city">Said To Contents</label>
                                                        <input type="text" class="form-control" name="said-to-contents[]" id="said-to-contents[]">
                                                    </div>
                                                    <div class="form-group col-sm-3">
                                                        <label for="reciever-address">Quantity </label>
                                                        <input type="text" class="form-control" name="qty[]" id="qty[]">
                                                    </div>
                                                    <div class="form-group col-sm-3">
                                                        <label for="reciever-area">Gross Wt.(Kgs)</label>
                                                        <input type="text" class="form-control" name="gross-weight[]" id="gross-weight[]">
                                                    </div>
                                                    <div class="form-group col-sm-3">
                                                        <label for="reciever-area">Charged Wt.(Kgs)</label>
                                                        <input type="text" class="form-control charged-weight"  name="charged-weight[]" id="charged-weight[]">
                                                    </div>
                                                    <!-- <div class="form-group col-sm-3">
                                                        <label>&nbsp;</label>
                                                        <a class="btn btn-danger" onclick="DelDiv(this)"><span class="fa fa-trash-o" aria-hidden="true"></span></a>
                                                    </div> -->
                                                </div>
                                                <div class="col-sm-12 text-right package-info-add-del-btns">
                                                    <a class="btn btn-primary" onclick="CloneDiv()"> <span class="fa fa-plus" aria-hidden="true"></span> Add Row</a>
                                                    <a class="btn btn-danger disabled" onclick="DelDiv()"> <span class="fa fa-trash-o" aria-hidden="true"></span> Del Row</a>
                                                </div>
                                            </div>
                                           
                                        </div>
                                        
                                        <div id="supporting-document" class="disabled">
                                            <div class="details-hdr">
                                                <!-- <span class="far fa-address-card"></span> -->
                                                <input class="form-check-input" type="checkbox" value="" id="volum-info">
                                                <h5>Volumetric Information If Any (in cms)</h5> 
                                            </div>
                                            <div class="send-rcv-dtl disabled" >
                                                <div id="volumetric-info1" class="volumetric-info">
                                                    <div class="volumetric-input-boxes">
														<input type="number" placeholder="length" class="form-control" id="length" ><span>X</span>
														<input type="number" placeholder="width" class="form-control" id="width" ><span>X</span>
														<input type="number" placeholder="height" class="form-control" id="height" > <span>X</span>
                                                        <input type="number" placeholder="Qty" class="form-control" id="quantity"> <span>=</span>
                                                        <input type="number" placeholder="Weight" class="form-control" id="weight"> 
                                                    </div>
                                                </div>
                                                <div class="col-sm-12 text-right">
                                                    <a class="btn btn-primary" onclick="CloneVolumDiv()"> <span class="fa fa-plus" aria-hidden="true"></span> Add More</a>
                                                    <a class="btn btn-danger disabled" onclick="DelVolumDiv()"> <span class="fa fa-trash-o" aria-hidden="true"></span> Del Row</a>
                                                </div>
                                            </div>
                                            
                                        </div>


                                        <div id="Attachements" class="disabled">
                                            <div class="details-hdr">
                                                <h5>Attachements</h5> 
                                            </div>
                                            <div class="send-rcv-dtl " >
                                                <div id="image-uploader1" class="image-uploader">
                                                    <div class="box">
                                                        <div class="avatar-upload">
                                                            <div class="avatar-edit">
                                                                <input type='file' id="imageUpload" accept=".png, .jpg, .jpeg" />
                                                                <label for="imageUpload"></label>
                                                            </div>
                                                            <div class="avatar-preview">
                                                                <div id="imagePreview" style="background-image: url('images/preview.png');">
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="box">
                                                        <div class="avatar-upload-g">
                                                            <div class="avatar-edit">
                                                                <input type='file' id="imagdpload"  />
                                                                <label for="imageUpload"></label>
                                                            </div>
                                                            <div class="avatar-preview">
                                                                <div id="imagePreview" style="background-image: url('images/doc.png');">
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                   </div>
                                                   <div class="col-sm-12 text-right">
                                                        <a class="btn btn-primary" onclick="CloneAttaDiv()"> <span class="fa fa-plus" aria-hidden="true"></span> Add More</a>
                                                        <a class="btn btn-danger disabled" onclick="DelAttaDiv()"> <span class="fa fa-trash-o" aria-hidden="true"></span> Del Row</a>
                                                    </div>
                                                    <!-- <div class="col-sm-12 text-center" style="margin-top : 10px">
                                                        <a class="btn btn-primary" onclick="upload()">Upload</a>
                                                    </div> -->
                                            </div>
                                           
                                        </div>

                                        <div id="payment-info" class="disabled">
                                            <div class="details-hdr">
                                                <h5>Payment Information</h5> 
                                            </div>
                                            <div class="send-rcv-dtl " >
                                            
                                                <table class="table mb-0">
                                                    <thead>
                                                        <th>Particulars</th>
                                                        <th class="text-center">Amount</th>
                                                    </thead>
                                                    <tbody>
                                                    <tr>
                                                        <td>Freight</td>
                                                        <td class="freight">
                                                            <div class="freight-wgt"><input type="number" class="form-control" placeholder="Weight"></div>
                                                            <div class="freight-span"><span>X</span></div>
                                                            <div class="freight-rate"><input type="number" class="form-control" placeholder="Rate"></div>
                                                            <div class="freight-span"><span>=</span></div>
                                                            <div class="freight-amt"><input type="number" class="form-control" placeholder="Amount"></div>
                                                        </td>
                                                        
                                                    </tr>
                                                    <tr>
                                                        <td>Doc Charges</td>
                                                        <td class="payment-info-cust-inp"><input type="number" class="form-control"></td>
                                                    </tr>
                                                    <tr>
                                                        <td>Labour Charges</td>
                                                        <td class="payment-info-cust-inp"><input type="number" class="form-control"></td>
                                                    </tr>
                                                    <tr>
                                                        <td>Any Other</td>
                                                        <td class="payment-info-cust-inp"><input type="number" class="form-control"></td>
                                                    </tr>
                                                    <tr>
                                                        <td>G.S.T (18 %)</td>
                                                        <td class="payment-info-cust-inp"><input type="number" class="form-control"></td>
                                                    </tr>
                                                    <tr>
                                                        <td><strong>Total</strong></td>
                                                        <td class="payment-info-cust-inp"><input type="number" class="form-control"></td>
                                                    </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>

                                        <div id="declaration" class="disabled">
                                            <div class="details-hdr">
                                                <h5>Declaration</h5> 
                                         