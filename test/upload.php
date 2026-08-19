<input id="sortpicture" type="file" name="sortpic" />
<button id="upload">Upload</button>
<script src="assets/plugins/jquery/jquery-1.11.3.min.js"></script>
<script>
$('#upload').on('click', function() {
    var file_data = $('#sortpicture').prop('files')[0];   
    var form_data = new FormData();                  
    form_data.append('file', file_data);
    form_data.append('name', name);

    alert(form_data);                             
    $.ajax({
        url: 'uploaded.php', // <-- point to server-side PHP script 
         // <-- what to expect back from the PHP script, if anything
        cache: false,
        contentType: false,
        processData: false,
        data: {form_data},                         
        type: 'post',
        success: function(php_script_response){
            alert(php_script_response); // <-- display response from the PHP script, if any
        }
     });
});
</script>
<script>
$.ajax({
						url:'save.php',
						cache: false,
						contentType: false,
						processData: false,
						type:'post',
						data:form_data,
						success:function(response){
							console.log(response);
						}
					
					}),
</script>
