(function ($){
    $('#contact-form').submit(event => {
        event.preventDefault();

        const userObj = {
            first_name: $("input[name='first_name']" ).val(),
            last_name: $("input[name='last_name']" ).val(),
            email: $("input[name='email']" ).val(),
            date: $("input[name='dob']" ).val(),
            uploaded_file_url: $("input[name='file']" ).val()
        }
console.log(userObj)
        $.ajax({
            method: 'post',
            url: restObj.restURL + 'contact-form/v1/send-email',
            headers: {'X-WP-Nonce': restObj.restNonce },
            dataType: 'text',
            data :  userObj,
            success: function() {
                $("#contact-form").html("Your data saved. See you inbox!")
            },
            error: function(xhr, status, error) {
                console.log("Error-->",error);
                console.log("xhr-->",xhr)
                console.log("status-->",status)
            }
       })
    })
})(jQuery)