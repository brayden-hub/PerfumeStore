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
        const msg = $(this).data('OK');
        if (msg && !confirm(msg)) {
            return; 
        }
        const url = $(this).data('post');
        const f = $('<form>').appendTo(document.body)[0];
        f.method = 'POST';
        f.action = url || location;
        f.submit();
    });


/*
    $(document).on('click', '[data-post]', function(e) {
        e.preventDefault();
        const msg = $(this).data('confirm');
        if (msg && !confirm(msg)) {
            return; 
        }
        const url = $(this).data('post');
        const f = $('<form>').appendTo('body')
                             .attr('method', 'post')
                             .attr('action', url);
        f.submit();
    });
*/

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

// app.js - Main JavaScript file for N°9 Perfume

$(document).ready(function() {
    
    // Update cart count on page load
    updateCartCount();
    
    // Generic AJAX GET handler
    $('[data-get]').on('click', function(e) {
        e.preventDefault();
        const url = $(this).data('get');
        location.href = url;
    });
    
    // Generic AJAX POST handler with confirmation
    $('[data-post]').on('click', function(e) {
        e.preventDefault();
        const url = $(this).data('post');
        const confirm_msg = $(this).data('confirm');
        
        if (confirm_msg) {
            if (!confirm(confirm_msg)) {
                return;
            }
        }
        
        const form = $('<form>', {
            method: 'POST',
            action: url
        });
        
        form.appendTo('body').submit();
    });
    
    // Add to cart from product grid (if any)
    $(document).on('click', '.quick-add-cart', function(e) {
        e.preventDefault();
        const productId = $(this).data('id');
        const btn = $(this);
        
        btn.prop('disabled', true).text('Adding...');
        
        $.post('/api/cart_add.php', {
            product_id: productId,
            quantity: 1
        }, function(response) {
            if (response.success) {
                updateCartCount();
                btn.text('✓ Added!');
                setTimeout(function() {
                    btn.prop('disabled', false).text('Add to Cart');
                }, 1500);
            } else {
                alert(response.message || 'Failed to add to cart');
                btn.prop('disabled', false).text('Add to Cart');
            }
        }, 'json').fail(function() {
            alert('Error. Please try again.');
            btn.prop('disabled', false).text('Add to Cart');
        });
    });
    
    // Photo upload preview
    $('label.upload input[type=file]').on('change', function(e) {
        const file = e.target.files[0];
        
        if (file) {
            if (file.type.indexOf('image') === -1) {
                alert('Please select an image file');
                return;
            }
            
            const reader = new FileReader();
            reader.onload = function(e) {
                $('label.upload img').attr('src', e.target.result);
            };
            reader.readAsDataURL(file);
        }
    });
});

    // Function to update cart count
    function updateCartCount() {
        $.get('/api/cart_count.php', function(response) {
            if (response.count > 0) {
                $('#cart-count').text(response.count).show();
            } else {
                $('#cart-count').hide();
            }
        }, 'json').fail(function() {
            console.log('Failed to fetch cart count');
        });
    }

$("#reg_phone_number").on("input", function() {
    let input = $(this).val().replace(/[^0-9]/g, ''); 

    if (input.startsWith('60')) {
      
        if (input.length > 13) {
            input = input.substring(0, 13);
        }
        $(this).val(input);

    } else if (input.startsWith('01')) {
        
        if (input.length > 11) {
            input = input.substring(0, 11);
        }

        let formatted = '';
        if (input.length > 3) {
           
            formatted = input.substring(0, 3) + '-' + input.substring(3);
        } else {
            formatted = input;
        }
        $(this).val(formatted);

    } else {
       
        if (input.length > 11) {
             input = input.substring(0, 11);
        }
        $(this).val(input);
    }
}).on("blur", function() {
    $(this).val($(this).val()); 
});
    
    const $toggle = $('#profile-menu-toggle');
    const $menu = $('#profile-dropdown-menu');

    
    $toggle.on('click', function(e) {
        e.stopPropagation(); 
        $menu.toggleClass('show');
    });

    
    $(document).on('click', function(e) {
        
        if (!$menu.is(e.target) && $menu.has(e.target).length === 0 && !$toggle.is(e.target)) {
            $menu.removeClass('show');
        }
    });
    
    
    $menu.on('click', function(e) {
        e.stopPropagation();
    });
    // === Dropdown End ===
    

// ============================================
    // NEW: Gallery Images Logic (Preview & Drag-n-Drop)
    // ============================================
    const $galleryInput = $('#gallery-input');
    const $galleryZone = $('#gallery-drop-zone');
    const $galleryPreview = $('#gallery-preview');

    // Only run this if the gallery elements exist on the page
    if ($galleryInput.length) {

        // A. Handle File Selection (Clicking the box)
        $galleryInput.on('change', function(e) {
            handleGalleryFiles(this.files);
        });

        // B. Visual Drag Effects (Highlight box when dragging)
        $galleryZone.on('dragenter dragover', function(e) {
            e.preventDefault();
            e.stopPropagation();
            $(this).addClass('dragover');
        });

        $galleryZone.on('dragleave drop', function(e) {
            e.preventDefault();
            e.stopPropagation();
            $(this).removeClass('dragover');
        });

        // C. Handle Dropped Files
        $galleryZone.on('drop', function(e) {
            e.preventDefault();
            const dt = e.originalEvent.dataTransfer;
            const files = dt.files;

            // 1. Manually assign dropped files to the <input> so they get submitted
            $galleryInput[0].files = files;

            // 2. Show the previews
            handleGalleryFiles(files);
        });

        // D. Function to Create Preview Images
        function handleGalleryFiles(files) {
            $galleryPreview.empty(); // Clear old previews

            if (files && files.length > 0) {
                Array.from(files).forEach(file => {
                    // Only process image files
                    if (file.type.startsWith('image/')) {
                        const reader = new FileReader();
                        
                        reader.onload = function(e) {
                            // Create the image tag
                            const img = $('<img>')
                                .addClass('gallery-preview-item')
                                .attr('src', e.target.result)
                                .attr('title', file.name)
                                .css({
                                    'width': '100px', 
                                    'height': '100px', 
                                    'object-fit': 'cover', 
                                    'margin': '5px',
                                    'border': '1px solid #ddd',
                                    'border-radius': '4px'
                                });
                            
                            // Add to the container
                            $galleryPreview.append(img);
                        };
                        
                        reader.readAsDataURL(file);
                    }
                });
            }
        }
    }
});

