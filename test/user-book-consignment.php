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
		<link rel="stylesheet" href="f5/css/all.css">
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
                    <div class="block" id="select-shipping" style="display : block">
                        <h5>I Prefer Mode Of Shipping Through ...</h5>
                        <div class="row">
                            <div class="col-xs-2 col-sm-2 cust-padding-margin">
                                <div class="sub-block">
                                    <span class="fas fa-plane custom-icon-size"></span>
                                    <!-- <p> &nbsp; </p> -->
                                    <h6>By Air</h6>
                                    <i class="fas fa-check"></i>
                                </div>
                            </div>
                            <div class="col-xs-2 col-sm-2 cust-padding-margin">
                                <div class="sub-block">
                                    <span class="fas fa-subway custom-icon-size"></span>
                                    <!-- <p> &nbsp; </p> -->
                                    <h6>By Train</h6>
                                    <i class="fas fa-check"></i>
                                </div>
                            </div>
                            <div class="col-xs-2 col-sm-2 cust-padding-margin">
                                <div class="sub-block">
                                    <span class="fas fa-road custom-icon-size"></span>
                                    <h6>By Road</h6>
                                    <p class="text-center">Surface</p>
                                    <i class="fas fa-check"></i>
                                </div>
                            </div>
                            <div class="col-xs-2 col-sm-2 cust-padding-margin" id="express">
                                <div class="sub-block">
                                    <span class="fas fa-truck-moving custom-icon-size"></span>
                                    <h6>By Express</h6>
                                    <i class="fas fa-check"></i>
                                </div>
                            </div>
                            <div class="col-xs-2 col-sm-2 cust-padding-margin">
                                <div class="sub-block">
                                    <span class="fas fa-truck custom-icon-size"></span>
                                    <!-- <p class="text-center">&nbsp;</p> -->
                                    <h6>Local Delivery</h6>
                                    <i class="fas fa-check"></i>
                                </div>
                            </div>
                        </div>			
                    </div>

                    <div class="block" id="select-load">
                        <h5>Select Load Type ...</h5>
                        <div class="row">
                            <div class="col-xs-6 col-sm-6 cust-padding-margin" id="full-truck">
                                <div class="sub-block">
                                    <h6>Full Truck Load</h6>
                                    <i class="fas fa-check"></i>
                                </div>
                            </div>
                            <div class="col-xs-6 col-sm-6 cust-padding-margin" id="partial-truck">
                                <div class="sub-block">
                                    <h6>Partial Truck Load</h6>
                                    <i class="fas fa-check"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="block" id="select-truck">
                        <h5>Select Truck Type ...</h5>
                        <div class="row">
                            <div class="col-xs-6 col-sm-4 cust-padding-margin">
                                <div class="sub-block">
                                    <h6>Single Axle Vehicle: 07MT</h6>
                                    <small class="text-center">32ft L * 8ft W * 9.5ft H = 65CBM</small>
                                    <i class="fas fa-check"></i>
                                </div>
                            </div>
                            <div class="col-xs-6 col-sm-4 cust-padding-margin">
                                <div class="sub-block">
                                    <h6>Multi Axle Vehicle : 10MT/14MT/17MT</h6>
                                    <small class="text-center">32ft L * 8ft W * 9.5ft H = 65CBM</small>
                                    <i class="fas fa-check"></i>
                                </div>
                            </div>
                            <div class="col-xs-6 col-sm-4 cust-padding-margin">
                                <div class="sub-block">
                                    <h6>22ft Vehicle : 07MT</h6>
                                    <small class="text-center">22ft L * 8ft W * 8ft H = 38CBM</small>
                                    <i class="fas fa-check"></i>
                                </div>
                            </div>
                            <div class="col-xs-6 col-sm-4 cust-padding-margin">
                                <div class="sub-block">
                                    <h6>18ft Vehicle : 06MT</h6>
                                    <small class="text-center">18ft L * 8ft W * 8ft H = 31CBM</small>
                                    <i class="fas fa-check"></i>
                                </div>
                            </div>
                            <div class="col-xs-6 col-sm-4 cust-padding-margin">
                                <div class="sub-block">
                                    <h6>Eicher 19 Vehicle : 7MT/8MT/9MT</h6>
                                    <small class="text-center">19ft L * 7ft W * 7ft H = 25CBM</small>
                                    <i class="fas fa-check"></i>
                                </div>
                            </div>
                            <div class="col-xs-6 col-sm-4 cust-padding-margin">
                                <div class="sub-block">
                                    <h6>Eicher 17 Vehicle : 5MT</h6>
                                    <small class="text-center">17ft L * 6ft W * 7ft H = 19CBM</small>
                                    <i class="fas fa-check"></i>
                                </div>
                            </div>
                            <div class="col-xs-6 col-sm-4 cust-padding-margin">
                                <div class="sub-block">
                                    <h6>Eicher 14 Vehicle : 4MT</h6>
                                    <small class="text-center">14ft L * 6ft W * 6.5ft H = 19CBM</small>
                                    <i class="fas fa-check"></i>
                                </div>
                            </div>
                            <div class="col-xs-6 col-sm-4 cust-padding-margin">
                                <div class="sub-block">
                                    <h6>Tata 407 Vehicle : 2.5MT</h6>
                                    <small class="text-center">9ft L * 5.5ft W * 5.5ft H = 7.35CBM</small>
                                    <i class="fas fa-check"></i>
                                </div>
                            </div>
                            <div class="col-xs-6 col-sm-4 cust-padding-margin">
                                <div class="sub-block">
                                    <h6>Mahendra Bolero Vehicle : 1.5MT</h6>
                                    <small class="text-center">8ft L * 4.8ft W * 4.8ft H = 5CBM</small>
                                    <i class="fas fa-check"></i>
                                </div>
                            </div>
                            <div class="col-xs-6 col-sm-6 cust-padding-margin">
                                <div class="sub-block">
                                    <h6>Tata Dost Vehicle : 1MT</h6>
                                    <small class="text-center">7ft L * 4.8ft W * 4.8ft H = 4CBM</small>
                                    <i class="fas fa-check"></i>
                                </div>
                            </div>
                            <div class="col-xs-12 col-sm-6 cust-padding-margin">
                                <div class="sub-block">
                                    <h6>Tata Ace Vehicle : 850Kgs</h6>
                                    <small class="text-center">7ft L * 4.8ft W * 4.8ft H = 4CBM</small>
                                    <i class="fas fa-check"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="block" id="select-payment-mode">
                        <h5>I Wish To Pay By...</h5>
                        <div class="row">
                            <div class="col-xs-3 col-sm-3 cust-padding-margin">
                                <div class="sub-block">
                                    <h6>To Billed</h6>
                                    <p class="text-center">By Sender</p>
                                    <i class="fas fa-check"></i>
                                </div>
                            </div>
                            <div class="col-xs-3 col-sm-3 cust-padding-margin">
                                <div class="sub-block">
                                    <h6>To Pay</h6>
                                    <p class="text-center">By Receiver</p>
                                    <i class="fas fa-check"></i>
                                </div>
                            </div>
                            <div class="col-xs-3 col-sm-3 cust-padding-margin">
                                <div class="sub-block">
                                    <h6>Cash On </h6>
                                    <h6 class="text-center"><strong>Delivery</strong></h6>
                                    <i class="fas fa-check"></i>
                                </div>
                            </div>
                            <div class="col-xs-3 col-sm-3 cust-padding-margin">
                                <div class="sub-block">
                                    <h6>Paid</h6>
                                    <p class="text-center">In Advance</p>
                                    <i class="fas fa-check"></i>
                                </div>
                            </div>
                        </div>			
                    </div>
                    
                    <div class="block" id="select-sender-dtl">
                        <h5>Consignee / Package Informations.</h5>
                        <div class="row">
                            <div class="col-sm-12 cust-padding-margin">
                                <div class="sub-block">
                                    <form>

                                        <div id="sender-details">
                                            <div class="details-hdr">
                                                <!-- <span class="far fa-address-card"></span> -->
                                                <h5>Consignee Details</h5>
                                            </div>
                                            <div class="send-rcv-dtl">
                                                <div class="form-group">
                                                    <label for="sender-name">Select Consignee</label>
                                                    <select class="form-control" id="sel-consignee" >
                                                        <option>Select Consigne</option>
                                                        <option>Leather B Unit</option>
                                                        <option>Forward Shoes - Gurgaon</option>
                                                        <option>Metro Exports</option>
                                                        <option>Farida & Groups</option>
                                                    </select>
                                                </div>
                                                <div class="hide" id="consignee-address">
                                                    <table class="table mb-0">
                                                        <tbody>
                                                        <tr>
                                                            <td>Address 1</td>
                                                            <td>13, Patel Yousuf Sahib Street</td>
                                                        </tr>
                                                        <tr>
                                                            <td>Address 2</td>
                                                            <td>R.N.Palayam</td>
                                                        </tr>
                                                        <tr>
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
                                                        </tr>
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
                                                        <input type="text" class="form-control" id="reciever-name">
                                                    </div>
                                                    <div class="form-group col-sm-3">
                                                        <label for="reciever-contact-no">Type Of Package</label>
                                                        <select class="form-control" id="sel1">
                                                            <option>COTTON BOX</option>
                                                            <option>Poly Bag</option>
                                                            <option>Roll</option>
                                                            <option>Sheet</option>
                                                            <option>Bundle</option>
                                                            <option>Cover</option>
                                                            <option>Poly Bundle</option>
                                                            <option>Can</option>
                                                            <option>Box</option>
                                                            <option>Mould</option>
                                                            <option>Packet</option>
                                                            <option>Cess</option>
                                                            <option>CAT</option>
                                                            <option>Gross Roll</option>
                                                            <option>Poly Roll</option>
                                                        </select>
                                                    </div>
                                                    <div class="form-group col-sm-3">
                                                        <label for="reciever-email">Invoice No</label>
                                                        <input type="email" class="form-control" id="reciever-email">
                                                    </div>
                                                    <div class="form-group col-sm-3">
                                                        <label for="reciever-city">Said To Contents</label>
                                                        <input type="text" class="form-control" id="reciever-city">
                                                    </div>
                                                    <div class="form-group col-sm-3">
                                                        <label for="reciever-address">Quantity </label>
                                                        <input type="text" class="form-control" id="reciever-address">
                                                    </div>
                                                    <div class="form-group col-sm-3">
                                                        <label for="reciever-area">Gross Wt.(Kgs)</label>
                                                        <input type="text" class="form-control" id="reciever-area">
                                                    </div>
                                                    <div class="form-group col-sm-3">
                                                        <label for="reciever-area">Charged Wt.(Kgs)</label>
                                                        <input type="text" class="form-control charged-weight"  id="reciever-area">
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
                                                <div id="image-uploader1" class="image-uploader col-sm-6">
                                                    <div class="box">
                                                        <div class="avatar-upload">
                                                            <div class="avatar-edit">
                                                                <!-- <input type='file' id="imageUpload" class="imageUpload" accept=".png, .jpg, .jpeg" /> -->
                                                                <input type='file' class="imageUpload" />
                                                                <label onclick="DelAttaDiv()" class="hide" for="imageUpload"></label>
                                                            </div>
                                                            <div class="avatar-preview">
                                                                <div id="imagePreview" class="imagePreview" style="background-image: url('images/doc.png');">
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                   
                                                   </div>
                                                   <div class="col-sm-12 text-right">
                                                        <a class="btn btn-primary" onclick="CloneAttaDiv()"> <span class="fa fa-plus" aria-hidden="true"></span> Add More</a>
                                                        <!-- <a class="btn btn-danger disabled" onclick="DelAttaDiv()"> <span class="fa fa-trash-o" aria-hidden="true"></span> Del Row</a> -->
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
                                            </div>
                                            <div class="send-rcv-dtl " >
                                                <div class="declaration-group">
                                                    <input class="form-check-input" type="checkbox" value="" id="declaration-checkbox">
                                                    <p>I hereby accept to book this consignment with with Gracious Express </p> 
                                                </div>
                                                <div class="col-sm-12 text-center submit-btn disabled">
                                                    <a class="btn btn-primary" onclick="submitDetails()">Submit</a>
                                                </div>
                                            </div>
                                        </div>

                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>      
            </div>
        </div>

       

    </div>

			
			


     
    
 <script>
              var wow = new WOW(
  {
    boxClass:     'wow',      // animated element css class (default is wow)
    animateClass: 'animated', // animation css class (default is animated)
    offset:       0,          // distance to the element when triggering the animation (default is 0)
    mobile:       true,       // trigger animations on mobile devices (default is true)
    live:         true,       // act on asynchronously loaded content (default is true)
    callback:     function(box) {
      // the callback is fired every time an animation is started
      // the argument that is passed in is the DOM node being animated
    },
    scrollContainer: null,    // optional scroll container selector, otherwise use window,
    resetAnimation: true,     // reset animation on end (default is true)
  }
);
wow.init();
              </script>
		<!-- SCRIPTS MAIN -->

		<script src="assets/js/jquery-migrate-1.2.1.js"></script>
		<script src="assets/plugins/bootstrap/js/bootstrap.min.js"></script>
		<script src="assets/js/waypoints.min.js"></script>
		<script src="http://cdnjs.cloudflare.com/ajax/libs/jquery-easing/1.3/jquery.easing.min.js"></script>
		<script src="assets/js/modernizr.custom.js"></script>
		<script src="assets/js/cssua.min.js"></script>
        

        <script type="text/javascript">
            $(document).ready(function(){
                if ($(window).width() < 992) {
                    $('.custom-col').removeClass('col-lg-2');
                } else {
                    $('.custom-col').addClass('col-lg-2');
                }
                $(window).resize(function () {
                    if ($(window).width() < 992) {
                        $('.custom-col').removeClass('col-lg-2');
                    } else {
                        $('.custom-col').addClass('col-lg-2');
                    }
                })
            })

            $(document).ready(function(){
                function readURL(input) {
                    alert("readurl");
                    var fileName = $(input).val().replace(/C:\\fakepath\\/i, '') ;
                    var extension = fileName.substr( fileName.lastIndexOf('.')+1);
                    alert(extension);
                    if (input.files && input.files[0]) {
                        alert("input files");

                        if(extension == 'png' || extension == 'jpeg' || extension == 'jpg'){
                            alert("inside if");
                            var reader = new FileReader();
                            reader.onload = function(e) {
                                var idName = $(input).parent().parent().parent().parent().attr('id');
                                $('#'+idName+ ' .imagePreview').css('background-image', 'url('+e.target.result +')');
                                $('#imagePreview').fadeIn(650);
                            }
                            reader.readAsDataURL(input.files[0]);
                        }else{
                          alert("else run");
                            
                            var firstchild_imageurl = 'images/download.png';
                            var reader = new FileReader();
                            reader.onload = function(e) {
                                var idName = $(input).parent().parent().parent().parent().attr('id');
                                $('#'+idName+ ' .imagePreview').css('background-image', 'url(images/download.png)');
                                $('#imagePreview').fadeIn(650);
                            }
                            reader.readAsDataURL(input.files[0]);
                        }
                    }
                }
                $(document).on('change','.imageUpload', function(){
                    alert("hi");
                    readURL(this);
                })
               
            })
        </script>

		<!--SCRIPTS THEME-->

		<!-- Home slider -->
		<script src="assets/plugins/slider-pro/dist/js/jquery.sliderPro.js"></script>
		<!-- Sliders -->
		<script src="assets/plugins/owl-carousel/owl.carousel.min.js"></script>

		<script src="assets/plugins/flexslider/jquery.flexslider.js"></script>
		<!-- Modal -->
		<script src="assets/plugins/prettyphoto/js/jquery.prettyPhoto.js"></script>
		<!-- Select customization -->
		<script src="assets/plugins/bootstrap-select/dist/js/bootstrap-select.js"></script>
		<!-- Chart -->
		<script src="assets/plugins/rendro-easy-pie-chart/dist/jquery.easypiechart.min.js"></script>
		<!-- Animation -->
		<script src="assets/plugins/scrollreveal/dist/scrollreveal.min.js"></script>
		<!-- Menu for android-->
		<script src="assets/js/doubletaptogo.js"></script>

		<!-- Custom -->
		<script src="assets/js/custom.js"></script>
		<script src="assets/js/book-consignment.js"></script>
		<script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>
		
        

	</body>
</html>

