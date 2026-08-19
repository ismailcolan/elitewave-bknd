// HIDE FOOTER
$('.section-clients, .section-default').css('display','none')

// SCROLL DOWN TO NEXT SECTIONS
$(document).on('click','#select-customer .cust-padding-margin',function(){
    $('.sub-block').removeClass('active');
    $(this).find('.sub-block').addClass('active');
    let idName = $(this).attr('id');
    if(idName == 'new-cust'){
        $('.block').removeClass('show')
        $('#select-shipping').addClass('show');
        $('html, body').animate({
            scrollTop: $("#select-shipping").offset().top - 200, 
        }, 1000);
    }else{
        $('.block').removeClass('show')
        $('#visit-login-page').addClass('show');
        $('html, body').animate({
            scrollTop: $("#visit-login-page").offset().top - 100, 
        }, 1000);
    }
})  

$(document).on('click','#select-shipping .cust-padding-margin',function(){
    $('#select-shipping .cust-padding-margin .sub-block').removeClass('active');
    $(this).find('.sub-block').addClass('active');
        // $('.block').removeClass('show')
        let idName = $(this).attr('id');
        if(idName == 'byRoad'){
            $('#select-load .cust-padding-margin .sub-block').removeClass('active');
            $('#select-sender-dtl, #select-payment-mode, #select-truck').removeClass('show');
            $('#select-load').addClass('show');
            $('html, body').animate({
                scrollTop: $("#select-load").offset().top - 200, 
            }, 1000);

        }else{
            $('#select-sender-dtl, #select-load, #select-truck').removeClass('show');
            $('#select-payment-mode .cust-padding-margin .sub-block').removeClass('active');
            $('#select-payment-mode').addClass('show');
            $('html, body').animate({
                scrollTop: $("#select-payment-mode").offset().top - 200, 
            }, 1000);
        }    
})  

$(document).on('click','#select-load .cust-padding-margin',function(){
    $('#select-load .cust-padding-margin .sub-block').removeClass('active');
    $(this).find('.sub-block').addClass('active');
    let idName = $(this).attr('id');
    if(idName == 'full-truck'){
        $('#select-sender-dtl, #select-payment-mode').removeClass('show')
        $('#select-truck').addClass('show');
        $('html, body').animate({
            scrollTop: $("#select-truck").offset().top - 200, 
        }, 1000);
    }else{
        $('#select-truck, #select-sender-dtl').removeClass('show');
        $('#select-payment-mode .cust-padding-margin .sub-block').removeClass('active');
        $('#select-payment-mode').addClass('show');
        $('html, body').animate({
            scrollTop: $("#select-payment-mode").offset().top - 200, 
        }, 1000);
    }
})

$(document).on('click','#select-truck .cust-padding-margin',function(){
    $('#select-truck .cust-padding-margin .sub-block').removeClass('active');
    $(this).find('.sub-block').addClass('active');
    $('#select-sender-dtl').removeClass('show');
    $('#select-payment-mode .cust-padding-margin .sub-block').removeClass('active');
        $('#select-payment-mode').addClass('show');
        $('html, body').animate({
            scrollTop: $("#select-payment-mode").offset().top - 200, 
        }, 1000);
})

$(document).on('click','#select-payment-mode .cust-padding-margin',function(){
    $('#select-payment-mode .cust-padding-margin .sub-block').removeClass('active');
    $(this).find('.sub-block').addClass('active');
    $('#select-sender-dtl').addClass('show');
    $('html, body').animate({
        scrollTop: $("#select-sender-dtl").offset().top - 200, 
    }, 1000);
})  
$(document).on('click','#select-sender-dtl .cust-padding-margin',function(){
    $('#select-sender-dtl .cust-padding-margin .sub-block').removeClass('active');
    // $(this).find('.sub-block').addClass('active');
})  

