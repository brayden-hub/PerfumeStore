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
// === 电话号码自动格式化 (register.php - Step 4) ===
    // app.js - 修复：60(12-13位)，01(10-11位且只有一个破折号)
$("#reg_phone_number").on("input", function() {
    let input = $(this).val().replace(/[^0-9]/g, ''); // 提取纯数字

    if (input.startsWith('60')) {
        // Case 1: 60 开头，限制 13 位纯数字
        if (input.length > 13) {
            input = input.substring(0, 13);
        }
        $(this).val(input);

    } else if (input.startsWith('01')) {
        // Case 2: 01 开头，限制 11 位纯数字
        if (input.length > 11) {
            input = input.substring(0, 11);
        }

        let formatted = '';
        if (input.length > 3) {
            // 只在第3位后面加一个破折号
            formatted = input.substring(0, 3) + '-' + input.substring(3);
        } else {
            formatted = input;
        }
        $(this).val(formatted);

    } else {
        // 其他情况默认限制 11 位
        if (input.length > 11) {
             input = input.substring(0, 11);
        }
        $(this).val(input);
    }
}).on("blur", function() {
    $(this).val($(this).val()); 
});
    // === 格式化结束 ===
    // === Profile Dropdown Toggle ===
    const $toggle = $('#profile-menu-toggle');
    const $menu = $('#profile-dropdown-menu');

    // 1. 点击头像时切换菜单显示
    $toggle.on('click', function(e) {
        e.stopPropagation(); // 防止点击头像后立即触发 document click
        $menu.toggleClass('show');
    });

    // 2. 点击菜单外部时隐藏菜单
    $(document).on('click', function(e) {
        // 如果点击的不是菜单或头像，则隐藏菜单
        if (!$menu.is(e.target) && $menu.has(e.target).length === 0 && !$toggle.is(e.target)) {
            $menu.removeClass('show');
        }
    });
    
    // 3. 确保点击菜单内的链接不会触发 document click 导致菜单立即关闭
    $menu.on('click', function(e) {
        e.stopPropagation();
    });
    // === Dropdown End ===
    

// === Drag and Drop Image Upload Logic ===
    const $dropZone = $('label.upload');

    // 1. Prevent default browser behavior (prevents opening the file in tab)
    $dropZone.on('dragenter dragover dragleave drop', function(e) {
        e.preventDefault();
        e.stopPropagation();
    });

    // 2. Add visual highlight when dragging over
    $dropZone.on('dragenter dragover', function() {
        $(this).addClass('dragover');
    });

    // 3. Remove highlight when dragging away or dropped
    $dropZone.on('dragleave drop', function() {
        $(this).removeClass('dragover');
    });

    // 4. Handle the file drop
    $dropZone.on('drop', function(e) {
        // Retrieve the files from the drag event
        const dt = e.originalEvent.dataTransfer;
        const files = dt.files;
        const fileInput = $(this).find('input[type="file"]');

        if (files.length > 0) {
            // Assign dropped files to the hidden input element
            // Note: .files property is supported in all modern browsers
            fileInput[0].files = files;

            // Trigger the manual 'change' event
            // This runs your EXISTING preview logic (FileReader) defined in app.js
            fileInput.trigger('change');
        }
    });
});

