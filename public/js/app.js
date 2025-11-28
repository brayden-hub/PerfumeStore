$(() => {
    setInterval(() => {
        $.get('cart_count.php', count => {
            $('#cart-count').text(count);
        });
    }, 3000);
    $('form :input:not(button):first').focus();
    $('.err:first').prev().focus();
    $('.err:first').prev().find(':input:first').focus();
    $('[type=reset]').on('click', e => {
        e.preventDefault();
        location = location;
    });

    
    $(document).ready(function() {

        
        $(".show-pass").on("mousedown", function() {
            $($(this).data("target")).attr("type", "text");
        }).on("mouseup mouseleave", function() {
            $($(this).data("target")).attr("type", "password");
        });

        
        $("form").on("submit", function(e) {
            const pass = $("#password").val();
            const conf = $("#confirm_password").val();
            
            if (pass !== conf) {
                e.preventDefault(); 
                $("#confirmErr").text("Passwords do not match!");
                $("#confirm_password").focus();
            }
        });

        
        $("#confirm_password").on("input", function () {
            if ($(this).val() === $("#password").val()) {
                $("#confirmErr").text("");
            }
        });

    });

    // Initiate GET request
    $('[data-get]').on('click', e => {
        e.preventDefault();
        const url = e.target.dataset.get;
        location = url || location;
    });

    // Initiate POST request
    $('[data-post]').on('click', e => {
        e.preventDefault();
        const url = e.target.dataset.post;
        const f = $('<form>').appendTo(document.body)[0];
        f.method = 'POST';
        f.action = url || location;
        f.submit();
    });

    $('label.upload input[type=file]').on('change', e => {
        const f = e.target.files[0]; // <input type="file">
        const img = $(e.target).siblings('img')[0]; // <img>

        if (!img) return;

        img.dataset.src ??= img.src; // get this data -> /images/photo.jpg

        if (f?.type.startsWith('image/')) {
            img.src = URL.createObjectURL(f); // load the preview in
        }
        else {
            img.src = img.dataset.src; // load back original image file
            e.target.value = '';
        }
    });
    

});