// ON FOCUSOUT FROM SENDER DETAILS INPUT BOX - MOVE TO RECIEVER DETAILS SECTION
$(document).on("focusout","#sender-details input",function() {
     if( $('#sender-name').val() != '' && $('#sender-contact-no').val() != '' && $('#sender-email').val() != '' && $('#sender-city').val() != '' && $('#sender-address').val() != '' && $('#sender-area').val() != '' ){
        $('#package-details, #reciever-details, #supporting-document').removeClass('disabled');
            $('html, body').animate({
                scrollTop: $("#package-details").offset().top -100 , 
            }, 1000);

        //  if($('#volumetric-check-box').is(':checked') == true){
        //     if( $('#length').val() != '' && $('#width').val() != '' && $('#height').val() != ''){
        //         $('#package-details').removeClass('disabled');
        //         $('html, body').animate({
        //             scrollTop: $("#package-details").offset().top -100 , 
        //         }, 1000);
        //     }
        //  }else{
        //     $('#package-details').removeClass('disabled');
        //     $('html, body').animate({
        //         scrollTop: $("#package-details").offset().top -100 , 
        //     }, 1000);
        //  }
        
     }else{
        $('#package-details, #reciever-details, #supporting-document').addClass('disabled');
     }
  })

  // ON FOCUSOUT FROM SENDER DETAILS INPUT BOX - MOVE TO RECIEVER DETAILS SECTION
$(document).on("focusout","#package-details input, #reciever-details input",function() {
    if( $('#package-count').val() != '' && $('#package-invoice').val() != '' && $('#package-content').val() != '' && $('#package-qty').val() != '' && $('#package-gross-wgt').val() != '' && $('#package-net-wgt').val() != '' ){
        if($('#volumetric-check-box').is(':checked') == true){
           if( $('#length').val() != '' && $('#width').val() != '' && $('#height').val() != ''){
               $('html, body').animate({
                   scrollTop: $("#reciever-details").offset().top -100 , 
               }, 1000);
           }
        }else{
           $('html, body').animate({
               scrollTop: $("#reciever-details").offset().top -100 , 
           }, 1000);
        }
       
    }
 })

  // ON FOCUSOUT FROM RECIEVER DETAILS INPUT BOX - MOVE TO SUPPORTING DOCS SECTION
//   $("#reciever-details input").focusout(function() {
//     if( $('#reciever-name').val() != '' && $('#reciever-contact-no').val() != '' && $('#reciever-email').val() != '' && $('#reciever-city').val() != '' && $('#reciever-address').val() != '' && $('#reciever-area').val() != ''){
//         $('html, body').animate({
//             scrollTop: $("#supporting-document").offset().top -100 , 
//         }, 1000);
//      }
//   })

//   RESTRICTION ON TAB PRESS - BOOK CONSIGNMENT PAGE
  $(document).on('keydown','#package-wgt',function(objEvent) {
    if (objEvent.keyCode == 9) {  //tab pressed
        if( $('#sender-name').val() == '' || $('#sender-contact-no').val() == '' || $('#sender-email').val() == '' || $('#sender-city').val() == '' || $('#sender-address').val() == '' || $('#sender-area').val() == '' || $('#package-qty').val() == '' || $('#package-wgt').val() == ''){
            objEvent.preventDefault(); // stops its action
        }else{
            if($('#volumetric-check-box').is(':checked') == true){
                if( $('#length').val() == '' || $('#width').val() == '' || $('#height').val() == ''){
                    objEvent.preventDefault(); // stops its action
                }
            }
        }
    }
})

//   RESTRICTION ON TAB PRESS - BOOK CONSIGNMENT PAGE
$(document).on('keydown','#reciever-area',function(objEvent) {
    if (objEvent.keyCode == 9) {
        if( $('#reciever-name').val() == '' || $('#reciever-contact-no').val() == '' || $('#reciever-email').val() == '' || $('#reciever-city').val() == '' || $('#reciever-address').val() == '' || $('#reciever-area').val() == ''){
            objEvent.preventDefault(); // stops its action
        }
    }
})

// DISABLE ENABLE WHEN CHECKBOX IS CHECKED
$(document).on('click','#volumetric-check-box',function(){
    if($(this).is(':checked')){
       $('.volumetric-input-boxes').removeClass('disabled')
       $('.volumetric-input-boxes input').attr('disabled',false);
    }else{
        $('.volumetric-input-boxes').addClass('disabled')
       $('.volumetric-input-boxes input').attr('disabled',true);
    }

})

