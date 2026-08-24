<?php

use App\Http\Controllers\EntrepreneurController;
use App\Http\Controllers\MyProfileController;
use App\Http\Controllers\Admin\AdminController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| 公开路由
|--------------------------------------------------------------------------
*/
Route::get('/', [EntrepreneurController::class, 'home'])->middleware('auth')->name('home');
Route::get('/entrepreneurs', [EntrepreneurController::class, 'index'])->name('entrepreneurs.index');

/*
| 短链路由 /u/{slug}：复用 entrepreneurs.show。
| 放在 /entrepreneurs/{id} 前面，避免 {id} 先吃掉纯数字 slug。
| named route 指向 /u，route('entrepreneurs.show', $slug) 自动输出短链 URL。
*/
Route::get('/u/{slug}', [EntrepreneurController::class, 'show'])
    ->name('entrepreneurs.show')
    ->where('slug', '[a-z0-9]+(?:-[a-z0-9]+)*');

// 旧数字链接保留，不 301（避免微信预览读到目标 URL）
Route::get('/entrepreneurs/{id}', [EntrepreneurController::class, 'show'])
    ->whereNumber('id');

/*
|--------------------------------------------------------------------------
| 需要登录的路由
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])->group(function () {
    // 个人中心
    Route::get('/my/profile', [MyProfileController::class, 'show'])->name('profile.show');
    Route::patch('/my/profile', [MyProfileController::class, 'update'])->name('profile.update');
    Route::post('/my/profile', [MyProfileController::class, 'create'])->name('profile.create');

    // 短链可用性实时校验（AJAX 端点）
    Route::get('/my/profile/check-slug', [MyProfileController::class, 'checkSlug'])->name('profile.check-slug');

    // 推荐申请
    Route::post('/my/profile/featured-request', [MyProfileController::class, 'requestFeatured'])->name('profile.featured-request');
});

/*
|--------------------------------------------------------------------------
| 管理员路由（中间件 + Policy 双重保护）
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
    Route::get('/entrepreneurs', [AdminController::class, 'entrepreneurs'])->name('entrepreneurs');
    Route::post('/entrepreneurs/batch-approve', [AdminController::class, 'batchApprove'])->name('batch-approve')->middleware('throttle:30,1');
    Route::post('/entrepreneurs/batch-reject', [AdminController::class, 'batchReject'])->name('batch-reject')->middleware('throttle:30,1');
    Route::post('/entrepreneurs/{entrepreneur}/approve', [AdminController::class, 'approve'])->name('approve');
    Route::post('/entrepreneurs/{entrepreneur}/reject', [AdminController::class, 'reject'])->name('reject');
    Route::post('/entrepreneurs/{entrepreneur}/toggle-featured', [AdminController::class, 'toggleFeatured'])->name('toggle-featured');
    Route::delete('/entrepreneurs/{entrepreneur}', [AdminController::class, 'destroy'])->name('destroy');

    // 推荐申请审核
    Route::post('/featured-requests/{entrepreneur}/approve', [AdminController::class, 'approveFeatured'])->name('featured-approve');
    Route::post('/featured-requests/{entrepreneur}/reject', [AdminController::class, 'rejectFeatured'])->name('featured-reject');

    // 系统设置
    Route::get('/settings', [AdminController::class, 'settings'])->name('settings');
    Route::post('/settings', [AdminController::class, 'updateSettings'])->name('settings.update');
});

require __DIR__.'/auth.php';
require __DIR__.'/setup.php';
