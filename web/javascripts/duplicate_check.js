//Duplicate Check
	   var dup_chk = true; 
		function duplicate_check(){
			var data = $('#form_data').serialize();
			$.ajax({
					cache: false,
					url: 'check_existing.php', // url where to submit the request
					type : "POST", // type of action POST || GET
					dataType : 'json', // data type
					async: false,
					data : data, // post data 
					success : function(result) {
					console.log(result);
					$.each( result, function( index, value ){
						$('.'+index).text(value).attr("style","color:red");
						
					});
						if(result['key']>0){
							dup_chk = false;
						}
						else{
							dup_chk = true;
						}
					},
					error: function(jqxhr) {
					console.log(jqxhr.responseText);
				  }
			});
		}