// USER-BOOK-CONSIGNMENT PAGE 
$(document).on('change','#sel-consignee',function(){
    // alert("inside")
    if( $(this).val !== 'Select Consigne'){
        $('#consignee-address').removeClass('hide');
        $('#reciever-details, #Attachements, #payment-info, #declaration').removeClass('disabled');
        $('#supporting-document').removeClass('disabled');
        $('html, body').animate({
            scrollTop: $("#reciever-details").offset().top -100 , 
        }, 1000);
    }
})

$(document).on('click','#volum-info',function(){
    if($(this).is(':checked')){
       $('#supporting-document .send-rcv-dtl').removeClass('disabled')
    }else{
        $('#supporting-document .send-rcv-dtl').addClass('disabled')
    }
})
$(document).on('click','#declaration-checkbox',function(){
    if($(this).is(':checked')){
       $('#declaration .send-rcv-dtl .submit-btn').removeClass('disabled')
    }else{
        $('#declaration .send-rcv-dtl .submit-btn').addClass('disabled')
    }
})

    $(document).on('keyup keypress','#userbookconsignment', function(e) {
        var keyCode = e.keyCode || e.which;
        if (keyCode === 13) { 
            e.preventDefault();
            return false;
        }
        });

function CloneDiv(){
     // get the last DIV which ID starts with ^= "klon"
    var $div = $('div[id^="package-info"]:last');

    // Read the Number from that DIV's ID (i.e: 3 from "klon3")
    // And increment that number by 1
    var num = parseInt( $div.prop("id").match(/\d+/g), 10 ) +1;

    // Clone it and assign the new ID (i.e: from num 4 to ID "klon4")
    var $klon = $div.clone().prop('id', 'package-info'+num );

    console.log($klon)
    
    // Finally insert $klon wherever you want
    $div.after( $klon);
    $('#package-info'+num+' input' ).val('');
    // $(".package-info").clone().insertAfter(".package-info:last");

    if($('.package-info').length > 1){
        $('#reciever-details .btn-danger').removeClass('disabled');
    }
}

function CloneVolumDiv(){
     var $div = $('div[id^="volumetric-info"]:last');
     var num = parseInt( $div.prop("id").match(/\d+/g), 10 ) +1;
 
     // Clone it and assign the new ID (i.e: from num 4 to ID "klon4")
     var $klon = $div.clone().prop('id', 'volumetric-info'+num );
    
     // Finally insert $klon wherever you want
     $div.after( $klon);
     $('#volumetric-info'+num+' input' ).val('');
     if($('.volumetric-info').length > 1){
        $('#supporting-document .btn-danger').removeClass('disabled');
     }
}

function CloneAttaDiv(){
    var $div = $('div[id^="image-uploader"]:last');
    var num = parseInt( $div.prop("id").match(/\d+/g), 10 ) +1;

    // Clone it and assign the new ID (i.e: from num 4 to ID "klon4")
    var $klon = $div.clone().prop('id', 'image-uploader'+num );
   
    // Finally insert $klon wherever you want
    $div.after( $klon);
    var firstchild_imageurl = 'images/download.png';
    var lastchild_imageurl = 'images/download.png';
    $('#image-uploader'+num+' .box:first-child #imagePreview' ).css('background-image','url(images/doc.png)');
    $('#image-uploader'+num+' .box:first-child .imageUpload' ).val('');
    $('#image-uploader'+num+' .box:last-child .imagdpload' ).val('');
    if($('.image-uploader').length > 1){
       $('#image-uploader'+num+ ' label').removeClass('hide');
    }
}

function DelAttaDiv(){
    $('.image-uploader:last').remove();
    if($('.image-uploader').length == 1){
        $('#Attachements label').addClass('hide');
    }
}

function DelVolumDiv(){
    // alert("test");

    // console.log("volume removed");
    $('.volumetric-info:last').remove();
  
    if($('.volumetric-info').length == 1){
        calculation();
        ss();
        $('#supporting-document .btn-danger').addClass('disabled');
    }
}

function DelDiv(){
    $('.package-info:last').remove();
    if($('.package-info').length == 1){
        cumulative_charge_wight();
        ss();
        $('#reciever-details .btn-danger').addClass('disabled');
    }
}










// SWEET ALERT - BOOK CONSIGNMENT PAGE
function submitDetails(e){
    swal("Great!", "Your booking #AWB 85SFTN02, Our Executive will reach you to pick the consignment in next 2-3 Hours !", "success");
}

