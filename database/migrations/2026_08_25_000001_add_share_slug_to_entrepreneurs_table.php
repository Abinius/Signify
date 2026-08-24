<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 为企业家名片添加短链字段。
     * 格式：小写字母 + 数字 + 连字符，长度 3-40，可选。
     */
    public function up(): void
    {
        Schema::table('entrepreneurs', function (Blueprint $table) {
            $table->string('share_slug', 50)->nullable()->unique()->after('id');
        });
    }

    public function down(): void
    {
        Schema::table('entrepreneurs', function (Blueprint $table) {
            $table->dropColumn('share_slug');
        });
    }
};