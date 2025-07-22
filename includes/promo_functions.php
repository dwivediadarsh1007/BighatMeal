<?php
function validatePromoCode($code, $user_id, $subtotal) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT * FROM promo_codes WHERE code = ? AND is_active = 1 AND start_date <= NOW() AND (end_date >= NOW() OR end_date IS NULL) AND (usage_limit > times_used OR usage_limit IS NULL)");
    $stmt->execute([$code]);
    $promo = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$promo) {
        return ['valid' => false, 'message' => 'Invalid or expired promo code'];
    }
    
    // Check if user has already used this code
    $stmt = $conn->prepare("SELECT COUNT(*) as usage_count FROM orders WHERE user_id = ? AND promo_code = ?");
    $stmt->execute([$user_id, $code]);
    $usage = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($usage['usage_count'] > 0) {
        return ['valid' => false, 'message' => 'You have already used this promo code'];
    }
    
    // Check minimum order amount
    if ($subtotal < $promo['min_order_amount']) {
        return ['valid' => false, 'message' => 'Minimum order amount for this promo is ₹' . $promo['min_order_amount']];
    }
    
    // Calculate discount amount
    $discount = 0;
    if ($promo['discount_type'] === 'percentage') {
        $discount = ($promo['discount_value'] / 100) * $subtotal;
        if ($promo['max_discount'] > 0 && $discount > $promo['max_discount']) {
            $discount = $promo['max_discount'];
        }
    } else {
        $discount = $promo['discount_value'];
    }
    
    return [
        'valid' => true,
        'code' => $promo['code'],
        'discount' => $discount,
        'discount_type' => $promo['discount_type'],
        'discount_value' => $promo['discount_value'],
        'message' => 'Promo code applied successfully!',
        'discount_display' => $promo['discount_type'] === 'percentage' 
            ? $promo['discount_value'] . '% off' 
            : '₹' . $promo['discount_value'] . ' off'
    ];
}

function incrementPromoCodeUsage($code) {
    global $conn;
    $stmt = $conn->prepare("UPDATE promo_codes SET times_used = times_used + 1 WHERE code = ?");
    $stmt->execute([$code]);
    return $stmt->rowCount() > 0;
}
