<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIndicatorToOTPTokensTable extends Migration
{
    private string $tokenTable;

    private string $defaultIndicator;

    public function __construct()
    {
        $this->tokenTable = config('otp.token_table', 'otp_tokens');
        $this->defaultIndicator = config('otp.prefix', 'otp_tokens');
    }

    public function up(): void
    {
        if (Schema::hasTable($this->tokenTable)) {
            Schema::table($this->tokenTable, function (Blueprint $table): void {
                $table->string('indicator')->default($this->defaultIndicator);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable($this->tokenTable)) {
            Schema::table($this->tokenTable, static function (Blueprint $table): void {
                $table->dropColumn('indicator');
            });
        }
    }
}
