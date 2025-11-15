$(() => {
    setInterval(() => {
        $.get('cart_count.php', count => {
            $('#cart-count').text(count);
        });
    }, 3000);
});