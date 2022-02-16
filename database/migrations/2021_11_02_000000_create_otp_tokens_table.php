<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOTPTokensTable extends Migration
{
    private string $tokenTable;

    private string $userTable;

    private string $mobileColumn;

    public function __construct()
    {
        $default            = config('otp.default_provider', 'users');
        $this->userTable    = config('otp.user_providers.'.$default.'.table', 'users');
        $this->mobileColumn = config('otp.mobile_column', 'mobile');
        $this->tokenTable   = config('otp.token_table', 'otp_tokens');
    }

    public function up(): void
    {
        if (!Schema::hasColumn($this->userTable, $this->mobileColumn)) {
            Schema::table(
                $this->userTable,
                function (Blueprint $table): void {
                    $table->string($this->mobileColumn);
                }
            );
        }

        if (config('otp.token_storage') === 'cache') {
            return;
        }

        Schema::create(
            $this->tokenTable,
            static function (Blueprint $table): void {
                $table->increments('id');
                $table->string('mobile')->index();
                $table->string('token', 10)->index();
                $table->timestamp('sent_at')->nullable();
                $table->timestamp('expires_at')->nullable();

                $table->index(['mobile', 'token']);
            }
        );
    }

    public function down(): void
    {
        if (config('otp.token_storage') === 'cache') {
            return;
        }
        Schema::drop($this->tokenTable);
    }
}
