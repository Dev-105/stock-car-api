<?php

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\ProductController;
use App\Http\Controllers\API\CartController;
use App\Http\Controllers\API\OrderController;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\PromoCodeController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Product public routes
Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{id}', [ProductController::class, 'show']);
Route::get('/products/theme/{theme}', [ProductController::class, 'searchByTheme']);
Route::get('/products/tag/{tagName}', [ProductController::class, 'getByTag']);

// Promo code verification (public)
Route::post('/promo-codes/verify', [PromoCodeController::class, 'verify']);

// Protected routes (require authentication)
Route::middleware('auth:sanctum')->group(function () {
    
    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/refresh', [AuthController::class, 'refresh']);
    
    // User profile
    Route::get('/profile', [UserController::class, 'profile']);
    Route::put('/profile', [UserController::class, 'updateProfile']);
    Route::post('/profile/image', [UserController::class, 'updateProfileImage']);
    Route::get('/likes', [UserController::class, 'getLikes']);
    Route::post('/likes/{productId}', [UserController::class, 'toggleLike']);
    Route::get('/fidelity-points', [UserController::class, 'getFidelityPoints']);
    
    // Cart routes
    Route::get('/cart', [CartController::class, 'index']);
    Route::post('/cart/add', [CartController::class, 'addToCart']);
    Route::put('/cart/{productId}', [CartController::class, 'updateQuantity']);
    Route::delete('/cart/{productId}', [CartController::class, 'removeFromCart']);
    Route::delete('/cart', [CartController::class, 'clearCart']);
    
    // Order routes
    Route::get('/orders', [OrderController::class, 'index']);
    Route::post('/orders', [OrderController::class, 'store']);
    Route::get('/orders/{id}', [OrderController::class, 'show']);
    Route::post('/orders/{id}/cancel', [OrderController::class, 'cancel']);

    // Review routes
    Route::post('/products/{productId}/reviews', [\App\Http\Controllers\API\ReviewController::class, 'store']);
    Route::delete('/reviews/{id}', [\App\Http\Controllers\API\ReviewController::class, 'destroy']);
    
    // Product management (admin only)
    Route::middleware('admin')->group(function () {
        Route::post('/products', [ProductController::class, 'store']);
        Route::put('/products/{id}', [ProductController::class, 'update']);
        Route::delete('/products/{id}', [ProductController::class, 'destroy']);
        Route::post('/products/{id}/images', [ProductController::class, 'addImage']);
        Route::delete('/products/images/{imageId}', [ProductController::class, 'deleteImage']);
        
        // Order management
        Route::get('/admin/orders', [OrderController::class, 'adminIndex']);
        Route::put('/admin/orders/{id}/status', [OrderController::class, 'updateStatus']);
        Route::get('/admin/stats', [OrderController::class, 'getStats']);
        
        // User management
        Route::get('/admin/users', [UserController::class, 'adminIndex']);
        Route::put('/admin/users/{id}/role', [UserController::class, 'updateRole']);
        Route::delete('/admin/users/{id}', [UserController::class, 'adminDestroy']);
        
        // Promo code management
        Route::get('/admin/promo-codes', [PromoCodeController::class, 'index']);
        Route::post('/admin/promo-codes', [PromoCodeController::class, 'store']);
        Route::delete('/admin/promo-codes/{id}', [PromoCodeController::class, 'destroy']);

        // Tag management
        Route::post('/admin/tags', [\App\Http\Controllers\API\TagController::class, 'store']);
        Route::delete('/admin/tags/{id}', [\App\Http\Controllers\API\TagController::class, 'destroy']);
    });
    
    // Generic tags list (publicly available but within auth for consistency with your structure if needed, or move out)
    Route::get('/tags', [\App\Http\Controllers\API\TagController::class, 'index']);
});