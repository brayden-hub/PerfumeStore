<!-- Set $current_step before use -->

<div class="progress-bar" style="width: 100%; margin-bottom: 3rem;">
    <div style="display: flex; justify-content: space-between; align-items: center;">
        <!-- Step 1: Shopping Cart -->
        <div style="text-align: center;">
            <div style="width: 40px; height: 40px; background: <?= $current_step >= 1 ? '#D4AF37' : '#eee' ?>; color: <?= $current_step >= 1 ? '#000' : '#666' ?>; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; margin: 0 auto 0.5rem;">1</div>
            <p style="margin: 0; font-size: 0.9rem; color: <?= $current_step >= 1 ? '#000' : '#666' ?>;">Shopping Cart</p>
        </div>
        
        <div style="flex: 1; height: 2px; background: <?= $current_step >= 2 ? '#D4AF37' : '#eee' ?>; margin: 0 1rem;"></div>
        
        <!-- Step 2: Checkout & Payment -->
        <div style="text-align: center;">
            <div style="width: 40px; height: 40px; background: <?= $current_step >= 2 ? '#D4AF37' : '#eee' ?>; color: <?= $current_step >= 2 ? '#000' : '#666' ?>; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; margin: 0 auto 0.5rem;">2</div>
            <p style="margin: 0; font-size: 0.9rem; color: <?= $current_step >= 2 ? '#000' : '#666' ?>;">Checkout & Payment</p>
        </div>
        
        <div style="flex: 1; height: 2px; background: <?= $current_step >= 3 ? '#D4AF37' : '#eee' ?>; margin: 0 1rem;"></div>
        
        <!-- Step 3: Done -->
        <div style="text-align: center;">
            <div style="width: 40px; height: 40px; background: <?= $current_step >= 3 ? '#D4AF37' : '#eee' ?>; color: <?= $current_step >= 3 ? '#000' : '#666' ?>; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; margin: 0 auto 0.5rem;">3</div>
            <p style="margin: 0; font-size: 0.9rem; color: <?= $current_step >= 3 ? '#000' : '#666' ?>;">Done</p>
        </div>
    </div>
</div>