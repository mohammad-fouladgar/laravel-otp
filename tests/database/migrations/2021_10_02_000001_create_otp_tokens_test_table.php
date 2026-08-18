<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOtpTokensTestTable extends Migration
{
    private string $tokenTable;

    public function __construct()
    {
        $this->tokenTable = config('otp.token_table', 'otp_tokens');
    }

    public function up(): void
    {
        Schema::create(
            $this->tokenTable,
            static function (Blueprint $table): void {
                $table->increments('id');
                $table->string('recipient');
                $table->string('purpose');
                $table->string('token', 10);
                $table->timestamp('sent_at');
                $table->timestamp('expires_at');

                $table->index(
                    ['recipient', 'purpose', 'token'],
                    'otp_recipient_purpose_token_index',
                );
            },
        );
    }

    public function down(): void
    {
        Schema::dropIfExists($this->tokenTable);
    }
}
