<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 移除已废弃的 social_platform / social_url 列。
     *
     * 背景：迁移 000003（2026_08_15）曾建单链接列 social_platform + social_url；
     * 迁移 000001（2026_08_16）改用 social_links JSON 数组后，已将 social_url 数据
     * 镜像进 social_links[0]，但旧列未 drop。此后前端、Request、Factory、Test 均
     * 只用 social_links，旧列从未被读取，属纯死字段。本迁移予以清理。
     */
    public function up(): void
    {
        Schema::table('entrepreneurs', function (Blueprint $table) {
            $table->dropColumn(['social_platform', 'social_url']);
        });
    }

    /**
     * 回滚仅重建空列：social_url 原始数据已迁入 social_links，不反向回写。
     */
    public function down(): void
    {
        Schema::table('entrepreneurs', function (Blueprint $table) {
            $table->string('social_platform', 50)->nullable()->after('contact_email');
            $table->string('social_url', 500)->nullable()->after('social_platform');
        });
    }
};
