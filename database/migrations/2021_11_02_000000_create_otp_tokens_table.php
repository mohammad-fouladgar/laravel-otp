<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOTPTokensTable extends Migration
{
    private string $tokenTable;

    public function __construct()
    {
        $this->tokenTable = config('otp.token_table', 'otp_tokens');
    }

    public function up(): void
    {
        if (config('otp.token_storage') === 'cache') {
            return;
        }

        if (Schema::hasTable($this->tokenTable)) {
            return;
        }

        Schema::create($this->tokenTable, static function (Blueprint $table): void {
            $table->increments('id');
            $table->string('recipient')->index();
            $table->string('purpose');
            $table->string('token', 10);
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('expires_at')->nullable();

            $table->index(
                ['recipient', 'purpose', 'token'],
                'otp_recipient_purpose_token_index',
            );
        });
    }

    public function down(): void
    {
        if (config('otp.token_storage') === 'cache') {
            return;
        }

        Schema::dropIfExists($this->tokenTable);
    }
}
